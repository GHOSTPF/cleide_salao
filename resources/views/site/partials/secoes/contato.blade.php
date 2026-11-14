@php $instagram = ltrim($config['instagram'], '@'); @endphp
<section id="contato" class="bg-luna-ink py-20 text-white sm:py-28">
    <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 md:grid-cols-2 md:gap-16">
        <div>
            <p class="font-serif text-lg italic text-luna-nude">Visite-nos</p>
            <h2 class="mt-2 font-serif text-3xl leading-tight sm:text-[2.75rem]">Onde estamos</h2>
            <dl class="mt-8 space-y-6 text-sm">
                <div>
                    <dt class="text-white/50">Endereço</dt>
                    <dd class="mt-1 text-base">{{ $config['endereco'] }}</dd>
                    @if ($config['link_maps'])
                        <dd class="mt-1"><a href="{{ $config['link_maps'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-luna-nude underline decoration-white/30 underline-offset-2 hover:text-white"><x-icone nome="pin" class="w-4 h-4" /> Abrir no mapa</a></dd>
                    @endif
                </div>
                <div>
                    <dt class="text-white/50">Horário</dt>
                    <dd class="mt-1 text-base">{{ $config['horario_funcionamento'] }}</dd>
                </div>
                <div class="flex flex-wrap gap-x-10 gap-y-6">
                    <div>
                        <dt class="text-white/50">WhatsApp</dt>
                        <dd class="mt-1"><a href="{{ $linkWhatsapp }}" target="_blank" rel="noopener" class="text-base tabular-nums hover:text-luna-nude">{{ \App\Support\Telefone::formatar($config['whatsapp_salao']) }}</a></dd>
                    </div>
                    <div>
                        <dt class="text-white/50">Instagram</dt>
                        <dd class="mt-1"><a href="https://instagram.com/{{ $instagram }}" target="_blank" rel="noopener" class="text-base hover:text-luna-nude">{{ '@' . $instagram }}</a></dd>
                    </div>
                </div>
            </dl>
        </div>

        <div class="flex flex-col justify-end border-t border-white/15 pt-10 md:border-l md:border-t-0 md:pl-16 md:pt-0">
            <p class="font-serif text-3xl leading-snug">Marque seu horário pelo site ou fale com a gente no WhatsApp.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="#agendar" class="btn bg-luna-cream px-7 py-3 text-luna-ink hover:bg-white">Agendar pelo site</a>
                <a href="{{ $linkWhatsapp }}" target="_blank" rel="noopener" class="btn border border-white/30 px-7 py-3 text-white hover:border-white"><x-icone nome="whatsapp" class="w-4 h-4" /> WhatsApp</a>
            </div>
        </div>
    </div>
</section>
