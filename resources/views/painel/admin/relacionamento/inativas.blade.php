@extends('painel.layout')
@section('titulo', 'Clientes inativas')

@section('conteudo')
@include('painel.admin.relacionamento._abas')
<form method="GET" class="mb-4 flex flex-wrap items-end gap-2 text-sm">
    <div><label class="rotulo" for="dias">Sem vir há mais de (dias)</label><input type="number" min="15" id="dias" name="dias" value="{{ $dias }}" class="campo w-32"></div>
    <x-button tamanho="sm">Atualizar</x-button>
</form>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="tabela">
            <thead><tr><th>Cliente</th><th>Última visita</th><th class="text-right">Visitas</th><th class="text-right">Total gasto</th><th></th></tr></thead>
            <tbody class="divide-y divide-luna-sand">
                @forelse ($clientes as $c)
                    <tr>
                        <td><a href="{{ route('admin.clientes.show', $c) }}" class="link">{{ $c->nome }}</a></td>
                        <td class="whitespace-nowrap">{{ $c->ultima_visita_em->format('d/m/Y') }} <span class="text-xs text-gray-600">({{ (int) $c->ultima_visita_em->diffInDays(now()) }} dias)</span></td>
                        <td class="text-right">{{ $c->total_visitas }}</td>
                        <td class="text-right whitespace-nowrap">{{ \App\Support\Dinheiro::formatar($c->total_gasto) }}</td>
                        <td class="text-right"><a href="{{ \App\Support\WhatsAppLink::reativacao($c) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icone nome="whatsapp" class="w-4 h-4 text-green-700" /> Chamar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-600">Nenhuma cliente inativa nesse período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
<div class="mt-4">{{ $clientes->links() }}</div>
@endsection
