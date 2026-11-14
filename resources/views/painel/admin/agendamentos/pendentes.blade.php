@extends('painel.layout')
@section('titulo', 'Pendentes')

@section('conteudo')
<p class="mb-4 text-sm text-gray-700">Agendamentos aguardando confirmação do salão. Confirme ou chame a cliente no WhatsApp com a mensagem pronta.</p>

<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
    @forelse ($pendentes as $a)
        <div class="card p-5 {{ $a->inicio->isPast() ? 'ring-2 ring-red-200' : '' }}">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-serif text-xl">{{ $a->cliente->nome }}</p>
                    <p class="text-sm text-gray-600">{{ $a->cliente->telefone_formatado }}</p>
                </div>
                <span class="text-xs text-gray-600">{{ $a->codigo }}</span>
            </div>
            <dl class="mt-3 space-y-1 text-sm">
                <div><dt class="inline text-gray-600">Quando:</dt> <dd class="inline font-medium">{{ \App\Support\WhatsAppLink::dataExtenso($a->inicio) }} às {{ $a->inicio->format('H:i') }}</dd></div>
                <div><dt class="inline text-gray-600">Serviço:</dt> <dd class="inline">{{ $a->servico->nome }} com {{ $a->profissional->nome_exibicao }}</dd></div>
                <div><dt class="inline text-gray-600">Origem:</dt> <dd class="inline">{{ $a->origem->label() }}
                    @if ($a->origem->value === 'site')
                        · {{ $a->whatsapp_enviado_em ? 'enviou WhatsApp' : 'não enviou WhatsApp' }}
                    @endif
                </dd></div>
                @if ($a->observacoes_cliente)<div class="rounded-md bg-luna-cream px-3 py-2 text-xs">“{{ $a->observacoes_cliente }}”</div>@endif
                @if ($a->nome_divergente)<div class="text-xs text-amber-800">Nome informado: “{{ $a->nome_informado }}”</div>@endif
            </dl>
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.agendamentos.confirmar', $a) }}">@csrf<x-button tamanho="sm" icone="check">Confirmar</x-button></form>
                <a href="{{ \App\Support\WhatsAppLink::confirmarComCliente($a) }}" target="_blank" rel="noopener" class="btn-whatsapp btn-sm"><x-icone nome="whatsapp" class="w-4 h-4" /> Chamar no WhatsApp</a>
                <a href="{{ route('admin.agendamentos.show', $a) }}" class="btn-secundario btn-sm">Detalhes</a>
            </div>
        </div>
    @empty
        <x-card class="md:col-span-2 xl:col-span-3"><p class="text-sm text-gray-600">Nenhum agendamento pendente. 💛</p></x-card>
    @endforelse
</div>
@endsection
