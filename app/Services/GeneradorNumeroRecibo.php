<?php

namespace App\Services;

use App\Models\Contador;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class GeneradorNumeroRecibo
{
    /**
     * Genera el siguiente número único de recibo.
     *
     * Formato:
     * REC-2026-4821-000001
     *
     * Debe ejecutarse dentro de una transacción.
     */
    public function generar(Contador $contador, string $fechaEmision): string
    {
        /*
         * El bloqueo del correlativo solamente es seguro
         * cuando existe una transacción activa.
         */
        if (DB::connection()->transactionLevel() === 0) {
            throw new LogicException(
                'La generación del número de recibo debe ejecutarse dentro de una transacción.'
            );
        }

        /*
         * Bloquea el correlativo RECIBO para impedir que
         * dos procesos obtengan simultáneamente
         * el mismo número.
         */
        $correlativo = DB::table('correlativos_documentos')
            ->where('tipo', 'RECIBO')
            ->lockForUpdate()
            ->first();

        if (! $correlativo) {
            throw ValidationException::withMessages([
                'contador_id' => 'No se encontró configurado el correlativo de recibos.',
            ]);
        }

        $siguiente = (int) $correlativo->ultimo_numero + 1;

        /*
         * Se conservan únicamente los dígitos del número
         * de registro del contador.
         */
        $registroNumerico = preg_replace(
            '/\D/',
            '',
            (string) $contador->numero_registro
        );

        if ($registroNumerico === '') {
            throw ValidationException::withMessages([
                'contador_id' => 'El contador no posee un número de registro numérico válido.',
            ]);
        }

        /*
         * Obtiene los últimos cuatro dígitos.
         *
         * Ejemplos:
         * 4821      -> 4821
         * CT-4821   -> 4821
         * 25        -> 0025
         * 12345678  -> 5678
         */
        $referenciaContador = substr(
            str_pad($registroNumerico, 4, '0', STR_PAD_LEFT),
            -4
        );

        /*
         * El año se obtiene de la fecha real de emisión.
         */
        $anio = Carbon::parse($fechaEmision)->format('Y');

        /*
         * Se actualiza el correlativo dentro de la misma
         * transacción que posteriormente crea el recibo.
         */
        DB::table('correlativos_documentos')
            ->where('id', $correlativo->id)
            ->update([
                'ultimo_numero' => $siguiente,
                'updated_at' => now(),
            ]);

        return sprintf(
            'REC-%s-%s-%06d',
            $anio,
            $referenciaContador,
            $siguiente
        );
    }
}