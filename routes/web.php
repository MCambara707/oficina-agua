<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\AutenticacionController;
use App\Http\Controllers\LecturaController;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

// Página principal.
Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Autenticación
|--------------------------------------------------------------------------
*/

// Mostrar formulario de inicio de sesión.
Route::get('/login', [AutenticacionController::class, 'mostrarLogin'])
    ->middleware('guest')
    ->name('login');

// Procesar inicio de sesión.
Route::post('/login', [AutenticacionController::class, 'iniciarSesion'])
    ->middleware('guest')
    ->name('login.procesar');

/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Cerrar sesión.
    Route::post('/logout', [AutenticacionController::class, 'cerrarSesion'])
        ->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Panel general
    |--------------------------------------------------------------------------
    */

    // Disponible para cualquier usuario autenticado.
    Route::get('/admin-demo', function () {
        return view('admin-demo');
    })->name('admin.demo');

    /*
    |--------------------------------------------------------------------------
    | Administrador y Secretaria
    |--------------------------------------------------------------------------
    */

    Route::middleware('rol:Administrador,Secretaria,Lector')->group(function () {

        // Gestión de clientes.
        Route::resource('clientes', ClienteController::class)
            ->except('show');

        // Gestión de contadores.
        Route::resource('contadores', ContadorController::class)
            ->parameters(['contadores' => 'contador'])
            ->except('show');

        // Registro y consulta de lecturas.
        Route::resource('lecturas', LecturaController::class)
            ->only(['index', 'create', 'store']);
    });

    /*
    |--------------------------------------------------------------------------
    | Solo Administrador
    |--------------------------------------------------------------------------
    */

    Route::middleware('rol:Administrador')->group(function () {

        // Gestión de tarifas.
        Route::resource('tarifas', TarifaController::class)
            ->except('show');
    });
});