<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'nombre',
        'precio_por_m3',
        'vigente_desde',
        'vigente_hasta',
        'activo',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'precio_por_m3' => 'decimal:2',
        'activo' => 'boolean',
    ];
}