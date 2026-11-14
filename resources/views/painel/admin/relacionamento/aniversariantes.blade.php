@extends('painel.layout')
@section('titulo', 'Aniversariantes')

@section('conteudo')
@include('painel.admin.relacionamento._abas')
<div class="mb-4 flex gap-2 text-sm">
    @foreach (['dia' => 'Hoje', 'semana' => 'Próximos 7 dias', 'mes' => 'Este mês'] as $p => $r)
        <a href="{{ route('admin.relacionamento.aniversariantes', ['periodo' => $p]) }}" class="pilula {{ $periodo === $p ? 'pilula-ativa' : 'bg-white' }}">{{ $r }}</a>
    @endforeach
</div>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($clientes as $c)
        <div class="card flex items-center justify-between gap-3 p-5">
            <div>
                <a href="{{ route('admin.clientes.show', $c) }}" class="link font-medium">{{ $c->nome }}</a>
                <p class="text-sm text-gray-600"><x-icone nome="presente" class="inline w-4 h-4 text-luna-gold" /> {{ $c->data_nascimento->format('d/m') }}{{ $c->data_nascimento->isBirthday() ? ' · hoje!' : '' }}</p>
            </div>
            <a href="{{ \App\Support\WhatsAppLink::aniversario($c) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icone nome="whatsapp" class="w-4 h-4 text-green-700" /> Parabenizar</a>
        </div>
    @empty
        <x-card class="sm:col-span-2 xl:col-span-3"><p class="text-sm text-gray-600">Nenhuma aniversariante no período.</p></x-card>
    @endforelse
</div>
@endsection
