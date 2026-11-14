{{-- Serviços em formato de "menu" de salão: nome, descrição, duração e preço em linha --}}
<section id="servicos" class="border-y border-luna-sand bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="max-w-xl">
            <p class="sobretitulo">O que fazemos</p>
            <h2 class="titulo-secao mt-2">Serviços</h2>
            <p class="mt-4 text-gray-600">Nos serviços que dependem do seu cabelo, valor e tempo são confirmados numa avaliação rápida pelo WhatsApp.</p>
        </div>

        <div class="mt-14 grid gap-x-16 gap-y-14 lg:grid-cols-2">
            @foreach ($categorias as $categoria)
                <div class="revelar" x-data x-intersect.once="$el.classList.add('visivel')">
                    <h3 class="border-b border-luna-ink pb-2 font-serif text-2xl">{{ $categoria->nome }}</h3>
                    <ul>
                        @foreach ($categoria->servicos as $servico)
                            <li class="flex gap-4 border-b border-luna-sand py-5">
                                @if ($servico->foto_url)
                                    <img src="{{ $servico->foto_url }}" alt="" class="h-16 w-16 shrink-0 rounded-md object-cover" loading="lazy">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline gap-3">
                                        <h4 class="font-medium">{{ $servico->nome }}</h4>
                                        <span class="mb-1 flex-1 border-b border-dotted border-luna-nude" aria-hidden="true"></span>
                                        <span class="whitespace-nowrap font-medium tabular-nums text-luna-brown">{{ $servico->preco_formatado }}</span>
                                    </div>
                                    @if ($servico->descricao)
                                        <p class="mt-1 text-sm leading-relaxed text-gray-600">{{ $servico->descricao }}</p>
                                    @endif
                                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                                        <span class="inline-flex items-center gap-1"><x-icone nome="relogio" class="w-3.5 h-3.5" /> {{ $servico->duracao_formatada }}</span>
                                        @if ($servico->requer_avaliacao)
                                            <span class="text-luna-brown">· valor confirmado após avaliação</span>
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-14 flex flex-col items-start gap-3 sm:flex-row sm:items-center">
            <a href="#agendar" class="btn-primario px-7 py-3">Reserve sua data</a>
            <a href="{{ $linkWhatsapp }}" target="_blank" rel="noopener" class="link px-2 text-sm">Tirar dúvidas no WhatsApp</a>
        </div>
    </div>
</section>
