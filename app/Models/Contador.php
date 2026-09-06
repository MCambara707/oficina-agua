<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contador extends Model
{
    protected $table = 'contadores';

    protected $fillable = [
        'cliente_id',
        'numero_registro',
        'direccion_servicio',
        'referencia',
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

    // AQ-28: relación de solo lectura hacia la tarifa contratada. No se
    // agrega tarifa_id a $fillable porque asignar o cambiar la tarifa de
    // un contador no es parte de esta tarjeta (eso lo maneja el CRUD de
    // contadores).
    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    /**
     * Tarifa realmente vigente para este contador en una fecha dada
     * (hoy por defecto). No asume que tarifa_id ya apunta a la fila
     * vigente: usa el "tipo" de esa tarifa para buscar, entre todas
     * las filas de ese tipo, la que esté vigente en la fecha indicada.
     *
     * Ojo: esto NO aplica exceso sobre la capacidad ni mora todavía.
     * La fórmula de precio_exceso_m3 sigue pendiente de confirmación
     * del inge (igual que el redondeo de AQ-22) — aplicarla aquí sería
     * adivinar la regla.
     */
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