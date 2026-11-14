@extends('painel.layout')
@section('titulo', 'Agenda')

@php
    $pxMin = 1.2; // altura por minuto na visão dia
    $passo = $visao === 'semana' ? 7 : 1;
    $qs = fn ($d) => ['data' => $d->toDateString(), 'visao' => $visao, 'profissional_id' => $profissionalId];
@endphp

@section('conteudo')
<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ route('admin.agenda', $qs($data->copy()->subDays($passo))) }}" class="btn-secundario btn-sm" aria-label="Anterior"><x-icone nome="seta-esq" class="w-4 h-4" /></a>
    <a href="{{ route('admin.agenda', $qs(today())) }}" class="btn-secundario btn-sm">Hoje</a>
    <a href="{{ route('admin.agenda', $qs($data->copy()->addDays($passo))) }}" class="btn-secundario btn-sm" aria-label="Próximo"><x-icone nome="seta-dir" class="w-4 h-4" /></a>
    <p class="mx-2 font-serif text-xl">
        @if ($visao === 'dia')
            {{ ucfirst($data->translatedFormat('l, d \d\e F')) }}
        @else
            {{ $de->format('d/m') }} a {{ $ate->format('d/m/Y') }}
        @endif
    </p>

    <form method="GET" class="ml-auto flex flex-wrap items-center gap-2">
        <input type="date" name="data" value="{{ $data->toDateString() }}" class="campo w-auto py-1.5 text-sm" onchange="this.form.submit()">
        <select name="profissional_id" class="campo w-auto py-1.5 text-sm" onchange="this.form.submit()" aria-label="Profissional">
            <option value="">Todas as profissionais</option>
            @foreach ($profissionais as $p)
                <option value="{{ $p->id }}" @selected($profissionalId === $p->id)>{{ $p->nome_exibicao }}</option>
            @endforeach
        </select>
        <div class="flex overflow-hidden rounded-md border border-gray-300 bg-white text-sm">
            <button name="visao" value="dia" class="px-3 py-1 {{ $visao === 'dia' ? 'bg-luna-ink text-white' : 'hover:bg-luna-cream' }}">Dia</button>
            <button name="visao" value="semana" class="border-l border-gray-300 px-3 py-1 {{ $visao === 'semana' ? 'bg-luna-ink text-white' : 'hover:bg-luna-cream' }}">Semana</button>
        </div>
        <a href="{{ route('admin.agendamentos.create', ['data' => $data->toDateString()]) }}" class="btn-primario btn-sm"><x-icone nome="mais" class="w-4 h-4" /> Novo agendamento</a>
    </form>
</div>

