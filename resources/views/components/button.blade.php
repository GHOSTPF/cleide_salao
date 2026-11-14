@props(['variante' => 'primario', 'href' => null, 'tamanho' => null, 'icone' => null])
@php
    $classes = match ($variante) {
        'secundario' => 'btn-secundario',
        'perigo' => 'btn-perigo',
        'whatsapp' => 'btn-whatsapp',
        default => 'btn-primario',
    } . ($tamanho === 'sm' ? ' btn-sm' : '');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icone)<x-icone :nome="$icone" class="w-4 h-4" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $classes]) }}>
        @if ($icone)<x-icone :nome="$icone" class="w-4 h-4" />@endif
        {{ $slot }}
    </button>
@endif
