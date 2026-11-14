@extends('painel.layout')
@section('titulo', 'Profissionais')

@section('conteudo')
<a href="{{ route('admin.profissionais.create') }}" class="btn-primario mb-5"><x-icone nome="mais" class="w-4 h-4" /> Nova profissional</a>

<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($profissionais as $p)
        <div class="card flex items-start gap-4 p-5 {{ $p->ativo ? '' : 'opacity-60' }}">
            @if ($p->foto_url)
                <img src="{{ $p->foto_url }}" alt="" class="h-14 w-14 rounded-full object-cover">
            @else
                <span class="flex h-14 w-14 items-center justify-center rounded-full font-serif text-2xl text-white" style="background: {{ $p->cor_agenda }}">{{ mb_substr($p->nome_exibicao, 0, 1) }}</span>
            @endif
            <div class="flex-1">
                <p class="font-medium">{{ $p->nome }} @if ($p->apelido)<span class="text-gray-600">({{ $p->apelido }})</span>@endif</p>
                <p class="text-sm text-gray-700">{{ $p->servicos_count }} serviço(s) · comissão {{ rtrim(rtrim(number_format($p->percentual_comissao, 2, ',', ''), '0'), ',') }}%</p>
                <p class="text-xs text-gray-600">{{ $p->user ? 'Acesso: ' . $p->user->email . ($p->user->ativo ? '' : ' (inativo)') : 'Sem acesso ao painel' }}</p>
                <a href="{{ route('admin.profissionais.edit', $p) }}" class="btn-secundario btn-sm mt-3"><x-icone nome="editar" class="w-4 h-4" /> Gerenciar</a>
            </div>
        </div>
    @endforeach
</div>
@endsection
