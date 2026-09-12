<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AltaServicioController;
use App\Http\Controllers\AutenticacionController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\ContadorController;
use App\Http\Controllers\DashboardEstadoCuentaController;
use App\Http\Controllers\LecturaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ReciboController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\UsuarioController;


/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

// Entrada al sistema según la sesión y el rol del usuario.
Route::get('/', [AutenticacionController::class, 'inicio'])
    ->name('inicio');


// AQ-69:
// Página de presentación del equipo.
Route::get('/equipo', function () {
    return view('presentacion');
})->name('equipo');


// Mostrar formulario de inicio de sesión.
Route::get(
    '/login',
    [AutenticacionController::class, 'mostrarLogin']
)
    ->middleware('guest')
    ->name('login');


// Procesar inicio de sesión.
Route::post(
    '/login',
    [AutenticacionController::class, 'iniciarSesion']
)
    ->middleware('guest')
    ->name('login.procesar');


/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'auditoria'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Sesión
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/logout',
        [AutenticacionController::class, 'cerrarSesion']
    )->name('logout');


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
         * Los usuarios no se eliminan físicamente.
         * Se utiliza activación / desactivación.
         */
        Route::resource(
            'usuarios',
            UsuarioController::class
        )->except([
            'show',
            'destroy',
        ]);


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

    Route::middleware(
        'rol:Administrador,Secretaria'
    )->group(function () {

        /*
         * ================================================================
         * ALTA INTEGRAL DE SERVICIO
         * ================================================================
         *
         * Flujo operativo:
         *
         * Cliente existente o cliente nuevo
         *          ↓
         * Servicio
         *          ↓
         * Tarifa
         *          ↓
         * Contador
         *
         * No utiliza una tabla adicional.
         * Coordina las entidades existentes de Cliente y Contador.
         */
        Route::get(
            '/alta-servicio',
            [AltaServicioController::class, 'create']
        )->name('alta-servicio.create');


        Route::post(
            '/alta-servicio',
            [AltaServicioController::class, 'store']
        )->name('alta-servicio.store');


        /*
         * ================================================================
         * CLIENTES
         * ================================================================
         */

        Route::resource(
            'clientes',
            ClienteController::class
        )->except('show');


        /*
         * ================================================================
         * CONTADORES
         * ================================================================
         */

        Route::resource(
            'contadores',
            ContadorController::class
        )
            ->parameters([
                'contadores' => 'contador',
            ])
            ->except('show');


        /*
         * ================================================================
         * SERVICIOS
         * ================================================================
         *
         * AQ-74:
         * Mantenimiento del catálogo de servicios.
         *
         * Actualmente Servicio únicamente clasifica qué servicio
         * presta un contador.
         *
         * No interviene directamente en el cálculo del recibo.
         */

        Route::resource(
            'servicios',
            ServicioController::class
        )->except('show');


        /*
         * ================================================================
         * TARIFAS
         * ================================================================
         */

        Route::resource(
            'tarifas',
            TarifaController::class
        )->except('show');

        Route::get('/recibos', [ReciboController::class, 'index'])
            ->name('recibos.index');


        /*
         * ================================================================
         * PAGOS
         * ================================================================
         *
         * Permite:
         *
         * - consultar recibos pendientes;
         * - calcular mora;
         * - registrar el pago;
         * - cambiar el recibo a PAGADO;
         * - generar el comprobante correspondiente.
         */

        Route::get(
            '/pagos',
            [PagoController::class, 'index']
        )->name('pagos.index');


        Route::get(
            '/pagos/{recibo}/registrar',
            [PagoController::class, 'create']
        )->name('pagos.create');


        Route::post(
            '/pagos',
            [PagoController::class, 'store']
        )->name('pagos.store');


        /*
         * ================================================================
         * DASHBOARD DE ESTADO DE CUENTA
         * ================================================================
         */

        Route::get(
            '/dashboard/estado-cuenta',
            [DashboardEstadoCuentaController::class, 'index']
        )->name('dashboard.estado-cuenta');
    });


    /*
    |--------------------------------------------------------------------------
    | Administrador, Secretaria y Lector
    |--------------------------------------------------------------------------
    |
    | El Lector puede:
    |
    | - registrar lecturas;
    | - consultar lecturas;
    | - imprimir el recibo generado.
    |
    | El Lector NO puede:
    |
    | - registrar pagos;
    | - administrar clientes;
    | - administrar contadores;
    | - administrar tarifas;
    | - administrar servicios;
    | - realizar altas de servicio.
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'rol:Administrador,Secretaria,Lector'
    )->group(function () {

        /*
         * ================================================================
         * LECTURAS
         * ================================================================
         */

        Route::resource(
            'lecturas',
            LecturaController::class
        )->only([
            'index',
            'create',
            'store',
        ]);


        /*
         * ================================================================
         * RECIBO / COMPROBANTE IMPRIMIBLE
         * ================================================================
         *
         * Lector:
         * puede imprimir el recibo generado en campo.
         *
         * Administrador / Secretaria:
         * pueden consultar, reimprimir y visualizar el comprobante
         * cuando el recibo se encuentre pagado.
         */

        Route::get(
            '/recibos/{recibo}/imprimir',
            [ReciboController::class, 'imprimir']
        )->name('recibos.imprimir');
    });
});
