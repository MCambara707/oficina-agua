<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\AutenticacionController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\DashboardEstadoCuentaController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\UsuarioController;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

// Página principal.
Route::get('/', function () {
    return view('welcome');
});

// AQ-69: página de presentación del equipo, enlazada desde el footer.
Route::get('/equipo', function () {
    return view('presentacion');
})->name('equipo');

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
    | Solo Administrador
    |--------------------------------------------------------------------------
    */

    Route::middleware('rol:Administrador')->group(function () {

        /*
         * AQ-67:
         * Mantenimiento de usuarios.
         *
         * No existe ruta DELETE porque la baja es lógica.
         */
        Route::resource('usuarios', UsuarioController::class)
            ->except(['show', 'destroy']);

        // Activar / desactivar usuario.
        Route::patch(
            '/usuarios/{usuario}/estado',
            [UsuarioController::class, 'cambiarEstado']
        )->name('usuarios.cambiar-estado');
    });

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

        // Recibo imprimible y comprobante de pago (AQ-30).
        Route::get(
            '/recibos/{recibo}/imprimir',
            [ReciboController::class, 'imprimir']
        )->name('recibos.imprimir');

        // Dashboard de estado de cuenta (AQ-35).
        Route::get(
            '/dashboard/estado-cuenta',
            [DashboardEstadoCuentaController::class, 'index']
        )->name('dashboard.estado-cuenta');
    });

    /*
    |--------------------------------------------------------------------------
    | Administrador, Secretaria y Lector
    |--------------------------------------------------------------------------
    */

    Route::middleware('rol:Administrador,Secretaria,Lector')->group(function () {

        // Gestión de tarifas.
        Route::resource('tarifas', TarifaController::class)
            ->except('show');

        // Registro y consulta de lecturas.
        Route::resource('lecturas', LecturaController::class)
            ->only(['index', 'create', 'store']);
    });

    
});