<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('pt_BR');

        // Detecta N+1 em desenvolvimento
        Model::preventLazyLoading($this->app->environment('local'));

        // Áreas exclusivas da administradora (financeiro, configurações, mesclagem, estornos...)
        Gate::define('admin', fn (User $user) => $user->isAdmin());
    }
}
