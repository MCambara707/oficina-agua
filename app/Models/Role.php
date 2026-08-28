<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre',
    'descripcion',
    'activo',
])]
class Role extends Model
{
    use HasFactory;

    /**
     * Relación: un rol puede tener muchos usuarios.
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class, 'rol_id');
    }

    /**
     * Conversión automática de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }
}