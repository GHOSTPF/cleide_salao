@extends('painel.layout')
@section('titulo', $prof->exists ? $prof->nome : 'Nova profissional')

@php
    $abas = $prof->exists
        ? ['dados' => 'Dados', 'acesso' => 'Acesso', 'servicos' => 'Serviços', 'horarios' => 'Horários', 'bloqueios' => 'Bloqueios']
        : ['dados' => 'Dados'];
    $vinculados = $prof->servicos->keyBy('id');
@endphp

@section('conteudo')
<nav class="abas mb-6 overflow-x-auto" aria-label="Abas">
    @foreach ($abas as $chave => $rotulo)
        <a href="{{ $prof->exists ? route('admin.profissionais.edit', [$prof, 'aba' => $chave]) : '#' }}" class="whitespace-nowrap" @if ($aba === $chave) aria-current="page" @endif>{{ $rotulo }}</a>
    @endforeach
</nav>

@if ($aba === 'dados')
    <x-card class="max-w-3xl">
        <form method="POST" enctype="multipart/form-data" action="{{ $prof->exists ? route('admin.profissionais.update', $prof) : route('admin.profissionais.store') }}" class="grid gap-4 sm:grid-cols-2">
            @csrf @if ($prof->exists) @method('PUT') @endif
            <x-input nome="nome" rotulo="Nome completo" :valor="$prof->nome" required />
            <x-input nome="apelido" rotulo="Apelido (exibido no site)" :valor="$prof->apelido" />
            <x-input nome="telefone" rotulo="Telefone" :valor="$prof->telefone" />
            <x-input nome="percentual_comissao" rotulo="Comissão (%)" tipo="number" step="0.5" min="0" max="100" :valor="$prof->percentual_comissao" required />
            <div><label class="rotulo" for="cor_agenda">Cor na agenda</label><input type="color" id="cor_agenda" name="cor_agenda" value="{{ old('cor_agenda', $prof->cor_agenda) }}" class="h-11 w-20 rounded-md border border-gray-200"></div>
            <x-input nome="ordem" rotulo="Ordem" tipo="number" min="0" :valor="$prof->ordem ?? 0" />
            <div class="sm:col-span-2"><x-input nome="bio" rotulo="Bio (site)" tipo="textarea" :valor="$prof->bio" /></div>
            <div><label class="rotulo" for="foto">Foto</label>@if ($prof->foto_url)<img src="{{ $prof->foto_url }}" class="mb-2 h-20 w-20 rounded-full object-cover" alt="">@endif<input type="file" id="foto" name="foto" accept="image/*" class="text-sm"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $prof->ativo)) class="rounded border-gray-300"> Ativa</label>
            <div class="flex gap-2 sm:col-span-2">
                <x-button>Salvar</x-button>
                @if ($prof->exists)
                    <button form="excluir-prof" class="btn-secundario"><x-icone nome="lixo" class="w-4 h-4" /> Excluir/desativar</button>
                @endif
            </div>
        </form>
        @if ($prof->exists)
            <form id="excluir-prof" method="POST" action="{{ route('admin.profissionais.destroy', $prof) }}" onsubmit="return confirm('Excluir esta profissional? Se tiver histórico, ela será apenas desativada.')">@csrf @method('DELETE')</form>
        @endif
    </x-card>
@elseif ($aba === 'acesso')
    <x-card class="max-w-xl" titulo="Usuário de acesso ao painel">
        <form method="POST" action="{{ route('admin.profissionais.acesso', $prof) }}" class="space-y-4">
            @csrf @method('PUT')
            <x-input nome="email" rotulo="E-mail de login" tipo="email" :valor="$prof->user?->email" required />
            <x-input nome="password" rotulo="{{ $prof->user ? 'Nova senha (deixe em branco para manter)' : 'Senha' }}" tipo="password" autocomplete="new-password" />
            <x-input nome="password_confirmation" rotulo="Confirmar senha" tipo="password" autocomplete="new-password" />
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="ativo" value="1" @checked(old('ativo', $prof->user?->ativo ?? true)) class="rounded border-gray-300"> Acesso ativo</label>
            <x-button>{{ $prof->user ? 'Atualizar acesso' : 'Criar acesso' }}</x-button>
        </form>
    </x-card>
