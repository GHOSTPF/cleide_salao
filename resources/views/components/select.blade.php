@props(['nome', 'rotulo' => null, 'opcoes' => [], 'valor' => null, 'vazio' => null])
<div>
    @if ($rotulo)
        <label for="{{ $attributes->get('id', $nome) }}" class="rotulo">{{ $rotulo }}</label>
    @endif
    <select name="{{ $nome }}" id="{{ $attributes->get('id', $nome) }}" {{ $attributes->merge(['class' => 'campo']) }}>
        @if ($vazio !== null)
            <option value="">{{ $vazio }}</option>
        @endif
        @foreach ($opcoes as $chave => $texto)
            <option value="{{ $chave }}" @selected((string) old($nome, $valor) === (string) $chave)>{{ $texto }}</option>
        @endforeach
    </select>
    @error($nome)
        <p class="mt-1 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
