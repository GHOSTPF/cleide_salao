@php
    $configForm = [
        'servicos' => $servicosJs,
        'urlHorarios' => route('horarios'),
        'antigo' => [
            'servico_id' => old('servico_id'),
            'profissional_id' => old('profissional_id'),
            'data' => old('data'),
            'hora' => old('hora'),
        ],
    ];
@endphp
<section id="agendar" class="scroll-mt-16 border-t border-luna-sand bg-luna-sand/40 py-20 sm:py-28">
    <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-[1fr_1.15fr] lg:gap-20">
        <div class="lg:sticky lg:top-24 lg:self-start">
            <p class="sobretitulo">Agendamento online</p>
            <h2 class="titulo-secao mt-2">Agende seu horário</h2>
            <p class="mt-4 max-w-md text-gray-700">Leva um minuto. Você escolhe e o salão confirma pelo WhatsApp.</p>
            <ol class="mt-8 max-w-md space-y-5 text-sm">
                @foreach ([
                    ['Escolha serviço, dia e horário', 'Os horários mostrados já estão livres na agenda.'],
                    ['Envie a mensagem pronta', 'No fim, abrimos o WhatsApp com seu pedido escrito.'],
                    ['Pronto, é só confirmar', 'O salão responde e seu horário fica garantido.'],
                ] as $i => [$titulo, $texto])
                    <li class="flex gap-4">
                        <span class="w-5 shrink-0 pt-px text-sm font-semibold tabular-nums text-luna-gold">{{ sprintf('%02d', $i + 1) }}</span>
                        <span><span class="block font-medium">{{ $titulo }}</span><span class="text-gray-600">{{ $texto }}</span></span>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="card bg-white p-6 sm:p-8" x-data="formAgendamento(@js($configForm))">
            @if (session('lista_espera_ok'))
                <div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('lista_espera_ok') }}</div>
            @endif

            @error('agendamento')
                <div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    <p class="flex items-start gap-2"><x-icone nome="alerta" class="w-5 h-5 shrink-0" /> {{ $message }}</p>
                    @if (session('sugerir_whatsapp'))
                        <a href="{{ $linkWhatsapp }}" target="_blank" rel="noopener" class="btn-whatsapp mt-3">
                            <x-icone nome="whatsapp" class="w-4 h-4" /> Falar no WhatsApp
                        </a>
                    @endif
                </div>
            @enderror

            <form method="POST" action="{{ route('agendar') }}" class="space-y-5" x-ref="form" novalidate>
                @csrf
                {{-- Honeypot: invisível para pessoas --}}
                <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                    <label for="website">Não preencha este campo</label>
                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                </div>

                <x-input nome="nome" rotulo="Nome" placeholder="Seu nome completo" autocomplete="name" required maxlength="120" />

                <x-input nome="telefone" rotulo="WhatsApp" tipo="tel" placeholder="(83) 99999-9999" autocomplete="tel-national"
                         inputmode="numeric" x-mask="(99) 99999-9999" required />

                <div>
                    <label for="servico_id" class="rotulo">Tipo de serviço</label>
                    <select name="servico_id" id="servico_id" class="campo" x-model="servicoId" required>
                        <option value="">Selecione o serviço</option>
                        @foreach ($categorias as $categoria)
                            <optgroup label="{{ $categoria->nome }}">
                                @foreach ($categoria->servicos as $s)
                                    <option value="{{ $s->id }}">{{ $s->nome }} · {{ $s->duracao_formatada }} · {{ $s->preco_formatado }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('servico_id') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                    <p x-show="servico && servico.requer_avaliacao" x-cloak class="mt-2 rounded-md border-l-2 border-luna-gold bg-luna-cream px-3 py-2 text-xs text-luna-brown">
                        Este serviço requer avaliação: o salão confirma o valor e o tempo pelo WhatsApp.
                    </p>
                </div>

                <div>
                    <label for="profissional_id" class="rotulo">Profissional</label>
                    <select name="profissional_id" id="profissional_id" class="campo" x-model="profissionalId" :disabled="!servico">
                        <option value="">Sem preferência</option>
                        <template x-for="p in profissionais" :key="p.id">
                            <option :value="String(p.id)" x-text="p.nome" :selected="String(p.id) === profissionalId"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label for="data" class="rotulo">Data</label>
                    <input type="date" name="data" id="data" class="campo" x-model="data" min="{{ $dataMinima }}" max="{{ $dataMaxima }}" required>
                    @error('data') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <fieldset>
                    <legend class="rotulo">Horário</legend>
                    <input type="hidden" name="hora" :value="hora">

                    <p x-show="!servicoId || !data" class="text-sm text-gray-600">Escolha o serviço e a data para ver os horários.</p>

                    <div x-show="carregando" x-cloak class="flex items-center gap-2 text-sm text-gray-600" role="status">
                        <x-icone nome="carregando" class="h-4 w-4 animate-spin text-luna-gold" />
                        Carregando horários...
                    </div>

                    <p x-show="erroSlots" x-cloak x-text="erroSlots" class="text-sm text-red-700"></p>

                    <div x-show="slots.length" x-cloak class="flex flex-wrap gap-2">
                        <template x-for="s in slots" :key="s.hora">
                            <button type="button" class="pilula" x-text="s.hora" @click="escolher(s.hora)" :class="hora === s.hora && 'pilula-ativa'" :aria-pressed="(hora === s.hora).toString()"></button>
                        </template>
                    </div>

                    <div x-show="semHorarios" x-cloak class="rounded-md border border-luna-sand bg-luna-cream p-4 text-sm">
                        <p class="font-medium">Nenhum horário disponível nesta data.</p>
                        <p class="mt-1 text-gray-700">Tente outro dia ou entre na lista de espera — se abrir uma vaga, avisamos você.</p>
                        <button type="button" class="btn-secundario btn-sm mt-3" @click="mostrarEspera = true">
                            <x-icone nome="ampulheta" class="w-4 h-4" /> Entrar na lista de espera
                        </button>
                    </div>
                    @error('hora') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </fieldset>

                <x-input nome="observacoes" rotulo="Observações (opcional)" tipo="textarea" placeholder="Ex.: quero clarear 2 tons" maxlength="500" />

                <div x-show="mostrarEspera" x-cloak class="space-y-3 rounded-md border border-luna-nude p-4">
                    <p class="text-sm font-medium">Lista de espera</p>
                    <label for="periodo_preferido" class="rotulo">Período preferido</label>
                    <select name="periodo_preferido" id="periodo_preferido" class="campo">
                        <option value="qualquer">Qualquer horário</option>
                        <option value="manha">Manhã</option>
                        <option value="tarde">Tarde</option>
                    </select>
                    <button type="submit" formaction="{{ route('lista-espera') }}" class="btn-primario w-full">Quero entrar na lista de espera</button>
                    <p class="text-xs text-gray-600">Usaremos o nome e o WhatsApp informados acima.</p>
                </div>

                <div>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="consentimento" value="1" class="mt-0.5 rounded border-gray-300 text-luna-ink focus:ring-luna-gold" @checked(old('consentimento')) required>
                        <span>Autorizo o {{ $config['nome_salao'] }} a usar meus dados para contato sobre meus agendamentos.</span>
                    </label>
                    @error('consentimento') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn-primario w-full py-3.5 text-base" :disabled="!podeEnviar">
                    Reserve sua data
                </button>
                <p class="text-center text-xs text-gray-600">Seu pedido fica registrado e o salão confirma pelo WhatsApp.</p>
            </form>
        </div>
    </div>
</section>
