<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AutenticacionController extends Controller
{
    /**
     * Envía al visitante al login o al módulo operativo correspondiente.
     */
    public function inicio(Request $request)
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        return redirect()->route($this->rutaInicial($request));
    }

    /**
     * Muestra el formulario de inicio de sesión.
     */
    public function mostrarLogin()
    {
        return view('autenticacion.login');
    }

    /**
     * Valida las credenciales e inicia la sesión del usuario.
     */
    public function iniciarSesion(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ]);

        /*
         * Además del correo y contraseña, verificamos que
         * el usuario esté activo en el sistema.
         */
        if (Auth::attempt([
            'email' => $credenciales['email'],
            'password' => $credenciales['password'],
            'activo' => 1,
        ])) {
            /*
             * Regenera el identificador de sesión para prevenir
             * ataques de fijación de sesión.
             */
            $request->session()->regenerate();

            // Una sesión anterior puede conservar la página demo ya retirada.
            $rutaPendiente = parse_url(
                $request->session()->get('url.intended', ''),
                PHP_URL_PATH
            );

            if (rtrim((string) $rutaPendiente, '/') === '/admin-demo') {
                $request->session()->forget('url.intended');
            }

            return redirect()->intended(route($this->rutaInicial($request)));
        }

        return back()
            ->withErrors([
                'email' => 'Las credenciales ingresadas son incorrectas o el usuario está inactivo.',
            ])
            ->onlyInput('email');
    }

    /**
     * Cierra la sesión del usuario.
     */
    public function cerrarSesion(Request $request)
    {
        Auth::logout();

        /*
         * Invalida completamente la sesión actual.
         */
        $request->session()->invalidate();

        /*
         * Genera un nuevo token CSRF.
         */
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * La autorización del módulo sigue a cargo de su middleware de roles.
     */
    private function rutaInicial(Request $request): string
    {
        return match ($request->user()->rol?->nombre) {
            'Administrador', 'Secretaria' => 'dashboard.estado-cuenta',
            'Lector' => 'lecturas.index',
            default => abort(403, 'No tiene permisos para acceder a esta sección.'),
        };
    }
}
