@extends('painel.layout')
@section('titulo', $cliente->nome)

@section('conteudo')
<a href="{{ route('profissional.clientes.index') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-luna-ink">
    <x-icone nome="seta-esq" class="w-4 h-4" /> Minhas clientes
</a>

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <p class="font-serif text-3xl leading-tight">{{ $cliente->nome }}</p>
        <p class="text-sm tabular-nums text-gray-600">{{ $cliente->telefone_formatado }}</p>
    </div>
    <a href="{{ \App\Support\WhatsAppLink::paraCliente($cliente) }}" target="_blank" rel="noopener" class="btn-whatsapp btn-sm"><x-icone nome="whatsapp" class="w-4 h-4" /> Chamar no WhatsApp</a>
</div>

@if ($cliente->observacoes)
    <div class="mb-6 flex gap-2 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        <x-icone nome="alerta" class="mt-px w-4 h-4 shrink-0 text-amber-700" />
        <p class="whitespace-pre-line">{{ $cliente->observacoes }}</p>
    </div>
@endif

<dl class="mb-8 grid grid-cols-2 gap-x-6 gap-y-4 border-y border-luna-sand py-4 text-sm md:grid-cols-4">
    @foreach ([
        ['Atendimentos comigo', $agendamentos->where('status.value', 'concluido')->count()],
        ['Última vez comigo', $agendamentos->firstWhere('status.value', 'concluido')?->inicio->format('d/m/Y') ?? '—'],
        ['Serviço que mais faz', $indicadores['servico_mais_feito']?->nome ?? '—'],
        ['Retorno sugerido', $indicadores['proximo_retorno'] ? $indicadores['proximo_retorno']['data']->format('d/m/Y') : '—'],
    ] as [$r, $v])
        <div><dt class="legenda">{{ $r }}</dt><dd class="mt-0.5 font-medium">{{ $v }}</dd></div>
    @endforeach
</dl>

<div class="grid gap-8 lg:grid-cols-5">
    <section class="lg:col-span-3">
        <h2 class="mb-3 text-sm font-semibold">Histórico comigo</h2>
        @if ($agendamentos->isEmpty())
            <p class="text-sm text-gray-600">Nenhum atendimento ainda.</p>
        @else
            <ul class="overflow-hidden rounded-lg border border-luna-sand bg-white text-sm">
                @foreach ($agendamentos as $a)
                    <li class="border-b border-luna-sand/70 last:border-0">
                        <a href="{{ route('profissional.agendamentos.show', $a) }}" class="flex items-center gap-3 px-4 py-2.5 hover:bg-luna-cream/50">
                            <span class="w-24 shrink-0 tabular-nums text-gray-600">{{ $a->inicio->format('d/m/Y') }}</span>
                            <span class="min-w-0 flex-1 truncate">{{ $a->servico->nome }}</span>
                            <x-badge-status :status="$a->status" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="lg:col-span-2">
        <h2 class="mb-3 text-sm font-semibold">Ficha técnica</h2>
        @if ($anotacoes->isEmpty())
            <p class="text-sm text-gray-600">Nenhuma anotação. Elas aparecem aqui quando você anota algo num atendimento.</p>
        @else
            <ol class="space-y-4 text-sm">
                @foreach ($anotacoes as $n)
                    <li class="border-l-2 border-luna-nude pl-3">
                        <p class="whitespace-pre-line">{{ $n->anotacao }}</p>
                        <p class="mt-1 text-xs text-gray-500">{{ $n->created_at->format('d/m/Y') }}{{ $n->profissional ? ' · ' . $n->profissional->nome_exibicao : '' }}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>
</div>
@endsection
