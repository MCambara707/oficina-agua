<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\ContadorController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin-demo', function () {
    return view('admin-demo');
});


Route::resource('clientes', ClienteController::class)->except('show');
Route::resource('tarifas', TarifaController::class)->except('show');
Route::resource('contadores', ContadorController::class)
    ->parameters(['contadores' => 'contador'])
    ->except('show');