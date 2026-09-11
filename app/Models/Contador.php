<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contador extends Model
{
    /**
     * Tabla asociada al modelo.
     */
    protected $table = 'contadores';


    /**
     * Campos permitidos para asignación masiva.
     */
    protected $fillable = [
        'cliente_id',
        'tarifa_id',
        'servicio_id',
        'numero_registro',
        'lectura_inicial',
        'direccion_servicio',
        'punto_referencia',
        'foto_ruta',
        'sector',
        'activo',
    ];


    /**
     * Conversión automática de atributos.
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'lectura_inicial' => 'decimal:3',
        ];
    }


    /**
     * Relación:
     *
     * Un contador pertenece a un cliente.
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class,
            'cliente_id'
        );
    }


    /**
     * Relación:
     *
     * Un contador tiene asignada una tarifa.
     */
    public function tarifa(): BelongsTo
    {
        return $this->belongsTo(
            Tarifa::class,
            'tarifa_id'
        );
    }


    /**
     * Relación:
     *
     * Un contador puede estar clasificado
     * dentro de un servicio.
     *
     * Servicio es informativo y no participa
     * directamente en el cálculo del recibo.
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(
            Servicio::class,
            'servicio_id'
        );
    }


    /**
     * Relación:
     *
     * Un contador puede tener múltiples lecturas
     * a lo largo del tiempo.
     */
    public function lecturas(): HasMany
    {
        return $this->hasMany(
            Lectura::class,
            'contador_id'
        );
    }


    /**
     * Obtiene la tarifa vigente correspondiente
     * al mismo tipo de la tarifa asignada al contador.
     *
     * Si no se proporciona una fecha,
     * utiliza la fecha actual.
     */
    public function tarifaVigente(
        CarbonInterface|string|null $fecha = null,
        bool $bloquear = false
    ): ?Tarifa {
        /*
         * Evita volver a consultar la relación
         * si ya fue cargada previamente.
         */
        $this->loadMissing('tarifa');


        /*
         * Sin tarifa base no podemos determinar
         * qué tipo de tarifa buscar.
         */
        if (! $this->tarifa) {
            return null;
        }


        /*
         * Normalizamos la fecha.
         */
        if ($fecha instanceof CarbonInterface) {

            $fechaConsulta = $fecha->toDateString();

        } elseif (is_string($fecha) && trim($fecha) !== '') {

            $fechaConsulta = $fecha;

        } else {

            $fechaConsulta = now()->toDateString();
        }


        /*
         * Busca la tarifa activa más reciente
         * del mismo tipo y vigente para la fecha indicada.
         */
        return Tarifa::query()
            ->where(
                'tipo',
                $this->tarifa->tipo
            )
            ->where(
                'activo',
                true
            )
            ->where(
                'vigente_desde',
                '<=',
                $fechaConsulta
            )
            ->where(
                function ($query) use ($fechaConsulta) {

                    $query
                        ->whereNull('vigente_hasta')
                        ->orWhere(
                            'vigente_hasta',
                            '>=',
                            $fechaConsulta
                        );
                }
            )
            ->orderByDesc('vigente_desde')
            ->orderByDesc('id')
            ->when($bloquear, fn ($query) => $query->lockForUpdate())
            ->first();
    }
}
