@props(['status'])
@php $s = $status instanceof \App\Enums\StatusAgendamento ? $status : \App\Enums\StatusAgendamento::from($status); @endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded px-2 py-0.5 text-xs font-medium whitespace-nowrap ' . $s->classes()]) }}>
    <span class="h-1.5 w-1.5 rounded-full" style="background: {{ $s->corBorda() }}" aria-hidden="true"></span>{{ $s->label() }}
</span>
