<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // Usuário já logado acessando /login vai para a área do seu papel
        $middleware->redirectUsersTo(fn ($request) => $request->user()?->rotaInicial() ?? '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
