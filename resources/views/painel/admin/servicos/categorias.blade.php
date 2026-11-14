@extends('painel.layout')
@section('titulo', 'Categorias de serviço')

@section('conteudo')
<div class="grid max-w-4xl gap-6 md:grid-cols-3">
    <x-card titulo="Nova categoria">
        <form method="POST" action="{{ route('admin.categorias.store') }}" class="space-y-3">@csrf
            <x-input nome="nome" rotulo="Nome" required />
            <x-input nome="ordem" rotulo="Ordem" tipo="number" min="0" valor="0" />
            <x-button tamanho="sm">Criar</x-button>
        </form>
    </x-card>
    <x-card titulo="Categorias" class="md:col-span-2">
        <ul class="divide-y divide-luna-sand">
            @foreach ($categorias as $c)
                <li class="flex flex-wrap items-end gap-2 py-3">
                    <form method="POST" action="{{ route('admin.categorias.update', $c) }}" class="flex flex-1 flex-wrap items-end gap-2">@csrf @method('PUT')
                        <input name="nome" value="{{ $c->nome }}" class="campo flex-1 min-w-[10rem]" aria-label="Nome">
                        <input name="ordem" type="number" value="{{ $c->ordem }}" class="campo w-20" aria-label="Ordem">
                        <button class="btn-secundario btn-sm">Salvar</button>
                    </form>
                    <span class="text-xs text-gray-600">{{ $c->servicos_count }} serviço(s)</span>
                    <form method="POST" action="{{ route('admin.categorias.destroy', $c) }}" onsubmit="return confirm('Excluir categoria?')">@csrf @method('DELETE')
                        <button class="btn-secundario btn-sm" aria-label="Excluir"><x-icone nome="lixo" class="w-4 h-4" /></button>
                    </form>
                </li>
            @endforeach
        </ul>
    </x-card>
</div>
<p class="mt-4"><a href="{{ route('admin.servicos.index') }}" class="link">← Voltar para serviços</a></p>
@endsection
