@extends('painel.layout')
@section('titulo', $despesa->exists ? 'Editar despesa' : 'Lançar despesa')

@section('conteudo')
<x-card class="max-w-xl">
    <form method="POST" action="{{ $despesa->exists ? route('admin.financeiro.despesas.update', $despesa) : route('admin.financeiro.despesas.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf @if ($despesa->exists) @method('PUT') @endif
        <div class="sm:col-span-2"><x-input nome="descricao" rotulo="Descrição" :valor="$despesa->descricao" required /></div>
        <x-select nome="categoria" rotulo="Categoria" :opcoes="\App\Models\Despesa::CATEGORIAS" :valor="$despesa->categoria" />
        <x-input nome="valor" rotulo="Valor (R$)" inputmode="decimal" :valor="$despesa->valor !== null ? number_format($despesa->valor, 2, ',', '') : ''" required />
        <x-input nome="data" rotulo="Data" tipo="date" :valor="$despesa->data?->toDateString()" required />
        <label class="flex items-center gap-2 self-end pb-3 text-sm"><input type="checkbox" name="pago" value="1" @checked(old('pago', $despesa->pago)) class="rounded border-gray-300"> Pago</label>
        <div class="sm:col-span-2"><x-input nome="observacao" rotulo="Observação" tipo="textarea" :valor="$despesa->observacao" /></div>
        <div class="flex gap-2 sm:col-span-2"><a href="{{ route('admin.financeiro.index', ['aba' => 'despesas']) }}" class="btn-secundario">Voltar</a><x-button>Salvar</x-button></div>
    </form>
</x-card>
@endsection
