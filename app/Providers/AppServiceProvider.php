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
        /*
         * Solo Administrador.
         *
         * Utilizado para el mantenimiento
         * de usuarios.
         */
        Gate::define('administrar-usuarios', function (User $user) {
            $user->loadMissing('rol');

            return $user->activo
                && $user->rol
                && $user->rol->activo
                && $user->rol->nombre === 'Administrador';
        });

        /*
         * AQ-70:
         * Administración del contenido de la landing pública.
         *
         * Disponible únicamente para el Administrador.
         */
        Gate::define('administrar-landing', function (User $user) {
            $user->loadMissing('rol');

            return $user->activo
                && $user->rol
                && $user->rol->activo
                && $user->rol->nombre === 'Administrador';
        });

        /*
         * AQ-68:
         * Módulos disponibles únicamente para
         * Administrador y Secretaria.
         */
        Gate::define(
            'ver-modulos-administrativos',
            function (User $user) {
                $user->loadMissing('rol');

                return $user->activo
                    && $user->rol
                    && $user->rol->activo
                    && in_array(
                        $user->rol->nombre,
                        ['Administrador', 'Secretaria'],
                        true
                    );
            }
        );
    }
}