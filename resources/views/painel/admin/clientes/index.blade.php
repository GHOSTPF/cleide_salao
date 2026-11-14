@extends('painel.layout')
@section('titulo', 'Clientes')

@section('conteudo')
<x-card padding="p-4 sm:p-5" class="mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="min-w-[14rem] flex-1"><label class="rotulo" for="q">Buscar</label><input id="q" name="q" value="{{ $filtros['q'] ?? '' }}" class="campo" placeholder="Nome ou WhatsApp"></div>
        <x-select nome="filtro" rotulo="Filtro" vazio="Todas" :valor="$filtros['filtro'] ?? ''"
                  :opcoes="['sumidas' => 'Sem vir há mais de 60 dias', 'aniversariantes' => 'Aniversariantes do mês', 'bloqueadas' => 'Bloqueadas online']" />
        <x-select nome="ordem" rotulo="Ordenar" vazio="Nome" :valor="$filtros['ordem'] ?? ''" :opcoes="['visita' => 'Última visita', 'gasto' => 'Total gasto']" />
        <x-button icone="busca">Filtrar</x-button>
        <a href="{{ route('admin.clientes.duplicadas') }}" class="btn-secundario"><x-icone nome="mesclar" class="w-4 h-4" /> Possíveis duplicadas</a>
        <span class="ml-auto flex gap-2">
            <a href="{{ route('admin.clientes.exportar', array_merge($filtros, ['formato' => 'xlsx'])) }}" class="btn-secundario btn-sm"><x-icone nome="download" class="w-4 h-4" /> Excel</a>
            <a href="{{ route('admin.clientes.exportar', array_merge($filtros, ['formato' => 'pdf'])) }}" class="btn-secundario btn-sm"><x-icone nome="download" class="w-4 h-4" /> PDF</a>
        </span>
    </form>
</x-card>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="tabela">
            <thead><tr><th>Nome</th><th>WhatsApp</th><th>Última visita</th><th class="text-right">Visitas</th><th class="text-right">Total gasto</th><th class="text-right">Faltas</th></tr></thead>
            <tbody class="divide-y divide-luna-sand">
                @forelse ($clientes as $c)
                    <tr class="hover:bg-luna-cream/40">
                        <td>
                            <a href="{{ route('admin.clientes.show', $c) }}" class="link">{{ $c->nome }}</a>
                            @if ($c->bloqueada_online)<span class="ml-1 rounded-full bg-red-100 px-2 text-[11px] text-red-900">bloqueada</span>@endif
                        </td>
                        <td class="whitespace-nowrap"><a href="{{ \App\Support\WhatsAppLink::paraCliente($c) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 hover:text-green-800"><x-icone nome="whatsapp" class="w-4 h-4" /> {{ $c->telefone_formatado }}</a></td>
                        <td class="whitespace-nowrap">{{ $c->ultima_visita_em?->format('d/m/Y') ?? '—' }}</td>
                        <td class="text-right">{{ $c->total_visitas }}</td>
                        <td class="whitespace-nowrap text-right">{{ \App\Support\Dinheiro::formatar($c->total_gasto) }}</td>
                        <td class="text-right {{ $c->total_faltas ? 'text-red-700' : '' }}">{{ $c->total_faltas }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-6 text-center text-gray-600">Nenhuma cliente encontrada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
<div class="mt-4">{{ $clientes->links() }}</div>
@endsection
