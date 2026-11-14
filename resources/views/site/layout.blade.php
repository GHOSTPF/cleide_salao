<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', \App\Support\Setting::get('nome_salao') . ' — ' . \App\Support\Setting::get('slogan'))</title>
    <meta name="description" content="@yield('descricao', 'Salão de beleza em João Pessoa/PB. Especialista em loiros e descoloração, cortes femininos, sobrancelhas, unhas e make. Agende online.')">
    <meta name="theme-color" content="#F4EEE6">
    <meta property="og:title" content="{{ \App\Support\Setting::get('nome_salao') }}">
    <meta property="og:description" content="{{ \App\Support\Setting::get('slogan') }}">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script>document.documentElement.classList.add('js');</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="site min-h-screen bg-luna-cream text-luna-ink">
    <a href="#conteudo" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:bg-white focus:px-4 focus:py-2 focus:rounded-md">Pular para o conteúdo</a>
    @yield('conteudo')
</body>
</html>
