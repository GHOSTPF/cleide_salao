@extends('painel.layout')
@section('titulo', 'Log de atividades')

@section('conteudo')
<x-card padding="p-4 sm:p-5" class="mb-5">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <x-select nome="user_id" rotulo="Usuário" vazio="Todos" :opcoes="$usuarios" :valor="$filtros['user_id'] ?? ''" />
        <x-select nome="acao" rotulo="Ação" vazio="Todas" :opcoes="$acoes" :valor="$filtros['acao'] ?? ''" />
        <x-select nome="entidade_tipo" rotulo="Tipo" vazio="Todos" :opcoes="$tipos" :valor="$filtros['entidade_tipo'] ?? ''" />
        <x-button icone="busca">Filtrar</x-button>
    </form>
</x-card>

<x-card padding="p-0">
    <div class="overflow-x-auto">
        <table class="tabela">
            <thead><tr><th>Quando</th><th>Quem</th><th>Ação</th><th>Registro</th><th>Alterações</th></tr></thead>
            <tbody class="divide-y divide-luna-sand">
                @forelse ($atividades as $a)
                    <tr>
                        <td class="whitespace-nowrap">{{ $a->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $a->user?->nome ?? 'Sistema / site' }}</td>
                        <td><span class="rounded-full bg-luna-cream px-2 py-0.5 text-xs">{{ str_replace('_', ' ', $a->acao) }}</span></td>
                        <td class="whitespace-nowrap">
                            @if ($a->entidade_tipo === 'Agendamento' && $a->entidade_id)
                                <a class="link" href="{{ route('admin.agendamentos.show', $a->entidade_id) }}">Agendamento #{{ $a->entidade_id }}</a>
                            @elseif ($a->entidade_tipo === 'Cliente' && $a->entidade_id)
                                <a class="link" href="{{ route('admin.clientes.show', $a->entidade_id) }}">Cliente #{{ $a->entidade_id }}</a>
                            @else
                                {{ $a->entidade_tipo }}{{ $a->entidade_id ? ' #' . $a->entidade_id : '' }}
                            @endif
                        </td>
                        <td class="max-w-md text-xs text-gray-700">
                            @foreach (($a->dados_depois ?? []) as $campo => $valor)
                                @continue(in_array($campo, ['id', 'token_publico']))
                                <span class="mr-2 inline-block"><strong>{{ $campo }}</strong>:
                                    @if (is_array($a->dados_antes) && array_key_exists($campo, $a->dados_antes))<span class="line-through">{{ \Illuminate\Support\Str::limit(is_scalar($a->dados_antes[$campo]) ? (string) $a->dados_antes[$campo] : json_encode($a->dados_antes[$campo]), 30) }}</span> → @endif
                                    {{ \Illuminate\Support\Str::limit(is_scalar($valor) ? (string) $valor : json_encode($valor), 40) }}
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-gray-600">Sem registros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>
<div class="mt-4">{{ $atividades->links() }}</div>
@endsection
