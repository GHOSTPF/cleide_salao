@extends('painel.layout')
@section('titulo', 'Dashboard')

@php use App\Support\Dinheiro; use App\Support\WhatsAppLink; @endphp

@section('conteudo')
<p class="mb-5 font-serif text-2xl">{{ ucfirst(today()->translatedFormat('l, d \d\e F')) }}</p>

{{-- Números principais numa faixa só --}}
<x-indicadores :itens="[
    ['rotulo' => 'Agendamentos hoje', 'valor' => $cards['hoje_total'], 'sub' => $cards['hoje_confirmados'] . ' confirmados · ' . $cards['hoje_pendentes'] . ' pendentes'],
    ['rotulo' => 'Faturamento hoje', 'valor' => Dinheiro::formatar($cards['faturamento_hoje'])],
    ['rotulo' => 'Faturamento do mês', 'valor' => Dinheiro::formatar($cards['faturamento_mes']), 'sub' => 'ticket médio ' . Dinheiro::formatar($cards['ticket_medio'])],
    ['rotulo' => 'Clientes novas no mês', 'valor' => $cards['clientes_novas'], 'sub' => 'faltas no mês: ' . $cards['taxa_faltas'] . '%'],
]" />

<div class="mt-8 grid gap-8 xl:grid-cols-3">
    {{-- Alertas --}}
    <section class="xl:col-span-2">
        @php
            $temAlerta = $alertas['pendentes'] || $alertas['sem_desfecho']->isNotEmpty() || $alertas['lista_espera_vaga']->isNotEmpty()
                || $alertas['nome_divergente']->isNotEmpty() || $alertas['duplicadas'] || $alertas['bloqueadas_faltas']->isNotEmpty()
                || $alertas['reagendados_profissional']->isNotEmpty() || $alertas['sem_whatsapp']->isNotEmpty();
        @endphp
        <h2 class="mb-3 text-sm font-semibold">Precisa de atenção</h2>

        @unless ($temAlerta)
            <div class="rounded-lg border border-dashed border-luna-nude px-6 py-8 text-center text-sm text-gray-600">Tudo em dia por aqui.</div>
        @else
            <div class="divide-y divide-luna-sand/70 overflow-hidden rounded-lg border border-luna-sand bg-white text-sm">
                @if ($alertas['pendentes'])
                    <a href="{{ route('admin.pendentes') }}" class="flex items-center justify-between gap-3 border-l-[3px] border-l-amber-500 px-4 py-3 hover:bg-luna-cream/50">
                        <span class="flex items-center gap-2.5"><x-icone nome="sino" class="w-4 h-4 text-amber-600" /> <span><strong class="font-semibold">{{ $alertas['pendentes'] }}</strong> {{ $alertas['pendentes'] === 1 ? 'agendamento aguardando' : 'agendamentos aguardando' }} confirmação</span></span>
                        <x-icone nome="seta-dir" class="w-4 h-4 text-gray-400" />
                    </a>
                @endif

                @if ($alertas['sem_desfecho']->isNotEmpty())
                    <div class="border-l-[3px] border-l-red-500 px-4 py-3">
                        <p class="flex items-center gap-2.5 font-medium"><x-icone nome="alerta" class="w-4 h-4 text-red-600" /> Sem desfecho — concluir ou marcar falta</p>
                        <ul class="mt-2 space-y-1 pl-[26px]">
                            @foreach ($alertas['sem_desfecho'] as $a)
                                <li><a class="link" href="{{ route('admin.agendamentos.show', $a) }}">{{ $a->inicio->format('d/m H:i') }} · {{ $a->cliente->nome }}</a> <span class="text-gray-500">· {{ $a->servico->nome }} ({{ $a->profissional->nome_exibicao }})</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($alertas['lista_espera_vaga']->isNotEmpty())
                    <div class="border-l-[3px] border-l-emerald-500 px-4 py-3">
                        <p class="flex items-center gap-2.5 font-medium"><x-icone nome="ampulheta" class="w-4 h-4 text-emerald-600" /> Abriu vaga para quem está na lista de espera</p>
                        <ul class="mt-2 space-y-2 pl-[26px]">
                            @foreach ($alertas['lista_espera_vaga'] as $e)
                                <li class="flex flex-wrap items-center gap-2">
                                    <span class="mr-auto">{{ $e->cliente->nome }} <span class="text-gray-500">· {{ $e->servico->nome }} · {{ $e->data_desejada->format('d/m') }}</span></span>
                                    <a href="{{ WhatsAppLink::listaEspera($e) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icone nome="whatsapp" class="w-3.5 h-3.5 text-green-700" /> Chamar</a>
                                    <a href="{{ route('admin.agendamentos.create', ['lista_espera_id' => $e->id]) }}" class="btn-secundario btn-sm">Agendar</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($alertas['nome_divergente']->isNotEmpty())
                    <div class="border-l-[3px] border-l-luna-gold px-4 py-3">
                        <p class="flex items-center gap-2.5 font-medium"><x-icone nome="usuario" class="w-4 h-4 text-luna-gold" /> Nome diferente do cadastro</p>
                        <ul class="mt-2 space-y-2 pl-[26px]">
                            @foreach ($alertas['nome_divergente'] as $a)
                                <li class="flex flex-wrap items-center gap-2">
                                    <span class="mr-auto">“{{ $a->nome_informado }}” × “{{ $a->cliente->nome }}” <a class="link text-xs" href="{{ route('admin.agendamentos.show', $a) }}">{{ $a->codigo }}</a></span>
                                    <form method="POST" action="{{ route('admin.agendamentos.nome-divergente', $a) }}" class="flex gap-1">
                                        @csrf
                                        <button name="acao" value="atualizar" class="btn-secundario btn-sm">Atualizar cadastro</button>
                                        <button name="acao" value="manter" class="btn-secundario btn-sm">Manter</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($alertas['sem_whatsapp']->isNotEmpty())
                    <div class="border-l-[3px] border-l-luna-gold px-4 py-3">
                        <p class="flex items-center gap-2.5 font-medium"><x-icone nome="whatsapp" class="w-4 h-4 text-luna-gold" /> Agendaram pelo site e não mandaram o WhatsApp</p>
                        <ul class="mt-2 space-y-2 pl-[26px]">
                            @foreach ($alertas['sem_whatsapp'] as $a)
                                <li class="flex flex-wrap items-center gap-2">
                                    <a class="link mr-auto" href="{{ route('admin.agendamentos.show', $a) }}">{{ $a->inicio->format('d/m H:i') }} · {{ $a->cliente->nome }} · {{ $a->servico->nome }}</a>
                                    <a href="{{ WhatsAppLink::confirmarComCliente($a) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm"><x-icone nome="whatsapp" class="w-3.5 h-3.5 text-green-700" /> Chamar</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($alertas['reagendados_profissional']->isNotEmpty())
                    <div class="border-l-[3px] border-l-violet-500 px-4 py-3">
                        <p class="flex items-center gap-2.5 font-medium"><x-icone nome="refazer" class="w-4 h-4 text-violet-600" /> Reagendados pelas profissionais <span class="font-normal text-gray-500">(últimos 7 dias)</span></p>
                        <ul class="mt-2 space-y-1 pl-[26px]">
                            @foreach ($alertas['reagendados_profissional'] as $a)
                                <li>{{ $a->profissional->nome_exibicao }}: {{ $a->cliente->nome }} <span class="text-gray-500">— {{ $a->inicio->format('d/m H:i') }} →</span> @if ($a->reagendadoPara)<a class="link" href="{{ route('admin.agendamentos.show', $a->reagendadoPara) }}">{{ $a->reagendadoPara->inicio->format('d/m H:i') }}</a>@endif</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($alertas['bloqueadas_faltas']->isNotEmpty())
                    <div class="border-l-[3px] border-l-red-500 px-4 py-3">
                        <p class="flex items-center gap-2.5 font-medium"><x-icone nome="cadeado" class="w-4 h-4 text-red-600" /> Clientes bloqueadas por faltas</p>
                        <ul class="mt-2 space-y-1 pl-[26px]">
                            @foreach ($alertas['bloqueadas_faltas'] as $c)
                                <li><a class="link" href="{{ route('admin.clientes.show', $c) }}">{{ $c->nome }}</a> <span class="text-gray-500">— {{ $c->total_faltas }} faltas</span></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($alertas['duplicadas'])
                    <a href="{{ route('admin.clientes.duplicadas') }}" class="flex items-center justify-between gap-3 border-l-[3px] border-l-luna-gold px-4 py-3 hover:bg-luna-cream/50">
                        <span class="flex items-center gap-2.5"><x-icone nome="mesclar" class="w-4 h-4 text-luna-gold" /> <span><strong class="font-semibold">{{ $alertas['duplicadas'] }}</strong> {{ $alertas['duplicadas'] === 1 ? 'possível cliente duplicada' : 'possíveis clientes duplicadas' }}</span></span>
                        <x-icone nome="seta-dir" class="w-4 h-4 text-gray-400" />
                    </a>
                @endif
            </div>
        @endunless
    </section>

    {{-- Próximos atendimentos --}}
    <section>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold">Próximos atendimentos</h2>
            <a href="{{ route('admin.agendamentos.create') }}" class="btn-primario btn-sm"><x-icone nome="mais" class="w-4 h-4" /> Novo</a>
        </div>
        @if ($proximos->isEmpty())
            <div class="rounded-lg border border-dashed border-luna-nude px-6 py-8 text-center text-sm text-gray-600">Nenhum atendimento próximo.</div>
        @else
            <ul class="divide-y divide-luna-sand/70 overflow-hidden rounded-lg border border-luna-sand bg-white text-sm">
                @foreach ($proximos as $a)
                    <li>
                        <a href="{{ route('admin.agendamentos.show', $a) }}" class="flex items-start gap-3 px-4 py-2.5 hover:bg-luna-cream/50">
                            <span class="w-12 shrink-0 tabular-nums">
                                <span class="block font-semibold">{{ $a->inicio->format('H:i') }}</span>
                                <span class="block text-xs text-gray-500">{{ $a->inicio->isToday() ? 'hoje' : $a->inicio->format('d/m') }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ $a->cliente->nome }}</span>
                                <span class="block truncate text-xs text-gray-500">{{ $a->servico->nome }} · {{ $a->profissional->nome_exibicao }}</span>
                            </span>
                            <x-badge-status :status="$a->status" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-2">
    {{-- Faturamento 6 meses (barras em CSS) --}}
    <x-card titulo="Faturamento — últimos 6 meses">
        @php $max = max(1, $faturamento6->max('total')); @endphp
        <div class="flex h-48 items-end gap-3 border-b border-luna-sand" role="img" aria-label="Gráfico de faturamento mensal">
            @foreach ($faturamento6 as $m)
                <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ Dinheiro::formatar($m['total']) }}">
                    <span class="whitespace-nowrap text-[10px] tabular-nums text-gray-500">{{ $m['total'] >= 1000 ? number_format($m['total'] / 1000, 1, ',', '.') . 'k' : number_format($m['total'], 0, ',', '.') }}</span>
                    <div @class(['w-full max-w-12 rounded-t-sm', 'bg-luna-ink' => $loop->last, 'bg-luna-gold/70' => ! $loop->last]) style="height: {{ max(2, round($m['total'] / $max * 100)) }}%"></div>
                </div>
            @endforeach
        </div>
        <div class="mt-1.5 flex gap-3">
            @foreach ($faturamento6 as $m)<span class="flex-1 text-center text-xs text-gray-500">{{ $m['mes'] }}</span>@endforeach
        </div>
    </x-card>

    <x-card titulo="Serviços mais vendidos no mês">
        @php $maxS = max(1, $rankingServicos->max('total')); @endphp
        <ul class="space-y-3.5 text-sm">
            @forelse ($rankingServicos as $s)
                <li>
                    <div class="flex justify-between gap-3"><span class="truncate">{{ $s->nome }}</span><span class="shrink-0 tabular-nums text-gray-500">{{ $s->total }}× · {{ Dinheiro::formatar($s->valor) }}</span></div>
                    <div class="mt-1.5 h-1.5 rounded-sm bg-luna-sand/70"><div class="h-1.5 rounded-sm bg-luna-brown" style="width: {{ round($s->total / $maxS * 100) }}%"></div></div>
                </li>
            @empty
                <li class="text-gray-600">Nenhum atendimento concluído neste mês.</li>
            @endforelse
        </ul>
    </x-card>
</div>
@endsection
