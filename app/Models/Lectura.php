<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lectura extends Model
{
    protected $table = 'lecturas';

    protected $fillable = [
        'contador_id',
        'usuario_lector_id',
        'periodo',
        'fecha_lectura',
        'lectura_anterior',
        'lectura_actual',
        'consumo_m3',
        'observacion',
    ];

    protected $casts = [
        'periodo' => 'date',
        'fecha_lectura' => 'date',
        'lectura_anterior' => 'decimal:3',
        'lectura_actual' => 'decimal:3',
        'consumo_m3' => 'decimal:3',
    ];

    public function contador()
    {
        return $this->belongsTo(Contador::class, 'contador_id');
    }

    public function usuarioLector()
    {
        return $this->belongsTo(User::class, 'usuario_lector_id');
    }
}
