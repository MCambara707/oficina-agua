<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    protected $table = 'tarifas';

    protected $fillable = [
        'nombre',
        'precio_por_m3',
        'vigente_desde',
        'vigente_hasta',
        'activo',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
        'precio_por_m3' => 'decimal:2',
        'activo' => 'boolean',
        // AQ-34: casts de mora, solo lectura por ahora (no se agregan a
        // fillable porque el mantenimiento de tarifas no es parte de esta
        // tarjeta; el CRUD de tarifas lo gestiona otra tarea).
        'mora_porcentaje' => 'decimal:2',
        'mora_monto_fijo' => 'decimal:2',
        // AQ-28: cast de capacidad, solo lectura por la misma razón de
        // arriba — esta tarjeta solo necesita LEER tipo/capacidad para
        // encontrar la tarifa vigente, no dar de alta tarifas nuevas.
        // "tipo" no necesita cast (es texto plano) pero ya es legible
        // como atributo aunque no esté en $fillable.
        'capacidad' => 'decimal:3',
    ];
}