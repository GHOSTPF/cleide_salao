@extends('painel.layout')
@section('titulo', 'Serviços')

@section('conteudo')
<div class="mb-5 flex flex-wrap gap-2">
    <a href="{{ route('admin.servicos.create') }}" class="btn-primario"><x-icone nome="mais" class="w-4 h-4" /> Novo serviço</a>
    <a href="{{ route('admin.categorias.index') }}" class="btn-secundario">Categorias</a>
</div>

@foreach ($categorias as $cat)
    <x-card :titulo="$cat->nome" class="mb-5" padding="p-0 sm:p-0">
        <div class="overflow-x-auto">
            <table class="tabela">
                <thead><tr><th>Serviço</th><th>Duração</th><th>Preço</th><th>Retorno</th><th>Profissionais</th><th>Situação</th><th></th></tr></thead>
                <tbody class="divide-y divide-luna-sand">
                    @forelse ($cat->servicos as $s)
                        <tr class="{{ $s->ativo ? '' : 'opacity-60' }}">
                            <td class="font-medium">{{ $s->nome }}@if ($s->requer_avaliacao)<span class="ml-1 text-xs text-luna-brown">(avaliação)</span>@endif</td>
                            <td class="whitespace-nowrap">{{ $s->duracao_formatada }}@if ($s->intervalo_apos_minutos)<span class="text-xs text-gray-600"> +{{ $s->intervalo_apos_minutos }} preparo</span>@endif</td>
                            <td class="whitespace-nowrap">{{ $s->preco_formatado }}</td>
                            <td>{{ $s->retorno_sugerido_dias ? $s->retorno_sugerido_dias . ' dias' : '—' }}</td>
                            <td>{{ $s->profissionais_count }}</td>
                            <td class="text-xs">{{ $s->ativo ? 'Ativo' : 'Inativo' }}{{ $s->exibir_no_site ? ' · no site' : '' }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.servicos.edit', $s) }}" class="btn-secundario btn-sm"><x-icone nome="editar" class="w-4 h-4" /> Editar</a>
                                <form method="POST" action="{{ route('admin.servicos.destroy', $s) }}" class="inline" onsubmit="return confirm('Excluir este serviço?')">@csrf @method('DELETE')
                                    <button class="btn-secundario btn-sm" aria-label="Excluir"><x-icone nome="lixo" class="w-4 h-4" /></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-4 text-center text-gray-600">Sem serviços.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>
@endforeach
@endsection
