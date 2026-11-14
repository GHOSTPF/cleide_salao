<section id="equipe" class="border-y border-luna-sand bg-white py-20 sm:py-28">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <p class="sobretitulo">Quem cuida de você</p>
        <h2 class="titulo-secao mt-2">Equipe</h2>

        <div class="mt-12 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($equipe as $prof)
                <article class="revelar" x-data x-intersect.once="$el.classList.add('visivel')">
                    @if ($prof->foto_url)
                        <div class="aspect-[4/5] overflow-hidden rounded-md bg-luna-cream">
                            <img src="{{ $prof->foto_url }}" alt="{{ $prof->nome }}" class="h-full w-full object-cover" loading="lazy">
                        </div>
                        <h3 class="mt-4 font-serif text-2xl">{{ $prof->nome_exibicao }}</h3>
                    @else
                        {{-- Sem foto: inicial discreta ao lado do nome, sem um quadro vazio enorme --}}
                        <div class="flex items-center gap-4 border-t border-luna-ink pt-5">
                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-md bg-luna-cream font-serif text-3xl text-luna-gold" aria-hidden="true">{{ mb_substr($prof->nome_exibicao, 0, 1) }}</span>
                            <h3 class="font-serif text-2xl">{{ $prof->nome_exibicao }}</h3>
                        </div>
                    @endif
                    @if ($prof->bio)
                        <p class="mt-3 text-sm leading-relaxed text-gray-600">{{ $prof->bio }}</p>
                    @endif
                </article>
            @endforeach
        </div>
    </div>
</section>
