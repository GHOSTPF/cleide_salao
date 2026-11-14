{{-- Detalhe do agendamento (admin e profissional). Variáveis: $ag, $cadeia, $prefixo --}}
@php
    use App\Enums\StatusAgendamento as S;
    use App\Support\Dinheiro;
    use App\Support\WhatsAppLink;
    $user = auth()->user();
    $ativo = in_array($ag->status, [S::Pendente, S::Confirmado], true);
    $rotaCliente = $user->isAdmin() ? route('admin.clientes.show', $ag->cliente) : route('profissional.clientes.show', $ag->cliente);
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        <x-card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs text-gray-500">{{ $ag->codigo }} · origem: {{ $ag->origem->label() }}</p>
                    <h2 class="mt-1 font-serif text-2xl sm:text-3xl">{{ ucfirst($ag->inicio->translatedFormat('l, d/m/Y')) }}</h2>
                    <p class="text-lg tabular-nums text-gray-700">{{ $ag->inicio->format('H:i') }} – {{ $ag->fim->format('H:i') }}</p>
                </div>
                <x-badge-status :status="$ag->status" class="text-sm" />
            </div>

            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="legenda">Cliente</dt>
                    <dd class="font-medium"><a href="{{ $rotaCliente }}" class="link">{{ $ag->cliente->nome }}</a></dd>
                    <dd class="text-gray-700">{{ $ag->cliente->telefone_formatado }}</dd>
                    @if ($ag->nome_divergente && $user->isAdmin())
                        <dd class="mt-2 rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            Nome diferente do cadastro: “{{ $ag->nome_informado }}” × “{{ $ag->cliente->nome }}”
                            <form method="POST" action="{{ route('admin.agendamentos.nome-divergente', $ag) }}" class="mt-2 flex gap-1">@csrf
                                <button name="acao" value="atualizar" class="btn-secundario btn-sm">Atualizar cadastro</button>
                                <button name="acao" value="manter" class="btn-secundario btn-sm">Manter</button>
                            </form>
                        </dd>
                    @endif
                </div>
                <div><dt class="legenda">Serviço</dt><dd class="font-medium">{{ $ag->servico->nome }}</dd><dd class="text-gray-700">{{ \App\Models\Servico::formatarDuracao($ag->duracao_minutos) }}</dd></div>
                <div><dt class="legenda">Profissional</dt><dd class="font-medium">{{ $ag->profissional->nome_exibicao }}</dd></div>
                <div>
                    <dt class="legenda">Valor</dt>
                    <dd class="font-medium">
                        @if ($ag->valor_cobrado !== null)
                            {{ Dinheiro::formatar($ag->valor_cobrado) }} <span class="text-xs text-gray-600">(previsto {{ Dinheiro::formatar($ag->preco_previsto) }})</span>
                        @else
                            {{ Dinheiro::formatar($ag->preco_previsto) }} <span class="text-xs text-gray-600">previsto</span>
                        @endif
                    </dd>
                </div>
                @if ($ag->observacoes_cliente)
                    <div class="sm:col-span-2"><dt class="legenda">Observações da cliente</dt><dd class="mt-1 border-l-2 border-luna-nude pl-3 italic text-luna-brown">“{{ $ag->observacoes_cliente }}”</dd></div>
                @endif
                @if ($ag->status === S::Cancelado)
                    <div class="sm:col-span-2"><dt class="legenda">Cancelamento</dt><dd>{{ $ag->cancelado_por?->label() }} em {{ $ag->cancelado_em?->format('d/m/Y H:i') }}{{ $ag->motivo_cancelamento ? ' — ' . $ag->motivo_cancelamento : '' }}</dd></div>
                @endif
                @if ($ag->origem->value === 'site')
                    <div><dt class="legenda">WhatsApp ao salão</dt><dd>{{ $ag->whatsapp_enviado_em ? 'Enviado em ' . $ag->whatsapp_enviado_em->format('d/m H:i') : 'Não enviado' }}</dd></div>
                @endif
                @if ($ag->pagamentos->isNotEmpty())
                    <div><dt class="legenda">Pagamento</dt>
                        @foreach ($ag->pagamentos as $p)<dd>{{ $p->forma->label() }}: {{ Dinheiro::formatar($p->valor) }}</dd>@endforeach
                    </div>
                @endif
                @if ($ag->comissao)
                    <div><dt class="legenda">Comissão</dt><dd>{{ Dinheiro::formatar($ag->comissao->valor) }} ({{ rtrim(rtrim(number_format($ag->comissao->percentual, 2, ',', ''), '0'), ',') }}%) · {{ $ag->comissao->status === 'paga' ? 'paga' : 'pendente' }}</dd></div>
                @endif
            </dl>

            {{-- Ações --}}
            <div class="mt-6 flex flex-wrap gap-2 border-t border-luna-sand pt-5">
                @can('confirmar', $ag)
                    <form method="POST" action="{{ route($prefixo . 'agendamentos.confirmar', $ag) }}">@csrf<x-button tamanho="sm" icone="check">Confirmar</x-button></form>
                @endcan
                @if ($ativo)
                    @can('concluir', $ag)
                        <button type="button" class="btn-primario btn-sm" x-data @click="$dispatch('open-modal', 'concluir')" @disabled(! $ag->ja_comecou) title="{{ $ag->ja_comecou ? '' : 'Disponível após o horário de início' }}"><x-icone nome="dinheiro" class="w-4 h-4" /> Concluir</button>
                    @endcan
                    @can('reagendar', $ag)
                        <a href="{{ route($prefixo . 'agendamentos.reagendar', $ag) }}" class="btn-secundario btn-sm"><x-icone nome="refazer" class="w-4 h-4" /> Reagendar</a>
                    @endcan
                    @can('cancelar', $ag)
                        <button type="button" class="btn-secundario btn-sm" x-data @click="$dispatch('open-modal', 'cancelar')"><x-icone nome="x" class="w-4 h-4" /> Cancelar</button>
                    @endcan
                @endif
                @if ($ag->status === S::Confirmado && $ag->ja_comecou)
                    @can('marcarFalta', $ag)
                        <form method="POST" action="{{ route($prefixo . 'agendamentos.falta', $ag) }}" onsubmit="return confirm('Registrar que a cliente não compareceu?')">@csrf
                            <button class="btn-secundario btn-sm"><x-icone nome="alerta" class="w-4 h-4" /> Não compareceu</button>
                        </form>
                    @endcan
                @endif
                @if ($ag->status === S::NaoCompareceu)
                    @can('desfazerFalta', $ag)
                        <form method="POST" action="{{ route('admin.agendamentos.desfazer-falta', $ag) }}">@csrf<button class="btn-secundario btn-sm">Desfazer falta</button></form>
                    @endcan
                @endif
                @if ($ag->status === S::Concluido)
                    @can('estornar', $ag)
                        <button type="button" class="btn-secundario btn-sm" x-data @click="$dispatch('open-modal', 'estornar')">Estornar</button>
                    @endcan
                @endif
                <a href="{{ WhatsAppLink::paraCliente($ag->cliente) }}" target="_blank" rel="noopener" class="btn-whatsapp btn-sm"><x-icone nome="whatsapp" class="w-4 h-4" /> WhatsApp da cliente</a>
                @if ($ag->status === S::Pendente)
                    <a href="{{ WhatsAppLink::confirmarComCliente($ag) }}" target="_blank" rel="noopener" class="btn-secundario btn-sm">Mensagem de confirmação</a>
                @endif
            </div>
        </x-card>

        {{-- Cadeia de reagendamentos --}}
        @if ($cadeia->isNotEmpty())
            <x-card titulo="Cadeia de reagendamentos">
                <ol class="space-y-2 text-sm">
                    @foreach ($cadeia as $i => $c)
                        <li class="flex flex-wrap items-center gap-2 {{ $c->id === $ag->id ? 'font-medium' : '' }}">
                            <span class="text-gray-600">{{ $i === 0 ? 'Agendado para' : '→ reagendado para' }}</span>
                            <a href="{{ route($prefixo . 'agendamentos.show', $c) }}" class="link">{{ $c->inicio->format('d/m H:i') }}</a>
                            <span class="text-gray-600">({{ $c->profissional->nome_exibicao }}, {{ $c->codigo }})</span>
                            @if ($c->reagendado_por)
                                <span class="text-xs text-gray-600">{{ mb_strtolower(\App\Services\ReagendamentoService::PEDIDO_POR[$c->reagendado_por] ?? $c->reagendado_por) }}{{ $c->motivo_reagendamento ? ', motivo: ' . $c->motivo_reagendamento : '' }}</span>
                            @endif
                            <x-badge-status :status="$c->status" />
                        </li>
                    @endforeach
                </ol>
            </x-card>
        @endif

        {{-- Linha do tempo --}}
        <x-card titulo="Linha do tempo">
            <ol class="relative space-y-4 border-l border-luna-sand pl-5 text-sm">
                @foreach ($ag->historico as $h)
                    <li>
                        <span class="absolute -left-1.5 mt-1 h-3 w-3 rounded-full border-2 border-white bg-luna-gold"></span>
                        <p class="text-xs text-gray-600">{{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->user?->nome ?? 'Cliente (site)' }}</p>
                        <p>
                            @if ($h->status_anterior && $h->status_anterior !== $h->status_novo)
                                {{ \App\Enums\StatusAgendamento::from($h->status_anterior)->label() }} → {{ \App\Enums\StatusAgendamento::from($h->status_novo)->label() }}.
                            @endif
                            {{ $h->observacao }}
                        </p>
                    </li>
                @endforeach
            </ol>
        </x-card>
    </div>

    <div class="space-y-6">
        @if ($user->isAdmin())
            <x-card titulo="Observações internas">
                <form method="POST" action="{{ route('admin.agendamentos.update', $ag) }}" class="space-y-3">
                    @csrf @method('PATCH')
                    <textarea name="observacoes_internas" class="campo" rows="3">{{ old('observacoes_internas', $ag->observacoes_internas) }}</textarea>
                    @if ($ag->status !== S::Concluido)
                        <div><label class="rotulo" for="preco_previsto">Preço previsto</label><input id="preco_previsto" name="preco_previsto" class="campo" value="{{ number_format($ag->preco_previsto, 2, ',', '') }}" inputmode="decimal"></div>
                    @endif
                    <x-button tamanho="sm">Salvar</x-button>
                </form>
            </x-card>
        @endif

        <x-card titulo="Ficha técnica">
            <form method="POST" action="{{ route($prefixo . 'agendamentos.anotacao', $ag) }}" class="space-y-2">
                @csrf
                <textarea name="anotacao" class="campo" rows="2" placeholder="Ex.: Descoloração 30vol + matizador 9.1" required></textarea>
                <x-button tamanho="sm">Adicionar anotação</x-button>
            </form>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($ag->anotacoes as $n)
                    <li class="border-l-2 border-luna-nude pl-3"><span class="whitespace-pre-line">{{ $n->anotacao }}</span><br><span class="text-xs text-gray-500">{{ $n->created_at->format('d/m/Y') }}</span></li>
                @endforeach
            </ul>
        </x-card>
    </div>
