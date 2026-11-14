@extends('painel.layout')
@section('titulo', 'Atendimento ' . $ag->codigo)

@section('conteudo')
    <a href="{{ route('profissional.agenda') }}" class="mb-4 inline-flex items-center gap-1 text-sm text-gray-600 hover:text-luna-ink">
        <x-icone nome="seta-esq" class="w-4 h-4" /> Minha agenda
    </a>
    @include('painel.partials.agendamento-detalhe')
@endsection
