@extends('painel.layout')
@section('titulo', 'Retornos')

@section('conteudo')
@include('painel.admin.relacionamento._abas')
<p class="mb-4 text-sm text-gray-700">Clientes cujo prazo de retorno do serviço já venceu ou vence nos próximos 7 dias, sem agendamento futuro.</p>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="tabela">
            <thead><tr><th>Cliente</th><th>Serviço</th><th>Último</th><th>Retorno sugerido</th><th></th></tr></thead>
            <tbody class="divide-y divide-luna-sand">
                @forelse ($retornos as $r)
                    <tr>
                        <td><a href="{{ route('admin.clientes.show', $r->cliente) }}" class="link">{{ $r->cliente->nome }}</a></td>
                        <td>{{ $r->servico->nome }} <span class="text-xs text-gray-600">(a cada {{ $r->servico->retorno_sugerido_dias }} dias)</span></td>
                        <td class="whitespace-nowrap">{{ $r->ultimo_atendimento->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap {{ $r->retorno_em->isPast() ? 'text-red-700 font-medium' : '' }}">{{ $r->retorno_em->format('d/m/Y') }}</td>
                        <td class="text-right"><a href="{{ \App\Support\WhatsAppLink::retorno($r->cliente, $r->servico->nome) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icone nome="whatsapp" class="w-4 h-4 text-green-700" /> Chamar no WhatsApp</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-600">Nenhuma cliente no prazo de retorno.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
@endsection
