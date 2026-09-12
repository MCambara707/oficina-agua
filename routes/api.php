<?php

use App\Http\Controllers\Api\ConsultaReciboPublicaController;
use App\Http\Controllers\Api\LandingPublicaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API pública - AquaTech GT
|--------------------------------------------------------------------------
|
| Endpoints públicos consumidos por el sitio web AquaTech GT.
| Estas rutas no requieren autenticación del sistema administrativo.
|
*/

Route::prefix('public')
    ->name('api.public.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Información pública
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/tarifas',
            [LandingPublicaController::class, 'tarifas']
        )->name('tarifas');

        Route::get(
            '/avisos',
            [LandingPublicaController::class, 'avisos']
        )->name('avisos');

        Route::get(
            '/preguntas-frecuentes',
            [LandingPublicaController::class, 'preguntasFrecuentes']
        )->name('preguntas-frecuentes');

        Route::get(
            '/quienes-somos',
            [LandingPublicaController::class, 'quienesSomos']
        )->name('quienes-somos');

        Route::get(
            '/contacto',
            [LandingPublicaController::class, 'contacto']
        )->name('contacto');

        /*
        |--------------------------------------------------------------------------
        | Consulta pública de recibos
        |--------------------------------------------------------------------------
        |
        | Se limita la cantidad de solicitudes porque este endpoint consulta
        | información relacionada con clientes y recibos.
        |
        */

        Route::post(
            '/consulta-recibo',
            [ConsultaReciboPublicaController::class, 'consultar']
        )
            ->middleware('throttle:20,1')
            ->name('consulta-recibo');
    });