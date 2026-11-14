@props(['nome', 'rotulo' => null, 'tipo' => 'text', 'valor' => null, 'ajuda' => null])
<div>
    @if ($rotulo)
        <label for="{{ $attributes->get('id', $nome) }}" class="rotulo">{{ $rotulo }}</label>
    @endif
    @if ($tipo === 'textarea')
        <textarea name="{{ $nome }}" id="{{ $attributes->get('id', $nome) }}" {{ $attributes->merge(['class' => 'campo', 'rows' => 3]) }}>{{ old($nome, $valor) }}</textarea>
    @else
        <input type="{{ $tipo }}" name="{{ $nome }}" id="{{ $attributes->get('id', $nome) }}" value="{{ $tipo === 'password' ? '' : old($nome, $valor) }}" {{ $attributes->merge(['class' => 'campo']) }}>
    @endif
    @if ($ajuda)
        <p class="mt-1 text-xs text-gray-600">{{ $ajuda }}</p>
    @endif
    @error($nome)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
