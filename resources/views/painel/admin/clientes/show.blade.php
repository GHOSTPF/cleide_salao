@extends('painel.layout')
@section('titulo', $cliente->nome)

@php use App\Support\Dinheiro; use App\Support\WhatsAppLink; @endphp

@section('conteudo')
<div class="mb-5 flex flex-wrap items-center gap-2">
    <a href="{{ WhatsAppLink::paraCliente($cliente) }}" target="_blank" rel="noopener" class="btn-whatsapp btn-sm"><x-icone nome="whatsapp" class="w-4 h-4" /> {{ $cliente->telefone_formatado }}</a>
    <a href="{{ route('admin.agendamentos.create', ['cliente_id' => $cliente->id]) }}" class="btn-primario btn-sm"><x-icone nome="mais" class="w-4 h-4" /> Novo agendamento</a>
    <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn-secundario btn-sm"><x-icone nome="editar" class="w-4 h-4" /> Editar</a>
    @if ($cliente->bloqueada_online)
        <form method="POST" action="{{ route('admin.clientes.desbloquear', $cliente) }}">@csrf<button class="btn-secundario btn-sm"><x-icone nome="cadeado-aberto" class="w-4 h-4" /> Desbloquear online</button></form>
    @else
        <button type="button" class="btn-secundario btn-sm" x-data @click="$dispatch('open-modal', 'bloquear')"><x-icone nome="cadeado" class="w-4 h-4" /> Bloquear online</button>
    @endif
    <button type="button" class="btn-secundario btn-sm" x-data @click="$dispatch('open-modal', 'mesclar')"><x-icone nome="mesclar" class="w-4 h-4" /> Mesclar com outra cliente</button>
</div>

@if ($cliente->bloqueada_online)
    <p class="mb-5 flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900"><x-icone nome="cadeado" class="mt-px w-4 h-4 shrink-0" /> <span>Bloqueada para agendamento online: {{ $cliente->motivo_bloqueio }}</span></p>
@endif

{{-- Números principais em destaque; o resto como ficha --}}
<x-indicadores :itens="[
    ['rotulo' => 'Visitas', 'valor' => $cliente->total_visitas, 'sub' => 'desde ' . ($cliente->primeira_visita_em?->format('d/m/Y') ?? '—')],
    ['rotulo' => 'Última visita', 'valor' => $cliente->ultima_visita_em?->format('d/m/Y') ?? '—'],
    ['rotulo' => 'Total gasto', 'valor' => Dinheiro::formatar($cliente->total_gasto), 'sub' => 'ticket médio ' . Dinheiro::formatar($cliente->ticket_medio)],
    ['rotulo' => 'Faltas', 'valor' => $cliente->total_faltas, 'cor' => $cliente->total_faltas > 0 ? 'text-red-800' : '', 'sub' => $cliente->total_cancelamentos . ' cancel. · ' . $cliente->total_reagendamentos . ' reagend.'],
]" />

<dl class="mt-4 grid grid-cols-2 gap-x-6 gap-y-3 border-b border-luna-sand pb-5 text-sm md:grid-cols-4">
    @foreach ([
        ['Profissional mais frequente', $indicadores['profissional_mais_frequente']?->nome_exibicao ?? '—'],
        ['Serviço mais feito', $indicadores['servico_mais_feito']?->nome ?? '—'],
        ['Retorno sugerido', $indicadores['proximo_retorno'] ? $indicadores['proximo_retorno']['data']->format('d/m/Y') . ' (' . $indicadores['proximo_retorno']['servico'] . ')' : '—'],
        ['Origem', $cliente->origem->label()],
    ] as [$rotulo, $valor])
        <div><dt class="legenda">{{ $rotulo }}</dt><dd class="mt-0.5 font-medium">{{ $valor }}</dd></div>
    @endforeach
</dl>

