<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recibo extends Model
{
    /**
     * Día límite mensual para realizar el pago sin mora.
     *
     * El cliente puede pagar hasta finalizar el día 10.
     * A partir del día 11, si el recibo continúa PENDIENTE,
     * se considera atrasado y se aplica la mora configurada
     * en la tarifa.
     */
    public const DIA_LIMITE_PAGO = 10;

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

    /**
     * Obtiene la fecha límite de pago.
     *
     * Si el recibo se emite entre los días 1 y 10,
     * vence el día 10 del mismo mes.
     *
     * Si el recibo se emite después del día 10,
     * vence el día 10 del mes siguiente.
     */
    public function fechaVencimiento()
    {
        $fechaEmision = $this->fecha_emision->copy();

        if ($fechaEmision->day <= self::DIA_LIMITE_PAGO) {
            return $fechaEmision
                ->copy()
                ->day(self::DIA_LIMITE_PAGO)
                ->endOfDay();
        }

        return $fechaEmision
            ->copy()
            ->addMonthNoOverflow()
            ->startOfMonth()
            ->day(self::DIA_LIMITE_PAGO)
            ->endOfDay();
    }

    /**
     * Un recibo está atrasado cuando:
     *
     * - continúa en estado PENDIENTE;
     * - y ya pasó completamente el día 10 correspondiente.
     *
     * PAGADO y ANULADO nunca generan mora.
     */
    public function estaAtrasado(): bool
    {
        return $this->estado === 'PENDIENTE'
            && now()->greaterThan($this->fechaVencimiento());
    }

    /**
     * Cantidad de días de atraso.
     *
     * Si todavía está dentro del plazo de pago,
     * devuelve cero.
     */
    public function diasAtraso(): int
    {
        if (! $this->estaAtrasado()) {
            return 0;
        }

        return (int) $this->fechaVencimiento()->diffInDays(now());
    }

    /**
     * Calcula la mora configurada en la tarifa.
     *
     * La mora puede contener:
     *
     * - un porcentaje sobre el monto del recibo;
     * - un monto fijo;
     * - o ambos.
     *
     * Si existen ambos, se suman.
     *
     * La mora se aplica una sola vez cuando el recibo
     * está vencido. No se incrementa diariamente.
     */
    public function montoMora(): float
    {
        if (! $this->estaAtrasado() || ! $this->tarifa) {
            return 0.0;
        }

        $montoRecibo = (float) $this->monto;

        $moraFija = (float) (
            $this->tarifa->mora_monto_fijo ?? 0
        );

        $moraPorcentaje = (float) (
            $this->tarifa->mora_porcentaje ?? 0
        );

        $montoPorcentaje = $montoRecibo
            * ($moraPorcentaje / 100);

        return round(
            $moraFija + $montoPorcentaje,
            2
        );
    }

    /**
     * Total que debe pagar el cliente.
     *
     * Antes del vencimiento:
     * monto original.
     *
     * Después del vencimiento:
     * monto original + mora.
     */
    public function montoConMora(): float
    {
        return round(
            (float) $this->monto + $this->montoMora(),
            2
        );
    }
}