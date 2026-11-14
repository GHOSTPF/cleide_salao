{{--
    Faixa de números lado a lado, separados por linhas finas.
    :itens = [['rotulo' => 'Entradas', 'valor' => 'R$ 10', 'sub' => '3 atendimentos', 'cor' => 'text-emerald-800'], ...]
    :colunas = colunas em telas grandes (2 a 6). No celular são sempre 2.
--}}
@props(['itens', 'colunas' => 4, 'tamanho' => 'text-2xl'])
@php
    $grade = [2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5', 6 => 'lg:grid-cols-6'][$colunas] ?? 'lg:grid-cols-4';
@endphp
<dl {{ $attributes->merge(['class' => "grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-luna-sand bg-luna-sand $grade"]) }}>
    @foreach ($itens as $item)
        <div class="bg-white p-4 sm:p-5">
            <dt class="legenda">{{ $item['rotulo'] }}</dt>
            <dd class="mt-1 {{ $tamanho }} font-semibold tabular-nums {{ $item['cor'] ?? '' }}">{{ $item['valor'] }}</dd>
            @if (! empty($item['sub']))<dd class="mt-0.5 text-xs text-gray-500">{{ $item['sub'] }}</dd>@endif
        </div>
    @endforeach
    {{-- completa a última linha para não sobrar buraco colorido --}}
    @for ($i = count($itens) % $colunas; $i > 0 && $i < $colunas; $i++)<div class="hidden bg-white lg:block"></div>@endfor
    @if (count($itens) % 2)<div class="bg-white lg:hidden"></div>@endif
</dl>
