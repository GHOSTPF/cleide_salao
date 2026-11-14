@extends('painel.layout')
@section('titulo', 'Comissões')

@php use App\Support\Dinheiro; @endphp

@section('conteudo')
<x-card padding="p-4 sm:p-5" class="mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-2">
        <div><label class="rotulo" for="de">Concluídos de</label><input type="date" id="de" name="de" value="{{ $de->toDateString() }}" class="campo"></div>
        <div><label class="rotulo" for="ate">Até</label><input type="date" id="ate" name="ate" value="{{ $ate->toDateString() }}" class="campo"></div>
        <x-select nome="profissional_id" rotulo="Profissional" vazio="Todas" :opcoes="$profissionais" :valor="$profissionalId" />
        <x-select nome="status" rotulo="Situação" :opcoes="['pendente' => 'Pendentes', 'paga' => 'Pagas', 'todas' => 'Todas']" :valor="$status" />
        <x-button icone="busca">Filtrar</x-button>
        <a href="{{ route('admin.financeiro.index', ['de' => $de->toDateString(), 'ate' => $ate->toDateString()]) }}" class="btn-secundario">← Financeiro</a>
    </form>
</x-card>

<form method="POST" action="{{ route('admin.financeiro.comissoes.pagar') }}" x-data="{ todos: false }">
    @csrf
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead><tr>
                    <th><input type="checkbox" x-model="todos" @change="$root.querySelectorAll('.sel').forEach(function (c) { c.checked = todos; })" class="rounded border-gray-300" aria-label="Selecionar todas"></th>
                    <th>Concluído</th><th>Profissional</th><th>Cliente / Serviço</th><th class="text-right">Base</th><th class="text-right">%</th><th class="text-right">Comissão</th><th>Situação</th>
                </tr></thead>
                <tbody class="divide-y divide-luna-sand">
                    @forelse ($comissoes as $c)
                        <tr>
                            <td>@if ($c->status === 'pendente')<input type="checkbox" name="ids[]" value="{{ $c->id }}" class="sel rounded border-gray-300" aria-label="Selecionar">@endif</td>
                            <td class="whitespace-nowrap">{{ $c->agendamento->concluido_em?->format('d/m/Y') }}</td>
                            <td>{{ $c->profissional->nome_exibicao }}</td>
                            <td>{{ $c->agendamento->cliente->nome }}<br><span class="text-xs text-gray-600">{{ $c->agendamento->servico->nome }}</span></td>
                            <td class="text-right whitespace-nowrap">{{ Dinheiro::formatar($c->base_calculo) }}</td>
                            <td class="text-right">{{ rtrim(rtrim(number_format($c->percentual, 2, ',', ''), '0'), ',') }}</td>
                            <td class="text-right whitespace-nowrap font-medium">{{ Dinheiro::formatar($c->valor) }}</td>
                            <td class="text-xs {{ $c->status === 'paga' ? 'text-emerald-800' : 'text-amber-800' }}">{{ $c->status === 'paga' ? 'Paga em ' . $c->pago_em?->format('d/m/Y') : 'Pendente' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-6 text-center text-gray-600">Nenhuma comissão.</td></tr>
                    @endforelse
                </tbody>
                @if ($comissoes->isNotEmpty())
                    <tfoot><tr class="font-medium"><td colspan="6" class="text-right">Total</td><td class="text-right whitespace-nowrap">{{ Dinheiro::formatar($comissoes->sum('valor')) }}</td><td></td></tr></tfoot>
                @endif
            </table>
        </div>
    </x-card>
    @if ($comissoes->where('status', 'pendente')->isNotEmpty())
        <x-button class="mt-4" icone="check">Marcar selecionadas como pagas</x-button>
    @endif
</form>
@endsection