@elseif ($aba === 'servicos')
    <x-card titulo="Serviços que realiza" class="max-w-4xl">
        <p class="mb-4 text-sm text-gray-700">Deixe duração/preço em branco para usar o padrão do serviço.</p>
        <form method="POST" action="{{ route('admin.profissionais.servicos', $prof) }}">
            @csrf @method('PUT')
            @foreach ($categorias as $cat)
                <p class="mt-4 font-serif text-lg text-luna-brown">{{ $cat->nome }}</p>
                <div class="divide-y divide-luna-sand">
                    @foreach ($cat->servicos as $s)
                        @php $pv = $vinculados->get($s->id)?->pivot; @endphp
                        <div class="grid items-center gap-2 py-2 sm:grid-cols-[1fr_8rem_8rem]">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="servicos[{{ $s->id }}][ativo]" value="1" @checked($vinculados->has($s->id)) class="rounded border-gray-300">
                                {{ $s->nome }} <span class="text-xs text-gray-600">({{ $s->duracao_formatada }} · {{ \App\Support\Dinheiro::formatar($s->preco) }})</span>
                            </label>
                            <input type="number" name="servicos[{{ $s->id }}][duracao_minutos]" value="{{ $pv?->duracao_minutos }}" placeholder="{{ $s->duracao_minutos }} min" class="campo py-1.5 text-sm" min="5" step="5" aria-label="Duração para {{ $s->nome }}">
                            <input type="text" name="servicos[{{ $s->id }}][preco]" value="{{ $pv?->preco !== null ? number_format($pv->preco, 2, ',', '') : '' }}" placeholder="{{ number_format($s->preco, 2, ',', '') }}" class="campo py-1.5 text-sm" inputmode="decimal" aria-label="Preço para {{ $s->nome }}">
                        </div>
                    @endforeach
                </div>
            @endforeach
            <x-button class="mt-5">Salvar serviços</x-button>
        </form>
    </x-card>
@elseif ($aba === 'horarios')
    <x-card titulo="Horários de trabalho" class="max-w-4xl">
        <form method="POST" action="{{ route('admin.profissionais.horarios', $prof) }}">
            @csrf @method('PUT')
            <div class="divide-y divide-luna-sand">
                @foreach (\App\Models\HorarioTrabalho::DIAS as $dia => $nomeDia)
                    @php $h = $prof->horarios->firstWhere('dia_semana', $dia); @endphp
                    <div class="grid items-center gap-2 py-3 sm:grid-cols-[8rem_repeat(4,1fr)]" x-data="{ on: {{ $h ? 'true' : 'false' }} }">
                        <label class="flex items-center gap-2 font-medium"><input type="checkbox" name="dias[{{ $dia }}][trabalha]" value="1" x-model="on" class="rounded border-gray-300"> {{ $nomeDia }}</label>
                        @foreach (['hora_inicio' => 'Início', 'hora_fim' => 'Fim', 'intervalo_inicio' => 'Intervalo de', 'intervalo_fim' => 'Intervalo até'] as $campo => $rot)
                            <label class="text-xs text-gray-600" x-show="on">{{ $rot }}
                                <input type="time" name="dias[{{ $dia }}][{{ $campo }}]" value="{{ $h ? substr((string) $h->{$campo}, 0, 5) : ($campo === 'hora_inicio' ? '09:00' : ($campo === 'hora_fim' ? '18:00' : '')) }}" class="campo py-1.5 text-sm">
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
            <x-button class="mt-4">Salvar horários</x-button>
        </form>
    </x-card>
@elseif ($aba === 'bloqueios')
    <div class="grid max-w-5xl gap-6 lg:grid-cols-2">
        <x-card titulo="Novo bloqueio (folga, consulta, almoço extra)">
            @include('painel.partials.form-bloqueio', ['acao' => route('admin.profissionais.bloqueios.store', $prof)])
        </x-card>
        <x-card titulo="Bloqueios futuros">
            @include('painel.partials.lista-bloqueios', ['bloqueios' => $bloqueios, 'rotaExcluir' => 'admin.bloqueios.destroy'])
        </x-card>
    </div>
@endif
@endsection
