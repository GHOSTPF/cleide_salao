@php
    use App\Support\FotoSite;
    // Fotos enviadas pelo painel (ignorando as provisórias do seed) + as definidas em config/fotos_site.php
    $fotosGaleria = $galeria
        ->reject(fn ($f) => str_contains($f->url, '/placeholders/'))
        ->map(fn ($f) => ['categoria' => $f->categoria, 'titulo' => $f->titulo, 'src' => $f->url, 'srcset' => null])
        ->concat(collect(config('fotos_site.galeria', []))->map(fn ($f) => [
            'categoria' => $f['categoria'], 'titulo' => $f['titulo'] ?? null,
            'src' => FotoSite::url($f['url'], 900), 'srcset' => FotoSite::srcset($f['url']),
        ]))
        ->values();
    $categoriasGaleria = $fotosGaleria->pluck('categoria')->unique()->values();
@endphp
<section id="galeria" class="py-20 sm:py-28" x-data="{ filtro: 'todas' }">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-6">
            <div>
                <p class="sobretitulo">Portfólio</p>
                <h2 class="titulo-secao mt-2">Trabalhos recentes</h2>
            </div>
            <a href="https://instagram.com/{{ ltrim($config['instagram'], '@') }}" target="_blank" rel="noopener" class="link inline-flex items-center gap-1.5 text-sm">
                <x-icone nome="instagram" class="w-4 h-4" /> Mais no Instagram
            </a>
        </div>

        @if ($categoriasGaleria->count() > 1)
            <div class="abas mt-8 overflow-x-auto" role="group" aria-label="Filtrar galeria">
                <button type="button" :aria-pressed="(filtro === 'todas').toString()" @click="filtro = 'todas'">Todos</button>
                @foreach ($categoriasGaleria as $cat)
                    <button type="button" :aria-pressed="(filtro === '{{ $cat }}').toString()" @click="filtro = '{{ $cat }}'">
                        {{ \App\Models\GaleriaFoto::CATEGORIAS[$cat] ?? ucfirst($cat) }}
                    </button>
                @endforeach
            </div>
        @endif

        <div class="mt-8 grid grid-cols-2 gap-2 sm:gap-3 md:grid-cols-3 lg:grid-cols-4">
            @forelse ($fotosGaleria as $foto)
                <figure x-show="filtro === 'todas' || filtro === '{{ $foto['categoria'] }}'" x-transition.opacity>
                    <div class="group aspect-[4/5] overflow-hidden rounded-md bg-luna-sand">
                        <img src="{{ $foto['src'] }}" @if ($foto['srcset']) srcset="{{ $foto['srcset'] }}" sizes="(min-width: 1024px) 25vw, (min-width: 768px) 33vw, 50vw" referrerpolicy="no-referrer" @endif
                             alt="{{ $foto['titulo'] ?: 'Trabalho do salão' }}" loading="lazy"
                             class="h-full w-full object-cover transition duration-700 group-hover:scale-[1.03]">
                    </div>
                    @if ($foto['titulo'])
                        <figcaption class="mt-1.5 text-sm text-gray-600">{{ $foto['titulo'] }}</figcaption>
                    @endif
                </figure>
            @empty
                <p class="col-span-full text-gray-600">Em breve, nossos trabalhos por aqui.</p>
            @endforelse
        </div>
    </div>
</section>
