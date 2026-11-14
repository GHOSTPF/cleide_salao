@extends('painel.layout')
@section('titulo', 'Depoimentos')

@section('conteudo')
@include('painel.admin.site._abas')
<div class="grid max-w-5xl gap-6 lg:grid-cols-3">
    <x-card titulo="Novo depoimento">
        <form method="POST" action="{{ route('admin.site.depoimentos.store') }}" class="space-y-3">@csrf
            <x-input nome="nome_cliente" rotulo="Nome da cliente" required />
            <x-input nome="texto" rotulo="Depoimento" tipo="textarea" required />
            <x-select nome="nota" rotulo="Nota" :opcoes="[5 => '5 estrelas', 4 => '4 estrelas', 3 => '3 estrelas', 2 => '2 estrelas', 1 => '1 estrela']" />
            <x-input nome="ordem" rotulo="Ordem" tipo="number" min="0" valor="0" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ativo" value="1" checked class="rounded border-gray-300"> Exibir no site</label>
            <x-button tamanho="sm">Adicionar</x-button>
        </form>
    </x-card>
    <div class="space-y-4 lg:col-span-2">
        @foreach ($depoimentos as $d)
            <x-card padding="p-4">
                <form method="POST" action="{{ route('admin.site.depoimentos.update', $d) }}" class="grid gap-2 sm:grid-cols-4">@csrf @method('PUT')
                    <input name="nome_cliente" value="{{ $d->nome_cliente }}" class="campo sm:col-span-2" aria-label="Nome">
                    <select name="nota" class="campo" aria-label="Nota">@for ($i = 5; $i >= 1; $i--)<option value="{{ $i }}" @selected($d->nota === $i)>{{ $i }} ★</option>@endfor</select>
                    <input name="ordem" type="number" value="{{ $d->ordem }}" class="campo" aria-label="Ordem">
                    <textarea name="texto" class="campo sm:col-span-4" rows="2" aria-label="Texto">{{ $d->texto }}</textarea>
                    <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" name="ativo" value="1" @checked($d->ativo) class="rounded border-gray-300"> Exibir no site</label>
                    <div class="flex justify-end gap-2 sm:col-span-2">
                        <button class="btn-secundario btn-sm">Salvar</button>
                        <button form="del-dep-{{ $d->id }}" class="btn-secundario btn-sm" aria-label="Excluir"><x-icone nome="lixo" class="w-4 h-4" /></button>
                    </div>
                </form>
                <form id="del-dep-{{ $d->id }}" method="POST" action="{{ route('admin.site.depoimentos.destroy', $d) }}" onsubmit="return confirm('Excluir depoimento?')">@csrf @method('DELETE')</form>
            </x-card>
        @endforeach
    </div>
</div>
@endsection
