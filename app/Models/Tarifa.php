<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'nombre',
        'tipo',
        'capacidad',
        'precio_por_m3',
        'precio_exceso_m3',
        'mora_porcentaje',
        'mora_monto_fijo',
        'vigente_desde',
        'vigente_hasta',
        'activo',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',

        'capacidad' => 'decimal:3',
        'precio_por_m3' => 'decimal:2',
        'precio_exceso_m3' => 'decimal:2',

        'mora_porcentaje' => 'decimal:2',
        'mora_monto_fijo' => 'decimal:2',

        'activo' => 'boolean',
    ];
}