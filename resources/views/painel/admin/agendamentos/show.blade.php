@extends('painel.layout')
@section('titulo', 'Agendamento ' . $ag->codigo)

@section('conteudo')
    @include('painel.partials.agendamento-detalhe')
@endsection
