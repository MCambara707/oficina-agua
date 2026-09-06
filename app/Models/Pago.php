<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


/**
 * AQ-33 — Idempotencia en pagos.
 *
 * La tabla `pagos` tiene una restricción UNIQUE sobre `recibo_id`
 * (uq_pagos_recibo_id), lo que impide registrar dos pagos para el
 * mismo recibo a nivel de base de datos. Con eso queda cubierta la
 * idempotencia para este sprint, ya que el módulo de pagos no es
 * funcional todavía (ver AQ-32).
 *
 * Cuando el pago real se active (si el proyecto continúa), agregar
 * también la validación a nivel de formulario/API para dar un
 * mensaje amigable en vez de dejar que falle la constraint.
 */
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