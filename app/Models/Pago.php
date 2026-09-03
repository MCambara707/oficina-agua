<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'recibo_id',
        'usuario_registro_id',
        'metodo_pago_id',
        'monto',
        'fecha_pago',
        'referencia',
        'observacion',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto' => 'decimal:2',
    ];

    public function recibo()
    {
        return $this->belongsTo(Recibo::class);
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class);
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro_id');
    }
}