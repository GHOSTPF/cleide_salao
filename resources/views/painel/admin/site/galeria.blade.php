@extends('painel.layout')
@section('titulo', 'Galeria do site')

@section('conteudo')
@include('painel.admin.site._abas')
<x-card titulo="Enviar fotos" class="mb-6 max-w-3xl">
    <form method="POST" action="{{ route('admin.site.galeria.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2">
        @csrf
        <div class="sm:col-span-2"><label class="rotulo" for="fotos">Fotos (JPG, PNG ou WEBP até 5 MB cada)</label><input type="file" id="fotos" name="fotos[]" accept="image/jpeg,image/png,image/webp" multiple required class="block text-sm"></div>
        <x-select nome="categoria" rotulo="Categoria" :opcoes="\App\Models\GaleriaFoto::CATEGORIAS" />
        <x-input nome="titulo" rotulo="Legenda (opcional)" />
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="destaque" value="1" class="rounded border-gray-300"> Destaque (aparece primeiro)</label>
        <div class="sm:col-span-2"><x-button icone="imagem">Enviar</x-button></div>
    </form>
</x-card>

<div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
    @foreach ($fotos as $f)
        <div class="card overflow-hidden p-0">
            <img src="{{ $f->url }}" alt="{{ $f->titulo }}" class="aspect-[4/5] w-full object-cover" loading="lazy">
            <form method="POST" action="{{ route('admin.site.galeria.update', $f) }}" class="space-y-2 p-3 text-xs">
                @csrf @method('PUT')
                <input name="titulo" value="{{ $f->titulo }}" class="campo py-1 text-xs" placeholder="Legenda" aria-label="Legenda">
                <select name="categoria" class="campo py-1 text-xs" aria-label="Categoria">
                    @foreach (\App\Models\GaleriaFoto::CATEGORIAS as $k => $v)<option value="{{ $k }}" @selected($f->categoria === $k)>{{ $v }}</option>@endforeach
                </select>
                <div class="flex items-center gap-2">
                    <input name="ordem" type="number" value="{{ $f->ordem }}" class="campo w-16 py-1 text-xs" aria-label="Ordem">
                    <label class="flex items-center gap-1"><input type="checkbox" name="destaque" value="1" @checked($f->destaque) class="rounded border-gray-300"> Destaque</label>
                </div>
                <div class="flex gap-1">
                    <button class="btn-secundario btn-sm flex-1">Salvar</button>
                    <button form="del-{{ $f->id }}" class="btn-secundario btn-sm" aria-label="Remover"><x-icone nome="lixo" class="w-4 h-4" /></button>
                </div>
            </form>
            <form id="del-{{ $f->id }}" method="POST" action="{{ route('admin.site.galeria.destroy', $f) }}" onsubmit="return confirm('Remover foto?')">@csrf @method('DELETE')</form>
        </div>
    @endforeach
</div>
@endsection
