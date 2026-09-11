<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contador extends Model
{
    protected $table = 'contadores';

    protected $fillable = [
        'cliente_id',
        'tarifa_id',
        'servicio_id',
        'numero_registro',
        'direccion_servicio',
        'punto_referencia',
        'foto_ruta',
        'sector',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function tarifaVigente($fecha = null)
    {
        $this->loadMissing('tarifa');

        if (! $this->tarifa) {
            return null;
        }

        $fecha = $fecha instanceof \Carbon\CarbonInterface
            ? $fecha->toDateString()
            : ($fecha ?? now()->toDateString());

        return Tarifa::where('tipo', $this->tarifa->tipo)
            ->where('activo', true)
            ->where('vigente_desde', '<=', $fecha)
            ->where(function ($query) use ($fecha) {
                $query->whereNull('vigente_hasta')
                    ->orWhere('vigente_hasta', '>=', $fecha);
            })
            ->orderByDesc('vigente_desde')
            ->first();
    }
}