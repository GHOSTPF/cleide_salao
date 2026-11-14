<nav class="abas mb-6 overflow-x-auto" aria-label="Relacionamento">
    @foreach ([
        'admin.relacionamento.retornos' => 'Retornos',
        'admin.relacionamento.aniversariantes' => 'Aniversariantes',
        'admin.relacionamento.inativas' => 'Inativas',
        'admin.relacionamento.lista-espera' => 'Lista de espera',
    ] as $rota => $rotulo)
        <a href="{{ route($rota) }}" class="whitespace-nowrap" @if (request()->routeIs($rota)) aria-current="page" @endif>{{ $rotulo }}</a>
    @endforeach
</nav>
