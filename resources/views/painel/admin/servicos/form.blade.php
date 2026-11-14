@extends('painel.layout')
@section('titulo', $servico->exists ? 'Editar serviço' : 'Novo serviço')

@section('conteudo')
<x-card class="max-w-3xl">
    <form method="POST" enctype="multipart/form-data" action="{{ $servico->exists ? route('admin.servicos.update', $servico) : route('admin.servicos.store') }}" class="grid gap-4 sm:grid-cols-2">
        @csrf
        @if ($servico->exists) @method('PUT') @endif
        <div class="sm:col-span-2"><x-input nome="nome" rotulo="Nome" :valor="$servico->nome" required /></div>
        <x-select nome="categoria_servico_id" rotulo="Categoria" :opcoes="$categorias" :valor="$servico->categoria_servico_id" />
        <x-input nome="ordem" rotulo="Ordem de exibição" tipo="number" min="0" :valor="$servico->ordem ?? 0" />
        <div class="sm:col-span-2"><x-input nome="descricao" rotulo="Descrição" tipo="textarea" :valor="$servico->descricao" /></div>
        <x-input nome="duracao_minutos" rotulo="Duração (min)" tipo="number" min="5" step="5" :valor="$servico->duracao_minutos" required />
        <x-input nome="intervalo_apos_minutos" rotulo="Intervalo após (min)" tipo="number" min="0" step="5" :valor="$servico->intervalo_apos_minutos ?? 0" ajuda="Limpeza/preparo: bloqueia a agenda, mas não aparece para a cliente." />
        <x-input nome="preco" rotulo="Preço (R$)" inputmode="decimal" :valor="$servico->preco !== null ? number_format($servico->preco, 2, ',', '') : ''" required />
        <x-input nome="retorno_sugerido_dias" rotulo="Retorno sugerido (dias)" tipo="number" min="1" :valor="$servico->retorno_sugerido_dias" ajuda="Ex.: mechas 60, terapia dos fios 30." />

        <div class="space-y-2 sm:col-span-2 text-sm">
            @foreach ([
                'preco_a_partir_de' => 'Exibir como “a partir de R$”',
                'requer_avaliacao' => 'Requer avaliação (salão confirma valor/tempo pelo WhatsApp)',
                'ativo' => 'Ativo',
                'exibir_no_site' => 'Exibir no site e no formulário de agendamento',
            ] as $campo => $rotulo)
                <label class="flex items-center gap-2">
                    <input type="hidden" name="{{ $campo }}" value="0">
                    <input type="checkbox" name="{{ $campo }}" value="1" class="rounded border-gray-300 text-luna-ink focus:ring-luna-gold" @checked(old($campo, $servico->{$campo}))> {{ $rotulo }}
                </label>
            @endforeach
        </div>

        <div class="sm:col-span-2">
            <label class="rotulo" for="foto">Foto</label>
            @if ($servico->foto_url)
                <img src="{{ $servico->foto_url }}" alt="" class="mb-2 h-32 rounded-lg object-cover">
                <label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remover_foto" value="1" class="rounded border-gray-300"> Remover foto</label>
            @endif
            <input type="file" name="foto" id="foto" accept="image/*" class="block text-sm">
        </div>

        <div class="flex gap-2 sm:col-span-2">
            <a href="{{ route('admin.servicos.index') }}" class="btn-secundario">Voltar</a>
            <x-button>Salvar</x-button>
        </div>
    </form>
</x-card>
@endsection
