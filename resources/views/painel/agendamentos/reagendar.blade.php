@extends('painel.layout')
@section('titulo', 'Reagendar ' . $ag->codigo)

@section('conteudo')
<div class="grid max-w-4xl gap-6 lg:grid-cols-5">
    <x-card titulo="Horário atual" class="lg:col-span-2">
        <dl class="space-y-2 text-sm">
            <div><dt class="text-gray-600">Cliente</dt><dd class="font-medium">{{ $ag->cliente->nome }}</dd></div>
            <div><dt class="text-gray-600">Serviço</dt><dd>{{ $ag->servico->nome }}</dd></div>
            <div><dt class="text-gray-600">Profissional</dt><dd>{{ $ag->profissional->nome_exibicao }}</dd></div>
            <div><dt class="text-gray-600">Quando</dt><dd class="font-medium">{{ \App\Support\WhatsAppLink::dataExtenso($ag->inicio) }} às {{ $ag->inicio->format('H:i') }}</dd></div>
        </dl>
        <p class="mt-4 text-xs text-gray-600">O agendamento atual será marcado como “Reagendado” e um novo será criado com o código {{ $ag->codigo_base }}-R…, mantendo o histórico completo.</p>
    </x-card>

    <x-card titulo="Novo horário" class="lg:col-span-3">
        <form method="POST" action="{{ route($prefixo . 'agendamentos.reagendar', $ag) }}" class="space-y-4"
              x-data="Object.assign(seletorHorario(@js([
                  'url' => route($prefixo . 'horarios'),
                  'servicoId' => $ag->servico_id,
                  'profissionalId' => old('profissional_id', $ag->profissional_id),
                  'data' => old('data', $ag->inicio->toDateString()),
                  'hora' => old('hora'),
                  'ignorar' => $ag->id,
              ])), { encaixe: false })">
            @csrf
            @if (auth()->user()->isAdmin() && $profissionais->count() > 1)
                <div>
                    <label class="rotulo" for="profissional_id">Profissional</label>
                    <select name="profissional_id" id="profissional_id" class="campo" x-model="profissionalId">
                        @foreach ($profissionais as $p)<option value="{{ $p->id }}">{{ $p->nome_exibicao }}</option>@endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="profissional_id" value="{{ $ag->profissional_id }}">
            @endif

            <div><label class="rotulo" for="data">Nova data</label><input type="date" id="data" name="data" class="campo" x-model="data" min="{{ today()->toDateString() }}" required></div>

            <fieldset>
                <legend class="rotulo">Novo horário</legend>
                <input type="hidden" :name="encaixe ? '' : 'hora'" :value="hora">
                <div x-show="!encaixe">
                    <p x-show="carregando" class="text-sm text-gray-600">Carregando...</p>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="s in slots" :key="s.hora">
                            <button type="button" class="pilula" :class="hora === s.hora && 'pilula-ativa'" x-text="s.hora" @click="hora = s.hora"></button>
                        </template>
                    </div>
                    <p x-show="buscou && !carregando && !slots.length" x-cloak class="text-sm text-gray-600">Nenhum horário livre nesta data.</p>
                </div>
                @if (auth()->user()->isAdmin())
                    <label class="mt-3 flex items-center gap-2 text-sm"><input type="checkbox" name="encaixe" value="1" x-model="encaixe" @change="manual = encaixe" class="rounded border-gray-300"> Encaixe fora do expediente</label>
                    <div x-show="encaixe" x-cloak class="mt-2"><input type="time" class="campo" :name="encaixe ? 'hora' : ''" x-model="hora" step="300" aria-label="Horário do encaixe"></div>
                @endif
            </fieldset>

            <x-select nome="pedido_por" rotulo="Quem pediu"
                      :opcoes="auth()->user()->isAdmin() ? ['cliente' => 'Cliente (WhatsApp/telefone)', 'salao' => 'Salão'] : ['cliente' => 'Cliente (WhatsApp/telefone)', 'profissional' => 'Eu (profissional)']" />
            <x-input nome="motivo" rotulo="Motivo" placeholder="Ex.: imprevisto" />

            <div class="flex gap-2">
                <a href="{{ route($prefixo . 'agendamentos.show', $ag) }}" class="btn-secundario">Voltar</a>
                <button class="btn-primario" :disabled="!hora">Reagendar</button>
            </div>
        </form>
    </x-card>
</div>
@endsection
