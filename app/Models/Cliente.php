<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    /**
     * Tabla asociada al modelo.
     *
     * El proyecto trabaja sobre una base de datos
     * ya existente.
     */
    protected $table = 'clientes';


    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'nombre',
        'dpi',
        'telefono',
        'direccion_principal',
        'activo',
    ];


    /**
     * Conversión automática de atributos.
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }


    /**
     * Relación:
     *
     * Un cliente puede tener uno o varios contadores.
     *
     * Esto permite representar casos como:
     *
     * Cliente
     *  ├── Contador 001
     *  └── Contador 002
     */
    public function contadores(): HasMany
    {
        return $this->hasMany(
            Contador::class,
            'cliente_id'
        );
    }
}