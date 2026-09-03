<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\AutenticacionController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\PagoController;

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

    Route::middleware('rol:Administrador,Secretaria')->group(function () {

        // Gestión de clientes.
        Route::resource('clientes', ClienteController::class)
            ->except('show');

        // Gestión de contadores.
        Route::resource('contadores', ContadorController::class)
            ->parameters(['contadores' => 'contador'])
            ->except('show');

        // Gestión de pagos (AQ-32 / AQ-33).
        Route::get('/pagos', [PagoController::class, 'index'])
            ->name('pagos.index');

        Route::get('/pagos/{recibo}/registrar', [PagoController::class, 'create'])
            ->name('pagos.create');

        Route::post('/pagos', [PagoController::class, 'store'])
            ->name('pagos.store');
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

    /*
    |--------------------------------------------------------------------------
    | Solo Lector
    |--------------------------------------------------------------------------
    */

    Route::middleware('rol:Lector')->group(function () {

        // Registro de lecturas (AQ-26 / AQ-27).
        Route::get('/lecturas', [LecturaController::class, 'index'])
            ->name('lecturas.index');

        Route::get('/lecturas/crear', [LecturaController::class, 'create'])
            ->name('lecturas.create');

        Route::post('/lecturas', [LecturaController::class, 'store'])
            ->name('lecturas.store');

        // Consulta AJAX de la última lectura de un contador.
        Route::get('/lecturas/ultima/{contador}', [LecturaController::class, 'ultima'])
            ->name('lecturas.ultima');
    });
});