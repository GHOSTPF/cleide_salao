@extends('painel.layout')
@section('titulo', 'Financeiro')

@php use App\Support\Dinheiro; $qs = ['de' => $de->toDateString(), 'ate' => $ate->toDateString()]; @endphp

@section('conteudo')
<div class="mb-6 flex flex-wrap items-end justify-between gap-3 border-b border-luna-sand pb-5">
    @include('painel.admin.financeiro._periodo', ['extras' => ['aba' => $aba]])
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.financeiro.comissoes', $qs) }}" class="btn-secundario btn-sm">Comissões</a>
        <a href="{{ route('admin.financeiro.exportar', $qs + ['formato' => 'xlsx']) }}" class="btn-secundario btn-sm"><x-icone nome="download" class="w-4 h-4" /> Excel</a>
        <a href="{{ route('admin.financeiro.exportar', $qs + ['formato' => 'pdf']) }}" class="btn-secundario btn-sm"><x-icone nome="download" class="w-4 h-4" /> PDF</a>
    </div>
</div>

<x-indicadores :itens="[
    ['rotulo' => 'Entradas', 'valor' => Dinheiro::formatar($resumo['entradas']), 'cor' => 'text-emerald-800', 'sub' => $resumo['atendimentos'] . ' atendimentos · ticket ' . Dinheiro::formatar($resumo['ticket_medio'])],
    ['rotulo' => 'Saídas', 'valor' => Dinheiro::formatar($resumo['saidas']), 'cor' => 'text-red-800'],
    ['rotulo' => 'Saldo', 'valor' => Dinheiro::formatar($resumo['saldo']), 'cor' => $resumo['saldo'] < 0 ? 'text-red-800' : ''],
    ['rotulo' => 'Comissões geradas', 'valor' => Dinheiro::formatar($resumo['comissoes']), 'sub' => 'saldo após comissões: ' . Dinheiro::formatar($resumo['saldo'] - $resumo['comissoes'])],
]" />

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <x-card titulo="Por forma de pagamento">
        <ul class="space-y-2 text-sm">
            @foreach ($resumo['por_forma'] as $f => $dados)
                <li class="flex justify-between"><a class="link" href="{{ route('admin.financeiro.index', $qs + ['forma' => $f]) }}">{{ $dados['label'] }}</a><span>{{ $dados['qtd'] }} · {{ Dinheiro::formatar($dados['total']) }}</span></li>
            @endforeach
        </ul>
    </x-card>
    <x-card titulo="Comissões por profissional">
        <ul class="space-y-2 text-sm">
            @forelse ($comissoes as $c)
                <li class="flex flex-wrap justify-between gap-2"><span>{{ $c->nome }} <span class="text-gray-600">({{ $c->atendimentos }})</span></span><span>{{ Dinheiro::formatar($c->total) }} <span class="text-xs text-amber-800">pendente {{ Dinheiro::formatar($c->pendente ?? 0) }}</span></span></li>
            @empty
                <li class="text-gray-600">Sem comissões no período.</li>
            @endforelse
        </ul>
    </x-card>
</div>

<nav class="abas mb-4 mt-8" aria-label="Lançamentos">
    <a href="{{ route('admin.financeiro.index', $qs + ['aba' => 'entradas']) }}" @if ($aba === 'entradas') aria-current="page" @endif>Entradas</a>
    <a href="{{ route('admin.financeiro.index', $qs + ['aba' => 'despesas']) }}" @if ($aba === 'despesas') aria-current="page" @endif>Despesas</a>
</nav>

@if ($aba === 'entradas')
    @if ($forma)<p class="mb-2 text-sm">Filtrando por <strong>{{ \App\Enums\FormaPagamento::from($forma)->label() }}</strong> · <a class="link" href="{{ route('admin.financeiro.index', $qs) }}">limpar</a></p>@endif
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead><tr><th>Data</th><th>Cliente</th><th>Serviço</th><th>Profissional</th><th>Forma</th><th class="text-right">Valor</th></tr></thead>
                <tbody class="divide-y divide-luna-sand">
                    @forelse ($entradas as $p)
                        <tr>
                            <td class="whitespace-nowrap"><a class="link" href="{{ route('admin.agendamentos.show', $p->agendamento_id) }}">{{ $p->pago_em->format('d/m/Y H:i') }}</a></td>
                            <td>{{ $p->agendamento->cliente->nome }}</td>
                            <td>{{ $p->agendamento->servico->nome }}</td>
                            <td>{{ $p->agendamento->profissional->nome_exibicao }}</td>
                            <td>{{ $p->forma->label() }}</td>
                            <td class="whitespace-nowrap text-right">{{ Dinheiro::formatar($p->valor) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-600">Nenhuma entrada no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
    <div class="mt-4">{{ $entradas->links() }}</div>
@else
    <a href="{{ route('admin.financeiro.despesas.create') }}" class="btn-primario mb-4"><x-icone nome="mais" class="w-4 h-4" /> Lançar despesa</a>
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead><tr><th>Data</th><th>Descrição</th><th>Categoria</th><th>Situação</th><th class="text-right">Valor</th><th></th></tr></thead>
                <tbody class="divide-y divide-luna-sand">
                    @forelse ($despesas as $d)
                        <tr>
                            <td class="whitespace-nowrap">{{ $d->data->format('d/m/Y') }}</td>
                            <td>{{ $d->descricao }}</td>
                            <td>{{ \App\Models\Despesa::CATEGORIAS[$d->categoria] }}</td>
                            <td class="{{ $d->pago ? 'text-emerald-800' : 'text-amber-800' }}">{{ $d->pago ? 'Pago' : 'A pagar' }}</td>
                            <td class="whitespace-nowrap text-right">{{ Dinheiro::formatar($d->valor) }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.financeiro.despesas.edit', $d) }}" class="btn-secundario btn-sm" aria-label="Editar"><x-icone nome="editar" class="w-4 h-4" /></a>
                                <form method="POST" action="{{ route('admin.financeiro.despesas.destroy', $d) }}" class="inline" onsubmit="return confirm('Excluir despesa?')">@csrf @method('DELETE')<button class="btn-secundario btn-sm" aria-label="Excluir"><x-icone nome="lixo" class="w-4 h-4" /></button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-gray-600">Nenhuma despesa no período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
    <div class="mt-4">{{ $despesas->links() }}</div>
@endif
@endsection
