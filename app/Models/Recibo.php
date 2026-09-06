<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Recibo extends Model
{
    /**
     * AQ-34 — Días de gracia antes de considerar un recibo atrasado.
     * No hay un valor definido por la junta todavía; 15 es un default
     * razonable y fácil de ajustar cuando se confirme la regla real.
     */
    const DIAS_GRACIA = 15;
 
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
     * AQ-34 — Fecha límite antes de que el recibo empiece a generar mora.
     */
    public function fechaVencimiento()
    {
        return $this->fecha_emision->copy()->addDays(self::DIAS_GRACIA);
    }
 
    /**
     * AQ-34 — Un recibo está atrasado si sigue PENDIENTE después de su
     * fecha de vencimiento. Un recibo PAGADO o ANULADO nunca se considera
     * atrasado, aunque haya pasado la fecha.
     */
    public function estaAtrasado(): bool
    {
        return $this->estado === 'PENDIENTE'
            && now()->greaterThan($this->fechaVencimiento());
    }
 
    /**
     * AQ-34 — Días transcurridos desde el vencimiento (0 si no está atrasado).
     */
    public function diasAtraso(): int
    {
        if (! $this->estaAtrasado()) {
            return 0;
        }
 
        return (int) $this->fechaVencimiento()->diffInDays(now());
    }
 
    /**
     * AQ-34 — Mora acumulada: monto fijo de la tarifa + porcentaje sobre
     * el monto del recibo. Cualquiera de los dos puede ser null en la
     * tarifa (no todas las tarifas aplican mora); en ese caso no suma.
     */
    public function montoMora(): float
    {
        if (! $this->estaAtrasado() || ! $this->tarifa) {
            return 0.0;
        }
 
        $fijo = (float) ($this->tarifa->mora_monto_fijo ?? 0);
        $porcentaje = (float) ($this->tarifa->mora_porcentaje ?? 0);
 
        return round($fijo + ($this->monto * $porcentaje / 100), 2);
    }
 
    /**
     * AQ-34 — Monto total a pagar incluyendo mora (para mostrar en
     * pantalla; no reemplaza el campo `monto` original del recibo).
     */
    public function montoConMora(): float
    {
        return round((float) $this->monto + $this->montoMora(), 2);
    }
}