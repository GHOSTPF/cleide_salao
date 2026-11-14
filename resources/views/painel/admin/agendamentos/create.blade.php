@extends('painel.layout')
@section('titulo', 'Novo agendamento')

@php
    $servicosProf = $servicos->mapWithKeys(fn ($s) => [$s->id => $s->profissionais->map(fn ($p) => ['id' => $p->id, 'nome' => $p->nome_exibicao])->values()]);
    $clienteSel = $cliente ? ['id' => $cliente->id, 'nome' => $cliente->nome, 'telefone' => $cliente->telefone_formatado] : null;
@endphp

@section('conteudo')
@if ($entrada)
    <p class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-900">Convertendo entrada da lista de espera de <strong>{{ $entrada->cliente->nome }}</strong> ({{ $entrada->data_desejada->format('d/m') }}, {{ \App\Models\ListaEspera::PERIODOS[$entrada->periodo_preferido] }}).</p>
@endif

<form method="POST" action="{{ route('admin.agendamentos.store') }}" class="grid max-w-5xl gap-6 lg:grid-cols-2"
      x-data="Object.assign(seletorHorario(@js(['url' => route('admin.horarios'), 'servicoId' => $prefill['servico_id'], 'profissionalId' => $prefill['profissional_id'], 'data' => $prefill['data'], 'hora' => $prefill['hora']])), { mapa: @js($servicosProf), encaixe: {{ old('encaixe') ? 'true' : 'false' }} })">
    @csrf
    @if ($entrada)<input type="hidden" name="lista_espera_id" value="{{ $entrada->id }}">@endif

    <x-card titulo="Cliente">
        <div x-data="buscaCliente(@js(['url' => route('admin.clientes.buscar'), 'selecionada' => $clienteSel]))">
            <template x-if="selecionada">
                <div class="flex items-center justify-between rounded-lg bg-luna-cream px-4 py-3">
                    <div><p class="font-medium" x-text="selecionada.nome"></p><p class="text-sm text-gray-600" x-text="selecionada.telefone"></p></div>
                    <input type="hidden" name="cliente_id" :value="selecionada.id">
                    <button type="button" class="btn-secundario btn-sm" @click="limpar()">Trocar</button>
                </div>
            </template>
            <div x-show="!selecionada" class="space-y-4">
                <div class="relative">
                    <label class="rotulo" for="busca-cliente">Buscar cliente cadastrada</label>
                    <input id="busca-cliente" type="search" class="campo" placeholder="Nome ou telefone" x-model="termo" @input="buscar()" autocomplete="off">
                    <ul x-show="aberto && resultados.length" x-cloak @click.outside="aberto = false" class="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-luna-sand bg-white shadow-soft">
                        <template x-for="c in resultados" :key="c.id">
                            <li><button type="button" class="w-full px-4 py-2 text-left text-sm hover:bg-luna-cream" @click="escolher(c)"><span x-text="c.nome"></span> · <span class="text-gray-600" x-text="c.telefone"></span></button></li>
                        </template>
                    </ul>
                </div>
                <p class="text-xs text-gray-600">Não encontrou? Cadastre agora (se o WhatsApp já existir, a cliente existente é usada):</p>
                <x-input nome="nome" rotulo="Nome" />
                <x-input nome="telefone" rotulo="WhatsApp" tipo="tel" placeholder="(83) 99999-9999" x-mask="(99) 99999-9999" />
            </div>
        </div>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <x-select nome="origem" rotulo="Origem" :opcoes="['whatsapp' => 'Combinado no WhatsApp', 'painel' => 'Balcão / telefone']" :valor="old('origem', 'whatsapp')" />
        </div>
        <div class="mt-4 space-y-4">
            <x-input nome="observacoes_cliente" rotulo="Observações da cliente" tipo="textarea" rows="2" />
            <x-input nome="observacoes_internas" rotulo="Observações internas" tipo="textarea" rows="2" />
        </div>
    </x-card>

    <x-card titulo="Serviço e horário">
        <div class="space-y-4">
            <div>
                <label class="rotulo" for="servico_id">Serviço</label>
                <select name="servico_id" id="servico_id" class="campo" x-model="servicoId" required>
                    <option value="">Selecione</option>
                    @foreach ($servicos->groupBy(fn ($s) => $s->categoria->nome) as $cat => $lista)
                        <optgroup label="{{ $cat }}">
                            @foreach ($lista as $s)<option value="{{ $s->id }}">{{ $s->nome }} · {{ $s->duracao_formatada }}</option>@endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="rotulo" for="profissional_id">Profissional</label>
                <select name="profissional_id" id="profissional_id" class="campo" x-model="profissionalId" required>
                    <option value="">Selecione</option>
                    <template x-for="p in (mapa[servicoId] || [])" :key="p.id">
                        <option :value="String(p.id)" x-text="p.nome" :selected="String(p.id) === profissionalId"></option>
                    </template>
                </select>
            </div>
            <div><label class="rotulo" for="data">Data</label><input type="date" name="data" id="data" class="campo" x-model="data" required></div>

            <fieldset>
                <legend class="rotulo">Horário</legend>
                <input type="hidden" :name="encaixe ? '' : 'hora'" :value="hora">
                <div x-show="!encaixe">
                    <p x-show="carregando" class="text-sm text-gray-600">Carregando...</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="s in slots" :key="s.hora">
                            <button type="button" class="pilula" :class="hora === s.hora && 'pilula-ativa'" x-text="s.hora" @click="hora = s.hora"></button>
                        </template>
                    </div>
                    <p x-show="buscou && !carregando && !slots.length" x-cloak class="text-sm text-gray-600">Nenhum horário livre no expediente.</p>
                </div>

                <label class="mt-4 flex items-start gap-2 text-sm">
                    <input type="checkbox" name="encaixe" value="1" x-model="encaixe" @change="manual = encaixe" class="mt-0.5 rounded border-gray-300 text-luna-ink focus:ring-luna-gold">
                    <span>Encaixe fora do expediente <span class="text-gray-600">(continua impedindo conflito com outro agendamento)</span></span>
                </label>
                <div x-show="encaixe" x-cloak class="mt-3 space-y-3 rounded-lg border border-amber-300 bg-amber-50 p-4">
                    <div><label class="rotulo" for="hora-manual">Horário do encaixe</label><input type="time" id="hora-manual" class="campo" :name="encaixe ? 'hora' : ''" x-model="hora" step="300"></div>
                    <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirmar_encaixe" value="1" class="mt-0.5 rounded border-gray-300"> Confirmo o encaixe fora do expediente.</label>
                </div>
            </fieldset>

            <x-button class="w-full py-3" icone="check">Criar agendamento</x-button>
        </div>
    </x-card>
</form>
@endsection
