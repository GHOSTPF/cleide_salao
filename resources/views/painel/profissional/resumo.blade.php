@extends('painel.layout')
@section('titulo', 'Meu resumo')

@php use App\Support\Dinheiro; @endphp

@section('conteudo')
<div class="mb-6 flex flex-wrap items-end justify-between gap-4">
    <p class="font-serif text-2xl">{{ ucfirst($mes->translatedFormat('F \d\e Y')) }}</p>
    <form method="GET" class="flex items-center gap-2">
        <label class="sr-only" for="mes">Mês</label>
        <input type="month" id="mes" name="mes" value="{{ $mes->format('Y-m') }}" class="campo w-auto py-1.5 text-sm">
        <x-button variante="secundario" tamanho="sm">Ver mês</x-button>
    </form>
</div>

{{-- Números do mês numa faixa só, separados por linhas --}}
<dl class="grid grid-cols-2 overflow-hidden rounded-lg border border-luna-sand bg-white lg:grid-cols-4">
    <div class="border-b border-r border-luna-sand p-4 sm:p-5 lg:border-b-0">
        <dt class="legenda">Atendimentos</dt>
        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ $concluidos->count() }}</dd>
        <dd class="mt-0.5 text-xs text-gray-500">{{ $faltas === 0 ? 'nenhuma falta' : $faltas . ($faltas === 1 ? ' falta' : ' faltas') }}</dd>
    </div>
    <div class="border-b border-luna-sand p-4 sm:p-5 lg:border-b-0 lg:border-r">
        <dt class="legenda">Comissão do mês</dt>
        <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ Dinheiro::formatar($comissaoMes) }}</dd>
    </div>
    <div class="border-r border-luna-sand p-4 sm:p-5">
        <dt class="legenda">Recebido no mês</dt>
        <dd class="mt-1 text-2xl font-semibold tabular-nums text-emerald-800">{{ Dinheiro::formatar($pagasMes) }}</dd>
    </div>
    <div class="bg-amber-50/50 p-4 sm:p-5">
        <dt class="legenda">A receber (total)</dt>
        <dd class="mt-1 text-2xl font-semibold tabular-nums text-amber-800">{{ Dinheiro::formatar($pendenteTotal) }}</dd>
        <dd class="mt-0.5 text-xs text-gray-500">somando todos os meses</dd>
    </div>
</dl>

<h2 class="mb-3 mt-8 text-sm font-semibold">Atendimentos concluídos</h2>

@if ($concluidos->isEmpty())
    <div class="rounded-lg border border-dashed border-luna-nude px-6 py-10 text-center text-sm text-gray-600">Nenhum atendimento concluído neste mês.</div>
@else
    {{-- Celular: lista --}}
    <ul class="overflow-hidden rounded-lg border border-luna-sand bg-white sm:hidden">
        @foreach ($concluidos as $a)
            @php $paga = $a->comissao?->status === 'paga'; @endphp
            <li class="border-b border-luna-sand/70 last:border-0">
                <a href="{{ route('profissional.agendamentos.show', $a) }}" class="flex items-center justify-between gap-3 px-4 py-3">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ $a->cliente->nome }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $a->inicio->format('d/m') }} · {{ $a->servico->nome }}</p>
                    </div>
                    <div class="shrink-0 text-right tabular-nums">
                        <p class="text-sm font-medium">{{ Dinheiro::formatar($a->comissao?->valor ?? 0) }}</p>
                        <p class="text-xs {{ $paga ? 'text-emerald-700' : 'text-amber-700' }}">{{ $paga ? 'paga' : 'a receber' }}</p>
                    </div>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Telas maiores: tabela --}}
    <div class="hidden overflow-x-auto rounded-lg border border-luna-sand bg-white sm:block">
        <table class="tabela">
            <thead><tr><th>Data</th><th>Cliente</th><th>Serviço</th><th class="text-right">Valor</th><th class="text-right">Comissão</th><th>Situação</th></tr></thead>
            <tbody class="divide-y divide-luna-sand/70">
                @foreach ($concluidos as $a)
                    @php $paga = $a->comissao?->status === 'paga'; @endphp
                    <tr>
                        <td class="whitespace-nowrap tabular-nums"><a href="{{ route('profissional.agendamentos.show', $a) }}" class="link">{{ $a->inicio->format('d/m H:i') }}</a></td>
                        <td>{{ $a->cliente->nome }}</td>
                        <td class="text-gray-600">{{ $a->servico->nome }}</td>
                        <td class="whitespace-nowrap text-right tabular-nums">{{ Dinheiro::formatar($a->valor_cobrado) }}</td>
                        <td class="whitespace-nowrap text-right font-medium tabular-nums">{{ Dinheiro::formatar($a->comissao?->valor ?? 0) }}</td>
                        <td class="text-xs {{ $paga ? 'text-emerald-700' : 'text-amber-700' }}">{{ $paga ? 'Paga' : 'A receber' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
