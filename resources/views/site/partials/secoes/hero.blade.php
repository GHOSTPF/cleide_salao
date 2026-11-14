@php
    use App\Support\FotoSite;
    $instagram = ltrim($config['instagram'], '@');

    // Fotos do carrossel do topo: fotos reais do painel (loiros primeiro), depois a do topo e as da galeria
    // definidas em config/fotos_site.php. Mesma moldura de antes; só a imagem troca sozinha.
    $reais = $galeria->reject(fn ($f) => str_contains($f->url, '/placeholders/'))->sortBy(fn ($f) => $f->categoria === 'loiros' ? 0 : 1);
    $slidesHero = $reais->map(fn ($f) => ['src' => $f->url, 'srcset' => null, 'alt' => $f->titulo ?: 'Trabalho do ' . $config['nome_salao']])
        ->concat(collect([config('fotos_site.hero')])->concat(config('fotos_site.galeria', []))->map(fn ($f) => [
            'src' => FotoSite::url($f['url'], 1200), 'srcset' => FotoSite::srcset($f['url']), 'alt' => $f['alt'] ?? $f['titulo'] ?? '',
        ]))
        ->unique('src')->values();
@endphp
<section id="inicio" class="bg-luna-sand/40 pt-20 sm:pt-24">
    <div class="mx-auto grid max-w-6xl items-end gap-10 px-4 sm:px-6 md:grid-cols-[1.1fr_1fr] md:gap-14">
        <div class="pb-4 pt-8 md:pb-20 md:pt-16">
            <p class="sobretitulo">Salão de beleza em João Pessoa</p>
            <h1 class="mt-3 font-serif text-5xl leading-[1.05] sm:text-6xl lg:text-7xl">{{ $config['slogan'] }}</h1>
            <p class="mt-6 max-w-md leading-relaxed text-gray-700">
                Escolha o serviço e o horário aqui pelo site em um minuto. O salão confirma com você pelo WhatsApp.
            </p>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="#agendar" class="btn-primario px-7 py-3">Agende seu horário</a>
                <a href="https://instagram.com/{{ $instagram }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 px-2 py-3 text-sm text-gray-700 hover:text-luna-ink">
                    <x-icone nome="instagram" class="w-4 h-4" /> {{ '@' . $instagram }}
                </a>
            </div>

            <ul class="mt-10 flex flex-wrap gap-x-5 gap-y-2 border-t border-luna-nude/60 pt-5 text-sm text-luna-brown">
                @foreach ([['brilho', 'Loiros'], ['tesoura', 'Cortes'], ['unha', 'Nails'], ['espelho', 'Make'], ['olho', 'Sobrancelhas']] as [$icone, $rotulo])
                    <li class="flex items-center gap-1.5"><x-icone :nome="$icone" class="w-4 h-4 text-luna-gold" /> {{ $rotulo }}</li>
                @endforeach
            </ul>
        </div>

        <div class="relative -mx-4 sm:mx-0">
            {{-- Carrossel automático: troca a cada 5s com esmaecimento; pausa com a aba oculta e
                 fica parado na primeira foto para quem prefere menos movimento (prefers-reduced-motion). --}}
            <div class="relative aspect-[4/5] overflow-hidden bg-luna-nude sm:rounded-t-lg md:aspect-[3/4]"
                 x-data="{
                     atual: 0,
                     total: {{ $slidesHero->count() }},
                     init() {
                         if (this.total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                         setInterval(() => { if (! document.hidden) this.atual = (this.atual + 1) % this.total; }, 5000);
                     }
                 }">
                @foreach ($slidesHero as $i => $slide)
                    <img src="{{ $slide['src'] }}" @if ($slide['srcset']) srcset="{{ $slide['srcset'] }}" sizes="(min-width: 768px) 45vw, 100vw" referrerpolicy="no-referrer" @endif
                         alt="{{ $slide['alt'] }}"
                         class="absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out"
                         style="opacity: {{ $i === 0 ? 1 : 0 }}"
                         :style="{ opacity: atual === {{ $i }} ? 1 : 0 }"
                         :aria-hidden="(atual !== {{ $i }}).toString()"
                         @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                @endforeach
            </div>
        </div>
    </div>
</section>
