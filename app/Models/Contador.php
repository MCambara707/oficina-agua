<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contador extends Model
{
    protected $table = 'contadores';

    protected $fillable = [
        'cliente_id',
        'numero_registro',
        'direccion_servicio',
        'punto_referencia',
        'sector',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}