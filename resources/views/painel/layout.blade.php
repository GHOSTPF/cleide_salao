@php
    $user = auth()->user();
    $menu = $user->isAdmin()
        ? [
            ['admin.dashboard', 'Dashboard', 'grafico', 'admin.dashboard'],
            ['admin.agenda', 'Agenda', 'calendario', 'admin.agenda'],
            ['admin.agendamentos.index', 'Agendamentos', 'lista', 'admin.agendamentos.*'],
            ['admin.pendentes', 'Pendentes', 'sino', 'admin.pendentes'],
            ['admin.clientes.index', 'Clientes', 'usuarios', 'admin.clientes.*'],
            ['admin.relacionamento.retornos', 'Relacionamento', 'coracao', 'admin.relacionamento.*'],
            ['admin.financeiro.index', 'Financeiro', 'dinheiro', 'admin.financeiro.*'],
            ['admin.servicos.index', 'Serviços', 'tesoura', 'admin.servicos.*|admin.categorias.*'],
            ['admin.profissionais.index', 'Profissionais', 'usuario', 'admin.profissionais.*'],
            ['admin.feriados.index', 'Feriados', 'cadeado', 'admin.feriados.*'],
            ['admin.site.configuracoes', 'Site', 'imagem', 'admin.site.*'],
            ['admin.atividades', 'Log de atividades', 'historico', 'admin.atividades'],
        ]
        : array_values(array_filter([
            ['profissional.agenda', 'Minha agenda', 'calendario', 'profissional.agenda|profissional.agendamentos.*'],
            ['profissional.clientes.index', 'Minhas clientes', 'usuarios', 'profissional.clientes.*'],
            ['profissional.resumo', 'Meu resumo', 'grafico', 'profissional.resumo'],
            \App\Support\Setting::bool('profissional_gerencia_bloqueios') ? ['profissional.bloqueios.index', 'Meus bloqueios', 'cadeado', 'profissional.bloqueios.*'] : null,
        ]));
    $ativo = fn ($padrao) => collect(explode('|', $padrao))->contains(fn ($p) => request()->routeIs($p));
    $inferior = array_slice($menu, 0, 4);
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>@yield('titulo', 'Painel') — {{ \App\Support\Setting::get('nome_salao') }}</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="painel bg-[#F7F3EE] text-luna-ink" x-data="{ menu: false }">
    {{-- Sidebar (desktop) / gaveta (mobile) --}}
    <aside class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col bg-luna-ink text-white transition-transform lg:translate-x-0"
           :class="menu ? 'translate-x-0' : '-translate-x-full'" aria-label="Menu do painel">
        <div class="flex items-center gap-3 border-b border-white/10 px-5 py-4">
            <img src="{{ asset('images/logo.png') }}" alt="" class="h-9 w-9 rounded-md bg-white p-0.5">
            <div class="min-w-0">
                <p class="truncate font-serif text-lg leading-tight">{{ \App\Support\Setting::get('nome_salao') }}</p>
                <p class="text-xs text-white/50">{{ $user->papel->label() }}</p>
            </div>
        </div>
        <nav class="flex-1 space-y-px overflow-y-auto px-2 py-3">
            @foreach ($menu as [$rota, $rotulo, $icone, $padrao])
                <a href="{{ route($rota) }}" @class([
                    'flex items-center gap-3 rounded-md border-l-2 px-3 py-2 text-sm transition-colors',
                    'border-luna-gold bg-white/[0.07] text-white' => $ativo($padrao),
                    'border-transparent text-white/65 hover:bg-white/[0.04] hover:text-white' => ! $ativo($padrao),
                ]) @if ($ativo($padrao)) aria-current="page" @endif>
                    <x-icone :nome="$icone" :class="$ativo($padrao) ? 'w-[18px] h-[18px] text-luna-gold' : 'w-[18px] h-[18px] text-white/45'" /> {{ $rotulo }}
                </a>
            @endforeach
        </nav>
        <div class="border-t border-white/10 px-2 py-3 text-sm">
            <a href="{{ route('perfil.edit') }}" class="flex items-center gap-3 rounded-md px-3 py-2 text-white/65 hover:bg-white/[0.04] hover:text-white">
                <x-icone nome="config" class="w-[18px] h-[18px] text-white/45" /> Meu perfil
            </a>
            <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-md px-3 py-2 text-white/65 hover:bg-white/[0.04] hover:text-white">
                <x-icone nome="casa" class="w-[18px] h-[18px] text-white/45" /> Ver site
            </a>
        </div>
    </aside>
    <div x-show="menu" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="menu = false"></div>

    <div class="lg:pl-60">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-luna-sand bg-[#F7F3EE]/95 px-4 py-2.5 backdrop-blur sm:px-8">
            <div class="flex min-w-0 items-center gap-2">
                <button type="button" class="-ml-1.5 rounded-md p-1.5 hover:bg-luna-sand/50 lg:hidden" @click="menu = true" aria-label="Abrir menu"><x-icone nome="menu" class="w-5 h-5" /></button>
                <h1 class="truncate text-lg font-semibold tracking-tight">@yield('titulo', 'Painel')</h1>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-gray-600 sm:inline">{{ $user->nome }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-gray-600 hover:bg-luna-sand/50 hover:text-luna-ink" type="submit"><x-icone nome="sair" class="w-4 h-4" /> Sair</button>
                </form>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 pb-24 sm:px-8 lg:pb-10">
            @if (session('sucesso'))
                <div class="mb-5 flex items-start gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">
                    <x-icone nome="check-circulo" class="mt-px w-5 h-5 shrink-0" />
                    <div>
                        {{ session('sucesso') }}
                        @if (session('link_whatsapp'))
                            <a href="{{ session('link_whatsapp') }}" target="_blank" rel="noopener" class="btn-whatsapp btn-sm ml-2 mt-1">
                                <x-icone nome="whatsapp" class="w-4 h-4" /> {{ session('link_whatsapp_rotulo', 'Avisar cliente no WhatsApp') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif
            @if (session('erro'))
                <div class="mb-5 flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    <x-icone nome="alerta" class="mt-px w-5 h-5 shrink-0" /> {{ session('erro') }}
                </div>
            @endif
            @if ($errors->any() && ! isset($semErrosGerais))
                <div class="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900" role="alert">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $erro)<li>{{ $erro }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @yield('conteudo')
        </main>
    </div>

    {{-- Menu inferior (mobile) --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 grid border-t border-luna-sand bg-white lg:hidden" style="grid-template-columns: repeat({{ count($inferior) + 1 }}, minmax(0, 1fr));" aria-label="Atalhos">
        @foreach ($inferior as [$rota, $rotulo, $icone, $padrao])
            <a href="{{ route($rota) }}" class="-mt-px flex flex-col items-center gap-0.5 border-t-2 py-2 text-[11px] {{ $ativo($padrao) ? 'border-luna-ink font-medium text-luna-ink' : 'border-transparent text-gray-500' }}">
                <x-icone :nome="$icone" class="w-5 h-5" /> {{ \Illuminate\Support\Str::limit($rotulo, 12, '') }}
            </a>
        @endforeach
        <button type="button" @click="menu = true" class="-mt-px flex flex-col items-center gap-0.5 border-t-2 border-transparent py-2 text-[11px] text-gray-500">
            <x-icone nome="menu" class="w-5 h-5" /> Mais
        </button>
    </nav>
    @stack('scripts')
</body>
</html>
