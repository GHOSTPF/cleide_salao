<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso: ->middleware('role:admin') ou 'role:profissional'.
 * Usuários desativados durante a sessão são deslogados.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$papeis): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->ativo) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('auth.inactive')]);
        }

        if (! in_array($user->papel->value, $papeis, true)) {
            abort(403, 'Você não tem acesso a esta área.');
        }

        if ($user->isProfissional() && ! $user->profissional) {
            abort(403, 'Seu usuário não está vinculado a uma profissional. Fale com a administração.');
        }

        return $next($request);
    }
}
