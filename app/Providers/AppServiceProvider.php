<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('administrar-usuarios', function (User $user) {
            $user->loadMissing('rol');

            return $user->activo
                && $user->rol
                && $user->rol->activo
                && $user->rol->nombre === 'Administrador';
        });
    }
}