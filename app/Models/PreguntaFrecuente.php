<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PreguntaFrecuente extends Model
{
    protected $table = 'preguntas_frecuentes';

    protected $fillable = [
        'pregunta',
        'respuesta',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}