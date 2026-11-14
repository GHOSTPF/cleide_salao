@extends('painel.layout')
@section('titulo', 'Minha agenda')

@php
    use App\Enums\StatusAgendamento as S;
    $ehLista = ! ($agendamentos instanceof \Illuminate\Contracts\Pagination\Paginator);
    $proximo = $aba === 'hoje'
        ? $agendamentos->first(fn ($a) => $a->fim->isFuture() && in_array($a->status, [S::Pendente, S::Confirmado], true))
        : null;
@endphp

@section('conteudo')
<nav class="abas mb-6" aria-label="Período">
    @foreach (['hoje' => 'Hoje', 'proximos' => 'Próximos dias', 'historico' => 'Histórico'] as $k => $r)
        <a href="{{ route('profissional.agenda', ['aba' => $k]) }}" @if ($aba === $k) aria-current="page" @endif>{{ $r }}</a>
    @endforeach
</nav>

@if ($aba === 'hoje')
    <div class="mb-5 flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
        <p class="font-serif text-2xl">{{ ucfirst(today()->translatedFormat('l, d \d\e F')) }}</p>
        <p class="text-sm text-gray-600">
            @if ($agendamentos->isEmpty())
                Agenda livre hoje.
            @else
                {{ $agendamentos->count() }} {{ $agendamentos->count() === 1 ? 'atendimento' : 'atendimentos' }}
                @if ($proximo) · próximo às <span class="font-medium text-luna-ink">{{ $proximo->inicio->format('H:i') }}</span> @endif
            @endif
        </p>
    </div>
@endif

@php $diaAnterior = null; @endphp
@forelse ($agendamentos as $a)
    @if ($aba !== 'hoje' && $diaAnterior !== $a->inicio->toDateString())
        @if ($diaAnterior !== null)</ul>@endif
        <h2 class="mb-2 mt-6 flex items-center gap-3 text-sm font-medium text-luna-brown first:mt-0">
            {{ ucfirst($a->inicio->translatedFormat('l, d/m')) }}
            <span class="h-px flex-1 bg-luna-sand"></span>
        </h2>
        <ul class="overflow-hidden rounded-lg border border-luna-sand bg-white">
        @php $diaAnterior = $a->inicio->toDateString(); @endphp
    @elseif ($loop->first)
        <ul class="overflow-hidden rounded-lg border border-luna-sand bg-white">
    @endif

    @php $passou = $a->fim->isPast() && $aba === 'hoje'; @endphp
    <li class="border-b border-luna-sand/70 last:border-0">
        <a href="{{ route('profissional.agendamentos.show', $a) }}"
           class="group flex items-stretch gap-4 px-4 py-3.5 transition-colors hover:bg-luna-cream/50 {{ $passou ? 'opacity-60' : '' }} {{ $proximo?->is($a) ? 'bg-luna-cream/40' : '' }}">
            <div class="w-14 shrink-0 pt-0.5 tabular-nums">
                <p class="text-base font-semibold leading-none">{{ $a->inicio->format('H:i') }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $a->fim->format('H:i') }}</p>
            </div>
            <span class="w-0.5 shrink-0 rounded-full" style="background: {{ $a->status->corBorda() }}" aria-hidden="true"></span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-medium group-hover:underline group-hover:decoration-luna-nude group-hover:underline-offset-2">{{ $a->cliente->nome }}</p>
                <p class="truncate text-sm text-gray-600">{{ $a->servico->nome }}</p>
                @if ($a->observacoes_cliente)
                    <p class="mt-1.5 line-clamp-2 border-l-2 border-luna-nude pl-2 text-xs italic text-luna-brown">{{ \Illuminate\Support\Str::limit($a->observacoes_cliente, 120) }}</p>
                @endif
            </div>
            <div class="flex shrink-0 flex-col items-end justify-between gap-2">
                <x-badge-status :status="$a->status" />
                <x-icone nome="seta-dir" class="w-4 h-4 text-gray-300 group-hover:text-luna-brown" />
            </div>
        </a>
    </li>

    @if ($loop->last)</ul>@endif
@empty
    <div class="rounded-lg border border-dashed border-luna-nude px-6 py-10 text-center">
        <x-icone nome="calendario" class="mx-auto mb-2 w-6 h-6 text-luna-nude" />
        <p class="text-sm text-gray-600">
            @switch($aba)
                @case('hoje') Nenhum atendimento marcado para hoje. @break
                @case('proximos') Nada agendado para os próximos dias. @break
                @default Ainda não há atendimentos anteriores.
            @endswitch
        </p>
    </div>
@endforelse

@unless ($ehLista)
    <div class="mt-5">{{ $agendamentos->links() }}</div>
@endunless
@endsection
