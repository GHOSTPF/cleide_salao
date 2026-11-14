// Componentes Alpine do painel. Regras: aspas simples, sem template literals.

export function seletorHorario(config) {
    return {
        servicoId: config.servicoId ? String(config.servicoId) : '',
        profissionalId: config.profissionalId ? String(config.profissionalId) : '',
        data: config.data || '',
        hora: config.hora || '',
        manual: false,
        slots: [],
        carregando: false,
        buscou: false,
        requisicao: 0,

        init: function () {
            var self = this;
            ['servicoId', 'profissionalId', 'data'].forEach(function (campo) {
                self.$watch(campo, function () { self.buscar(); });
            });
            this.buscar();
        },

        buscar: function () {
            var self = this;
            if (this.manual) {
                return;
            }
            this.slots = [];
            this.buscou = false;
            if (!this.servicoId || !this.profissionalId || !this.data) {
                return;
            }
            var id = ++this.requisicao;
            var params = new URLSearchParams();
            params.append('servico_id', this.servicoId);
            params.append('profissional_id', this.profissionalId);
            params.append('data', this.data);
            if (config.ignorar) {
                params.append('ignorar', config.ignorar);
            }
            this.carregando = true;
            fetch(config.url + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(function (dados) {
                    if (id !== self.requisicao) {
                        return;
                    }
                    self.slots = dados;
                    if (!dados.some(function (s) { return s.hora === self.hora; })) {
                        self.hora = '';
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

export function concluirAtendimento(config) {
    return {
        valor: config.valor || '',
        dividir: false,
        forma1: 'pix',
        valor1: config.valor || '',
        forma2: 'dinheiro',
        valor2: '',

        numero: function (v) {
            if (v === null || v === undefined || v === '') {
                return 0;
            }
            var s = String(v).trim();
            if (s.indexOf(',') !== -1) {
                s = s.replace(/\./g, '').replace(',', '.');
            }
            var n = parseFloat(s);
            return isNaN(n) ? 0 : n;
        },

        get soma() {
            return this.numero(this.valor1) + (this.dividir ? this.numero(this.valor2) : 0);
        },

        get confere() {
            return Math.abs(this.soma - this.numero(this.valor)) < 0.01;
        },

        atualizarValor: function () {
            if (!this.dividir) {
                this.valor1 = this.valor;
            }
        },

        dinheiro: function (n) {
            return 'R$ ' + n.toFixed(2).replace('.', ',');
        }
    };
}

export function buscaCliente(config) {
    return {
        termo: '',
        resultados: [],
        selecionada: config.selecionada || null,
        aberto: false,
        temporizador: null,

        buscar: function () {
            var self = this;
            clearTimeout(this.temporizador);
            if (this.termo.length < 2) {
                this.resultados = [];
                return;
            }
            this.temporizador = setTimeout(function () {
                fetch(config.url + '?q=' + encodeURIComponent(self.termo), { headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (dados) {
                        self.resultados = dados;
                        self.aberto = true;
                    });
            }, 250);
        },

        escolher: function (c) {
            this.selecionada = c;
            this.aberto = false;
            this.termo = '';
        },

        limpar: function () {
            this.selecionada = null;
        }
    };
}
