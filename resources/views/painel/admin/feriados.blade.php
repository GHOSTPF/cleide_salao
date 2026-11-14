@extends('painel.layout')
@section('titulo', 'Feriados e fechamentos')

@section('conteudo')
<div class="grid max-w-5xl gap-6 lg:grid-cols-2">
    <x-card titulo="Fechar o salão">
        <p class="mb-4 text-sm text-gray-700">Bloqueia a agenda de todas as profissionais (feriado, recesso, evento).</p>
        @include('painel.partials.form-bloqueio', ['acao' => route('admin.feriados.store')])
    </x-card>
    <x-card titulo="Fechamentos cadastrados">
        @include('painel.partials.lista-bloqueios', ['bloqueios' => $bloqueios, 'rotaExcluir' => 'admin.feriados.destroy'])
    </x-card>
</div>
@endsection
