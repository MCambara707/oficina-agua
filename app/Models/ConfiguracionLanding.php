<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionLanding extends Model
{
    protected $table = 'configuraciones_landing';

    protected $fillable = [
        'titulo_principal',
        'descripcion_principal',
        'historia',
        'mision',
        'vision',
        'diferenciadores',
        'telefono',
        'whatsapp',
        'correo',
        'direccion',
        'horario',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}