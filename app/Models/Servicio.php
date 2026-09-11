<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
    ];

    public function contadores()
    {
        return $this->hasMany(Contador::class, 'servicio_id');
    }
}