// Formulário público de agendamento (Alpine.js).
// Regras do projeto: aspas simples e concatenação, sem template literals.

export default function formAgendamento(config) {
    return {
        servicos: config.servicos || {},
        servicoId: config.antigo.servico_id ? String(config.antigo.servico_id) : '',
        profissionalId: config.antigo.profissional_id ? String(config.antigo.profissional_id) : '',
        data: config.antigo.data || '',
        hora: config.antigo.hora || '',
        slots: [],
        carregando: false,
        buscou: false,
        erroSlots: '',
        mostrarEspera: false,
        requisicao: 0,

        init: function () {
            var self = this;
            this.$watch('servicoId', function () {
                var aptas = self.profissionais.map(function (p) { return String(p.id); });
                if (aptas.indexOf(self.profissionalId) === -1) {
                    self.profissionalId = '';
                }
                self.buscar();
            });
            this.$watch('profissionalId', function () { self.buscar(); });
            this.$watch('data', function () { self.buscar(); });
            if (this.servicoId && this.data) {
                this.buscar();
            }
        },

        get servico() {
            return this.servicos[this.servicoId] || null;
        },

        get profissionais() {
            return this.servico ? this.servico.profissionais : [];
        },

        get semHorarios() {
            return this.buscou && !this.carregando && !this.erroSlots && this.slots.length === 0;
        },

        get podeEnviar() {
            return this.servicoId && this.data && this.hora;
        },

        escolher: function (hora) {
            this.hora = hora;
        },

        buscar: function () {
            var self = this;
            this.slots = [];
            this.buscou = false;
            this.erroSlots = '';
            this.mostrarEspera = false;
            if (!this.servicoId || !this.data) {
                return;
            }

            var id = ++this.requisicao;
            var params = new URLSearchParams();
            params.append('servico_id', this.servicoId);
            params.append('data', this.data);
            if (this.profissionalId) {
                params.append('profissional_id', this.profissionalId);
            }

            this.carregando = true;
            fetch(config.urlHorarios + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(function (resposta) {
                    if (!resposta.ok) {
                        throw new Error('falha');
                    }
                    return resposta.json();
                })
                .then(function (dados) {
                    if (id !== self.requisicao) {
                        return;
                    }
                    self.slots = dados;
                    var aindaExiste = dados.some(function (s) { return s.hora === self.hora; });
                    if (!aindaExiste) {
                        self.hora = '';
                    }
                })
                .catch(function () {
                    if (id === self.requisicao) {
                        self.erroSlots = 'Não foi possível carregar os horários. Tente novamente.';
                    }
                })
                .finally(function () {
                    if (id === self.requisicao) {
                        self.carregando = false;
                        self.buscou = true;
                    }
                });
        }
    };
}