@if ($visao === 'dia')
    @php
        $bloqueiosSalao = $bloqueios->whereNull('profissional_id');
    @endphp
    @foreach ($bloqueiosSalao as $b)
        <p class="mb-3 flex items-center gap-2 rounded-md border border-gray-300 bg-gray-50 px-4 py-2 text-sm text-gray-800"><x-icone nome="cadeado" class="w-4 h-4 shrink-0" /> Salão fechado: {{ $b->motivo }} ({{ $b->inicio->format('d/m H:i') }} – {{ $b->fim->format('d/m H:i') }})</p>
    @endforeach

    <div class="card overflow-x-auto p-0">
        <div class="flex min-w-max">
            {{-- Régua de horas --}}
            <div class="sticky left-0 z-10 w-14 shrink-0 border-r border-luna-sand bg-white">
                <div class="h-12 border-b border-luna-sand"></div>
                <div class="relative" style="height: {{ ($horaFim - $horaIni) * 60 * $pxMin }}px">
                    @for ($h = $horaIni; $h < $horaFim; $h++)
                        <span class="absolute right-2 -translate-y-1/2 text-[11px] text-gray-600" style="top: {{ ($h - $horaIni) * 60 * $pxMin }}px">{{ sprintf('%02d:00', $h) }}</span>
                    @endfor
                </div>
            </div>

            @forelse ($colunas as $prof)
                @php
                    $expediente = $prof->horarios->where('dia_semana', $data->dayOfWeek);
                    $doProf = $agendamentos->where('profissional_id', $prof->id);
                @endphp
                <div class="w-56 shrink-0 border-r border-luna-sand sm:w-64">
                    <div class="flex h-12 items-center gap-2 border-b border-luna-sand px-3">
                        <span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $prof->cor_agenda }}"></span>
                        <span class="font-medium">{{ $prof->nome_exibicao }}</span>
                        <span class="ml-auto text-xs tabular-nums text-gray-500">{{ $doProf->count() }}</span>
                    </div>
                    <div class="relative bg-gray-50" style="height: {{ ($horaFim - $horaIni) * 60 * $pxMin }}px">
                        {{-- Linhas das horas --}}
                        @for ($h = $horaIni; $h < $horaFim; $h++)
                            <div class="absolute inset-x-0 border-t border-gray-200" style="top: {{ ($h - $horaIni) * 60 * $pxMin }}px"></div>
                        @endfor
                        {{-- Expediente --}}
                        @foreach ($expediente as $e)
                            @php
                                $ini = (int) substr($e->hora_inicio, 0, 2) * 60 + (int) substr($e->hora_inicio, 3, 2) - $horaIni * 60;
                                $fim = (int) substr($e->hora_fim, 0, 2) * 60 + (int) substr($e->hora_fim, 3, 2) - $horaIni * 60;
                            @endphp
                            <div class="absolute inset-x-0 bg-white" style="top: {{ $ini * $pxMin }}px; height: {{ ($fim - $ini) * $pxMin }}px"></div>
                            @if ($e->intervalo_inicio)
                                @php
                                    $ii = (int) substr($e->intervalo_inicio, 0, 2) * 60 + (int) substr($e->intervalo_inicio, 3, 2) - $horaIni * 60;
                                    $if = (int) substr($e->intervalo_fim, 0, 2) * 60 + (int) substr($e->intervalo_fim, 3, 2) - $horaIni * 60;
                                @endphp
                                <div class="absolute inset-x-0 flex items-center justify-center bg-gray-100 text-[11px] text-gray-600" style="top: {{ $ii * $pxMin }}px; height: {{ ($if - $ii) * $pxMin }}px">Intervalo</div>
                            @endif
                        @endforeach
                        {{-- Bloqueios --}}
                        @foreach ($bloqueios->where('profissional_id', $prof->id) as $b)
                            @php
                                $bIni = ($b->inicio->lt($data) ? 0 : ($b->inicio->hour * 60 + $b->inicio->minute)) - $horaIni * 60;
                                $bFim = ($b->fim->gt($data->copy()->endOfDay()) ? 24 * 60 : ($b->fim->hour * 60 + $b->fim->minute)) - $horaIni * 60;
                                $bIni = max(0, $bIni); $bFim = min(($horaFim - $horaIni) * 60, $bFim);
                            @endphp
                            <div class="absolute inset-x-1 rounded bg-[repeating-linear-gradient(45deg,#e5e7eb,#e5e7eb_6px,#f3f4f6_6px,#f3f4f6_12px)] px-2 py-1 text-[11px] text-gray-700" style="top: {{ $bIni * $pxMin }}px; height: {{ max(16, ($bFim - $bIni) * $pxMin) }}px">
                                <x-icone nome="cadeado" class="inline w-3 h-3" /> {{ $b->motivo ?: 'Bloqueado' }}
                            </div>
                        @endforeach
                        {{-- Agendamentos --}}
                        @foreach ($doProf as $a)
                            @php
                                $top = ($a->inicio->hour * 60 + $a->inicio->minute - $horaIni * 60) * $pxMin;
                                $alt = max(28, $a->inicio->diffInMinutes($a->fim) * $pxMin);
                            @endphp
                            <a href="{{ route('admin.agendamentos.show', $a) }}"
                               class="absolute inset-x-1 overflow-hidden rounded border-l-[3px] px-2 py-1 text-xs ring-1 ring-black/5 transition-shadow hover:z-10 hover:shadow {{ $a->status->value === 'nao_compareceu' ? 'opacity-60' : '' }}"
                               style="top: {{ $top }}px; height: {{ $alt }}px; background: {{ $prof->cor_agenda }}22; border-color: {{ $a->status->corBorda() }}"
                               title="{{ $a->status->label() }}">
                                <span class="font-semibold">{{ $a->inicio->format('H:i') }}</span> {{ $a->cliente->nome }}<br>
                                <span class="text-gray-700">{{ $a->servico->nome }}</span>
                                <span class="sr-only">— {{ $a->status->label() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="p-6 text-sm text-gray-600">Nenhuma profissional ativa.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-600">
        @foreach (\App\Enums\StatusAgendamento::cases() as $s)
            @continue(in_array($s->value, ['cancelado', 'reagendado']))
            <span class="flex items-center gap-1"><span class="h-3 w-1 rounded-sm" style="background: {{ $s->corBorda() }}"></span>{{ $s->label() }}</span>
        @endforeach
    </div>
@else
    {{-- Visão semana / lista --}}
    <div>
        @for ($d = $de->copy(); $d->lte($ate); $d->addDay())
            @php $doDia = $agendamentos->filter(fn ($a) => $a->inicio->isSameDay($d)); @endphp
            <h2 @class(['mb-2 flex items-center gap-3 text-sm font-medium', 'mt-6' => ! $d->eq($de), 'text-luna-ink' => $d->isToday(), 'text-luna-brown' => ! $d->isToday()])>
                {{ ucfirst($d->translatedFormat('l, d/m')) }}@if ($d->isToday()) <span class="text-xs font-normal text-gray-500">hoje</span>@endif
                <span class="h-px flex-1 bg-luna-sand"></span>
                <span class="text-xs font-normal tabular-nums text-gray-500">{{ $doDia->count() ?: '' }}</span>
            </h2>
            @if ($doDia->isEmpty())
                <p class="px-1 text-sm text-gray-400">Sem agendamentos.</p>
            @else
                <ul class="divide-y divide-luna-sand/70 overflow-hidden rounded-lg border border-luna-sand bg-white text-sm">
                    @foreach ($doDia as $a)
                        <li>
                            <a href="{{ route('admin.agendamentos.show', $a) }}" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2.5 hover:bg-luna-cream/50">
                                <span class="w-24 tabular-nums"><span class="font-semibold">{{ $a->inicio->format('H:i') }}</span><span class="text-gray-500">–{{ $a->fim->format('H:i') }}</span></span>
                                <span class="flex w-28 items-center gap-1.5 text-gray-600"><span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $a->profissional->cor_agenda }}"></span><span class="truncate">{{ $a->profissional->nome_exibicao }}</span></span>
                                <span class="min-w-0 flex-1"><span class="font-medium">{{ $a->cliente->nome }}</span> <span class="text-gray-500">· {{ $a->servico->nome }}</span></span>
                                <x-badge-status :status="$a->status" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endfor
    </div>
@endif
@endsection
