<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    // Como no usamos migraciones, le decimos a Eloquent
    // exactamente qué tabla ya existe en la base de datos.
    protected $table = 'clientes';

    // Campos que se pueden llenar de forma masiva (formularios)
    protected $fillable = [
        'nombre',
        'telefono',
        'direccion_principal',
        'activo',
    ];

    // Relación: un cliente puede tener varios contadores/predios
    public function contadores()
    {
        return $this->hasMany(Contador::class, 'cliente_id');
    }
}