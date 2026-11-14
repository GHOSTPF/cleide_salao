@extends('painel.layout')
@section('titulo', 'Agendamentos')

@section('conteudo')
<x-card padding="p-4 sm:p-5" class="mb-5">
    <form method="GET" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 items-end">
        <div class="xl:col-span-2"><label class="rotulo" for="q">Busca</label><input id="q" name="q" value="{{ $filtros['q'] ?? '' }}" class="campo" placeholder="Nome, telefone ou código"></div>
        <div><label class="rotulo" for="de">De</label><input type="date" id="de" name="de" value="{{ $filtros['de'] ?? '' }}" class="campo"></div>
        <div><label class="rotulo" for="ate">Até</label><input type="date" id="ate" name="ate" value="{{ $filtros['ate'] ?? '' }}" class="campo"></div>
        <x-select nome="status" rotulo="Status" vazio="Todos" :valor="$filtros['status'] ?? ''"
                  :opcoes="collect(\App\Enums\StatusAgendamento::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <x-select nome="profissional_id" rotulo="Profissional" vazio="Todas" :valor="$filtros['profissional_id'] ?? ''" :opcoes="$profissionais" />
        <x-select nome="servico_id" rotulo="Serviço" vazio="Todos" :valor="$filtros['servico_id'] ?? ''" :opcoes="$servicos" />
        <x-select nome="origem" rotulo="Origem" vazio="Todas" :valor="$filtros['origem'] ?? ''"
                  :opcoes="collect(\App\Enums\OrigemAgendamento::cases())->mapWithKeys(fn ($o) => [$o->value => $o->label()])" />
        <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-4 xl:col-span-8">
            <x-button icone="busca">Filtrar</x-button>
            <a href="{{ route('admin.agendamentos.index') }}" class="btn-secundario">Limpar</a>
            <a href="{{ route('admin.agendamentos.create') }}" class="btn-secundario"><x-icone nome="mais" class="w-4 h-4" /> Novo</a>
            <span class="ml-auto flex gap-2">
                <a href="{{ route('admin.agendamentos.exportar', array_merge($filtros, ['formato' => 'xlsx'])) }}" class="btn-secundario btn-sm"><x-icone nome="download" class="w-4 h-4" /> Excel</a>
                <a href="{{ route('admin.agendamentos.exportar', array_merge($filtros, ['formato' => 'pdf'])) }}" class="btn-secundario btn-sm"><x-icone nome="download" class="w-4 h-4" /> PDF</a>
            </span>
        </div>
    </form>
</x-card>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="tabela">
            <thead><tr><th>Data</th><th>Cliente</th><th>Serviço</th><th>Profissional</th><th>Origem</th><th>Status</th><th class="text-right">Valor</th></tr></thead>
            <tbody class="divide-y divide-luna-sand">
                @forelse ($agendamentos as $a)
                    <tr class="hover:bg-luna-cream/40">
                        <td class="whitespace-nowrap"><a href="{{ route('admin.agendamentos.show', $a) }}" class="link">{{ $a->inicio->format('d/m/Y H:i') }}</a><br><span class="text-xs text-gray-600">{{ $a->codigo }}</span></td>
                        <td>{{ $a->cliente->nome }}<br><span class="text-xs text-gray-600">{{ $a->cliente->telefone_formatado }}</span></td>
                        <td>{{ $a->servico->nome }}</td>
                        <td>{{ $a->profissional->nome_exibicao }}</td>
                        <td>{{ $a->origem->label() }}</td>
                        <td><x-badge-status :status="$a->status" /></td>
                        <td class="whitespace-nowrap text-right">{{ \App\Support\Dinheiro::formatar($a->valor_cobrado ?? $a->preco_previsto) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-gray-600">Nenhum agendamento encontrado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
<div class="mt-4">{{ $agendamentos->links() }}</div>
@endsection
