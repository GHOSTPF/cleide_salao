@if ($depoimentos->isNotEmpty())
<section id="depoimentos" class="py-20 sm:py-28">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <p class="sobretitulo">Quem já veio</p>
        <h2 class="titulo-secao mt-2">O que as clientes dizem</h2>

        <div class="mt-12 flex snap-x snap-mandatory gap-8 overflow-x-auto pb-4 md:grid md:grid-cols-3 md:gap-10 md:overflow-visible">
            @foreach ($depoimentos as $d)
                <blockquote class="w-[85%] shrink-0 snap-start border-t border-luna-ink pt-5 md:w-auto">
                    <p class="font-serif text-xl leading-snug">“{{ $d->texto }}”</p>
                    <footer class="mt-5 flex items-center justify-between gap-3 text-sm">
                        <span class="text-gray-700">{{ $d->nome_cliente }}</span>
                        <span class="flex gap-px text-luna-gold" aria-label="Nota {{ $d->nota }} de 5">
                            @for ($i = 1; $i <= 5; $i++)
                                <x-icone nome="estrela" class="w-3.5 h-3.5 {{ $i <= $d->nota ? 'fill-current' : 'opacity-30' }}" />
                            @endfor
                        </span>
                    </footer>
                </blockquote>
            @endforeach
        </div>
    </div>
</section>
@endif