</div>

{{-- Modais --}}
@can('concluir', $ag)
    <x-modal name="concluir" :show="$errors->has('valor_cobrado') || $errors->has('pagamentos') || $errors->has('pagamentos.*')" focusable>
        <form method="POST" action="{{ route($prefixo . 'agendamentos.concluir', $ag) }}" class="space-y-4 p-6"
              x-data="concluirAtendimento(@js(['valor' => number_format($ag->preco_previsto, 2, ',', '')]))">
            @csrf
            <h2 class="text-lg font-semibold">Concluir atendimento</h2>
            <div>
                <label class="rotulo" for="valor_cobrado">Valor cobrado (R$)</label>
                <input id="valor_cobrado" name="valor_cobrado" class="campo" inputmode="decimal" x-model="valor" @input="atualizarValor()" required>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="rotulo" for="forma1">Forma de pagamento</label>
                    <select id="forma1" name="pagamentos[0][forma]" class="campo" x-model="forma1">
                        @foreach (\App\Enums\FormaPagamento::cases() as $f)<option value="{{ $f->value }}">{{ $f->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="rotulo" for="valor1">Valor</label>
                    <input id="valor1" name="pagamentos[0][valor]" class="campo" inputmode="decimal" x-model="valor1" :readonly="!dividir">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" x-model="dividir" class="rounded border-gray-300"> Dividir em duas formas de pagamento</label>
            <div class="grid gap-3 sm:grid-cols-2" x-show="dividir" x-cloak>
                <div>
                    <label class="rotulo" for="forma2">Segunda forma</label>
                    <select id="forma2" :name="dividir ? 'pagamentos[1][forma]' : ''" class="campo" x-model="forma2">
                        @foreach (\App\Enums\FormaPagamento::cases() as $f)<option value="{{ $f->value }}">{{ $f->label() }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="rotulo" for="valor2">Valor</label>
                    <input id="valor2" :name="dividir ? 'pagamentos[1][valor]' : ''" class="campo" inputmode="decimal" x-model="valor2">
                </div>
            </div>
            <p x-show="dividir && !confere" x-cloak class="text-sm text-red-700">A soma (<span x-text="dinheiro(soma)"></span>) precisa ser igual ao valor cobrado.</p>
            <div>
                <label class="rotulo" for="anotacao-concluir">Anotação técnica (opcional)</label>
                <textarea id="anotacao-concluir" name="anotacao" class="campo" rows="2" placeholder="Ex.: fórmula de coloração usada"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secundario" x-on:click="$dispatch('close')">Voltar</button>
                <button class="btn-primario" :disabled="!confere">Concluir</button>
            </div>
        </form>
    </x-modal>
@endcan

@can('cancelar', $ag)
    <x-modal name="cancelar" focusable>
        <form method="POST" action="{{ route($prefixo . 'agendamentos.cancelar', $ag) }}" class="space-y-4 p-6">
            @csrf
            <h2 class="text-lg font-semibold">Cancelar agendamento</h2>
            <x-select nome="cancelado_por" rotulo="Quem pediu" :opcoes="['cliente' => 'Cliente pediu', 'salao' => 'Salão']" />
            <x-input nome="motivo" rotulo="Motivo" placeholder="Ex.: imprevisto" />
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secundario" x-on:click="$dispatch('close')">Voltar</button>
                <button class="btn-perigo">Cancelar agendamento</button>
            </div>
        </form>
    </x-modal>
@endcan

@can('estornar', $ag)
    <x-modal name="estornar" focusable>
        <form method="POST" action="{{ route('admin.agendamentos.estornar', $ag) }}" class="space-y-4 p-6">
            @csrf
            <h2 class="text-lg font-semibold">Estornar atendimento</h2>
            <p class="text-sm text-gray-700">Os pagamentos e a comissão serão removidos e o agendamento volta para “Confirmado”. Os totais da cliente são recalculados.</p>
            <x-input nome="motivo" rotulo="Motivo" />
            <div class="flex justify-end gap-2">
                <button type="button" class="btn-secundario" x-on:click="$dispatch('close')">Voltar</button>
                <button class="btn-perigo">Estornar</button>
            </div>
        </form>
    </x-modal>
@endcan
