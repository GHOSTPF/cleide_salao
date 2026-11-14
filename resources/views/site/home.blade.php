@extends('site.layout')

@section('conteudo')
    @include('site.partials.secoes.cabecalho')

    <main id="conteudo">
        @include('site.partials.secoes.hero')
        @include('site.partials.secoes.sobre')
        @include('site.partials.secoes.servicos')
        @include('site.partials.secoes.galeria')
        @include('site.partials.secoes.equipe')
        @include('site.partials.secoes.depoimentos')
        @include('site.partials.secoes.agendamento')
        @include('site.partials.secoes.contato')
    </main>

    @include('site.partials.secoes.rodape')
@endsection