<div class="mt-8 grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        @if ($proximo)
            <a href="{{ route('admin.agendamentos.show', $proximo) }}" class="group block rounded-lg border border-luna-sand border-l-[3px] border-l-luna-gold bg-white px-5 py-4 hover:bg-luna-cream/40">
                <p class="legenda">Próximo agendamento</p>
                <p class="mt-1 font-serif text-2xl group-hover:underline group-hover:decoration-luna-nude group-hover:underline-offset-4">{{ WhatsAppLink::dataExtenso($proximo->inicio) }} às {{ $proximo->inicio->format('H:i') }}</p>
                <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-600">{{ $proximo->servico->nome }} com {{ $proximo->profissional->nome_exibicao }} <x-badge-status :status="$proximo->status" /></p>
            </a>
        @endif

        <x-card titulo="Linha do tempo de atendimentos">
            <ol class="relative space-y-4 border-l border-luna-sand pl-5 text-sm">
                @forelse ($agendamentos as $a)
                    <li>
                        <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border-2 border-white" style="background: {{ $a->status->corBorda() }}"></span>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('admin.agendamentos.show', $a) }}" class="link font-medium">{{ $a->inicio->format('d/m/Y H:i') }}</a>
                            <span>{{ $a->servico->nome }} · {{ $a->profissional->nome_exibicao }}</span>
                            <x-badge-status :status="$a->status" />
                            @if ($a->valor_cobrado !== null)<span class="text-gray-700">{{ Dinheiro::formatar($a->valor_cobrado) }}</span>@endif
                        </div>
                        @if ($a->reagendadoPara)
                            <p class="text-xs text-gray-600">Reagendado para {{ $a->reagendadoPara->inicio->format('d/m H:i') }}
                                ({{ mb_strtolower(\App\Services\ReagendamentoService::PEDIDO_POR[$a->reagendado_por] ?? 'salão') }}{{ $a->motivo_reagendamento ? ', motivo: ' . $a->motivo_reagendamento : '' }})</p>
                        @endif
                        @if ($a->motivo_cancelamento)<p class="text-xs text-gray-600">Cancelado: {{ $a->motivo_cancelamento }}</p>@endif
                    </li>
                @empty
                    <li class="text-gray-600">Nenhum atendimento ainda.</li>
                @endforelse
            </ol>
        </x-card>
    </div>

    <div class="space-y-6">
        <x-card titulo="Dados">
            <dl class="space-y-2 text-sm">
                <div><dt class="legenda">E-mail</dt><dd>{{ $cliente->email ?: '—' }}</dd></div>
                <div><dt class="legenda">Nascimento</dt><dd>{{ $cliente->data_nascimento?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="legenda">Instagram</dt><dd>{{ $cliente->instagram ? '@' . $cliente->instagram : '—' }}</dd></div>
                <div><dt class="legenda">Profissional preferida</dt><dd>{{ $cliente->profissionalPreferida?->nome_exibicao ?? '—' }}</dd></div>
                <div><dt class="legenda">Consentimento LGPD</dt><dd>{{ $cliente->consentimento_lgpd_em?->format('d/m/Y H:i') ?? 'Não registrado' }}</dd></div>
                @if ($cliente->observacoes)<div><dt class="legenda">Observações</dt><dd class="whitespace-pre-line border-l-2 border-luna-nude pl-3">{{ $cliente->observacoes }}</dd></div>@endif
            </dl>
        </x-card>

        <x-card titulo="Anotações técnicas">
            <form method="POST" action="{{ route('admin.clientes.anotacao', $cliente) }}" class="space-y-2">@csrf
                <textarea name="anotacao" class="campo" rows="2" placeholder="Ex.: alergia a amônia; fórmula 7.1 + 20vol" required></textarea>
                <x-button tamanho="sm">Adicionar</x-button>
            </form>
            <ul class="mt-4 space-y-3 text-sm">
                @forelse ($cliente->anotacoes as $n)
                    <li class="border-l-2 border-luna-nude pl-3">{{ $n->anotacao }}<br><span class="text-xs text-gray-600">{{ $n->created_at->format('d/m/Y') }}{{ $n->profissional ? ' · ' . $n->profissional->nome_exibicao : '' }}</span></li>
                @empty
                    <li class="text-gray-600">Nenhuma anotação.</li>
                @endforelse
            </ul>
        </x-card>
    </div>
</div>

<x-modal name="bloquear" focusable>
    <form method="POST" action="{{ route('admin.clientes.bloquear', $cliente) }}" class="space-y-4 p-6">@csrf
        <h2 class="text-lg font-semibold">Bloquear agendamento online</h2>
        <p class="text-sm text-gray-700">No site, ela verá “Para agendar, fale com a gente pelo WhatsApp.” O salão continua podendo agendá-la pelo painel.</p>
        <x-input nome="motivo" rotulo="Motivo" placeholder="Ex.: muitas faltas" required />
        <div class="flex justify-end gap-2"><button type="button" class="btn-secundario" x-on:click="$dispatch('close')">Voltar</button><button class="btn-perigo">Bloquear</button></div>
    </form>
</x-modal>

<x-modal name="mesclar" focusable>
    <form method="POST" action="{{ route('admin.clientes.mesclar') }}" class="space-y-4 p-6">@csrf
        <h2 class="text-lg font-semibold">Mesclar clientes</h2>
        <p class="text-sm text-gray-700"><strong>{{ $cliente->nome }}</strong> será a cliente principal. Agendamentos, pagamentos, anotações e lista de espera da outra cliente serão movidos para cá e os totais recalculados.</p>
        <input type="hidden" name="principal_id" value="{{ $cliente->id }}">
        <div>
            <label class="rotulo" for="secundaria_id">Cliente duplicada</label>
            <select name="secundaria_id" id="secundaria_id" class="campo" required>
                <option value="">Selecione</option>
                @foreach ($outrasClientes as $o)<option value="{{ $o->id }}">{{ $o->nome }} — {{ $o->telefone_formatado }}</option>@endforeach
            </select>
        </div>
        <div class="flex justify-end gap-2"><button type="button" class="btn-secundario" x-on:click="$dispatch('close')">Voltar</button><button class="btn-primario">Mesclar</button></div>
    </form>
</x-modal>
@endsection
