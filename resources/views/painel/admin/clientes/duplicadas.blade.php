@extends('painel.layout')
@section('titulo', 'Possíveis duplicadas')

@section('conteudo')
<p class="mb-4 text-sm text-gray-700">Clientes com nomes parecidos (≥ 60% de similaridade) e telefones diferentes — pode ser a mesma pessoa com dois números. Escolha qual cadastro fica como principal.</p>

<div class="space-y-4">
    @forelse ($pares as $par)
        <div class="card p-5">
            <p class="mb-3 legenda">Similaridade {{ $par->similaridade }}%</p>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ([[$par->a, $par->b], [$par->b, $par->a]] as [$principal, $outra])
                    <div class="rounded-lg border border-luna-sand p-4">
                        <a href="{{ route('admin.clientes.show', $principal) }}" class="link font-medium">{{ $principal->nome }}</a>
                        <p class="text-sm text-gray-700">{{ $principal->telefone_formatado }} · {{ $principal->total_visitas }} visitas · desde {{ $principal->created_at->format('d/m/Y') }}</p>
                        <form method="POST" action="{{ route('admin.clientes.mesclar') }}" class="mt-3" onsubmit="return confirm('Mesclar mantendo {{ e($principal->nome) }} como principal?')">
                            @csrf
                            <input type="hidden" name="principal_id" value="{{ $principal->id }}">
                            <input type="hidden" name="secundaria_id" value="{{ $outra->id }}">
                            <button class="btn-primario btn-sm"><x-icone nome="mesclar" class="w-4 h-4" /> Mesclar mantendo esta</button>
                        </form>
                    </div>
                @endforeach
            </div>
            <form method="POST" action="{{ route('admin.clientes.ignorar') }}" class="mt-3">
                @csrf
                <input type="hidden" name="a_id" value="{{ $par->a->id }}"><input type="hidden" name="b_id" value="{{ $par->b->id }}">
                <button class="btn-secundario btn-sm">Ignorar (são pessoas diferentes)</button>
            </form>
        </div>
    @empty
        <x-card><p class="text-sm text-gray-600">Nenhuma possível duplicada encontrada.</p></x-card>
    @endforelse
</div>
@endsection
