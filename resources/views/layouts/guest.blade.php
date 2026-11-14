<!DOCTYPE html>
<html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex">
        <title>Área da equipe — {{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('images/logo.png') }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="site bg-luna-cream text-luna-ink antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('home') }}" class="text-center">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="mx-auto h-20 w-20 rounded-full">
                <p class="mt-3 font-serif text-2xl">Área da equipe</p>
            </a>
            <div class="card mt-6 w-full max-w-md p-6 sm:p-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
