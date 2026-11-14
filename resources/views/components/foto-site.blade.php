{{--
    Foto de destaque do site (topo / sobre): usa a foto real da galeria do painel na categoria configurada
    quando existir; senão, a definida em config/fotos_site.php.
--}}
@props(['chave', 'galeria', 'class' => '', 'sizes' => '(min-width: 768px) 50vw, 100vw', 'eager' => false])
@php
    use App\Support\FotoSite;
    $cfg = config("fotos_site.$chave");
    $fotoReal = $galeria->first(fn ($f) => $f->categoria === $cfg['categoria'] && ! str_contains($f->url, '/placeholders/'));
@endphp
@if ($fotoReal)
    <img src="{{ $fotoReal->url }}" alt="{{ $fotoReal->titulo ?: $cfg['alt'] }}" class="{{ $class }}" @unless ($eager) loading="lazy" @endunless>
@else
    <img src="{{ FotoSite::url($cfg['url'], 1200) }}" @if ($srcset = FotoSite::srcset($cfg['url'])) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
         alt="{{ $cfg['alt'] }}" class="{{ $class }}"
         @if ($eager) fetchpriority="high" @else loading="lazy" @endif referrerpolicy="no-referrer">
@endif
