{{-- Pílula de horário (usada no Alpine: :class controla o estado selecionado) --}}
<button type="button" {{ $attributes->merge(['class' => 'pilula']) }}>{{ $slot }}</button>
