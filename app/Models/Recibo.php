<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    protected $table = 'recibos';

    protected $fillable = [
        'lectura_id',
        'tarifa_id',
        'numero_recibo',
        'fecha_emision',
        'monto',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'monto' => 'decimal:2',
    ];

    public function lectura()
    {
        return $this->belongsTo(Lectura::class);
    }

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class);
    }

    public function pago()
    {
        return $this->hasOne(Pago::class);
    }
}