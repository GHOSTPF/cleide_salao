@props(['titulo' => null, 'acoes' => null, 'padding' => 'p-5 sm:p-6'])
<section {{ $attributes->merge(['class' => 'card ' . $padding]) }}>
    @if ($titulo || $acoes)
        <header class="flex flex-wrap items-center justify-between gap-2 mb-4">
            @if ($titulo)
                <h2 class="font-serif text-xl text-luna-ink">{{ $titulo }}</h2>
            @endif
            @if ($acoes)
                <div class="flex flex-wrap gap-2">{{ $acoes }}</div>
            @endif
        </header>
    @endif
    {{ $slot }}
</section>
