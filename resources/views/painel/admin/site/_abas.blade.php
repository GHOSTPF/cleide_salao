<nav class="abas mb-6 overflow-x-auto" aria-label="Site">
    @foreach (['admin.site.configuracoes' => 'Dados do salão e regras', 'admin.site.galeria' => 'Galeria', 'admin.site.depoimentos' => 'Depoimentos'] as $rota => $rotulo)
        <a href="{{ route($rota) }}" class="whitespace-nowrap" @if (request()->routeIs($rota)) aria-current="page" @endif>{{ $rotulo }}</a>
    @endforeach
</nav>
