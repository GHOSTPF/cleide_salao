<ul class="divide-y divide-luna-sand text-sm">
    @forelse ($bloqueios as $b)
        <li class="flex items-center justify-between gap-2 py-2.5">
            <span>
                <span class="font-medium">{{ $b->inicio->format('d/m/Y H:i') }} → {{ $b->fim->format('d/m/Y H:i') }}</span><br>
                <span class="text-gray-600">{{ $b->motivo ?: 'Sem motivo' }}{{ isset($mostrarProfissional) && $b->profissional ? ' · ' . $b->profissional->nome_exibicao : '' }}</span>
            </span>
            <form method="POST" action="{{ route($rotaExcluir, $b) }}" onsubmit="return confirm('Remover este bloqueio?')">@csrf @method('DELETE')
                <button class="btn-secundario btn-sm" aria-label="Remover"><x-icone nome="lixo" class="w-4 h-4" /></button>
            </form>
        </li>
    @empty
        <li class="py-3 text-gray-600">Nenhum bloqueio.</li>
    @endforelse
</ul>
