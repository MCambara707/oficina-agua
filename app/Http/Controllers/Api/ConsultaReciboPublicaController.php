<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ConsultarReciboPublicoRequest;
use App\Http\Resources\ReciboPublicoResource;
use App\Models\Contador;
use App\Models\Recibo;
use Illuminate\Http\JsonResponse;

class ConsultaReciboPublicaController extends Controller
{
    /**
     * Consulta los recibos asociados a un contador,
     * comprobando primero que el DPI y el número
     * de contador pertenezcan al mismo cliente.
     */
    public function consultar(
        ConsultarReciboPublicoRequest $request
    ): JsonResponse {
        $datos = $request->validated();

        /*
         * Ambos datos deben coincidir.
         *
         * No buscamos únicamente por DPI ni únicamente
         * por contador para reducir exposición de información.
         */
        $contador = Contador::query()
            ->where(
                'numero_registro',
                $datos['numero_contador']
            )
            ->whereHas(
                'cliente',
                function ($query) use ($datos) {
                    $query->where(
                        'dpi',
                        $datos['dpi']
                    );
                }
            )
            ->first();

        /*
         * Utilizamos una respuesta genérica.
         *
         * No indicamos si falló el DPI o el contador,
         * evitando revelar cuál de los dos existe.
         */
        if (! $contador) {
            return response()->json(
                [
                    'message' =>
                        'No se encontró información con los datos proporcionados.',
                ],
                404
            );
        }

        /*
         * Se devuelven los recibos más recientes.
         *
         * Limitamos la consulta para que el endpoint público
         * no entregue indefinidamente todo el historial.
         */
        $recibos = Recibo::query()
            ->with([
                'lectura',
                'tarifa',
                'pago.metodoPago',
            ])
            ->whereHas(
                'lectura',
                function ($query) use ($contador) {
                    $query->where(
                        'contador_id',
                        $contador->id
                    );
                }
            )
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->limit(24)
            ->get();

        return response()->json([
            'numero_contador' =>
                $contador->numero_registro,

            'recibos' =>
                ReciboPublicoResource::collection(
                    $recibos
                )->resolve($request),
        ]);
    }
}