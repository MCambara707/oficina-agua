<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'rol_id',
    'nombre',
    'email',
    'password',
    'activo',
])]
#[Hidden([
    'password',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Relación: un usuario pertenece a un rol.
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    /**
     * Conversión automática de atributos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }
}