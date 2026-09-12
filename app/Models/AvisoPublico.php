<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AvisoPublico extends Model
{
    protected $table = 'avisos_publicos';

    protected $fillable = [
        'titulo',
        'contenido',
        'fecha_inicio',
        'fecha_fin',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];
    }

    public function scopePublicados(Builder $query): Builder
    {
        $hoy = now()->toDateString();

        return $query
            ->where('activo', true)
            ->where(function (Builder $query) use ($hoy) {
                $query
                    ->whereNull('fecha_inicio')
                    ->orWhere('fecha_inicio', '<=', $hoy);
            })
            ->where(function (Builder $query) use ($hoy) {
                $query
                    ->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $hoy);
            });
    }
}