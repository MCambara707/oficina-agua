<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReciboPublicoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pendiente = $this->estado === 'PENDIENTE';

        return [
            'numero_recibo' => $this->numero_recibo,

            'periodo' =>
                $this->lectura?->periodo?->format('Y-m'),

            'fecha_emision' =>
                $this->fecha_emision?->toDateString(),

            'fecha_vencimiento' =>
                $this->fechaVencimiento()?->toDateString(),

            'estado' => $this->estado,

            'lectura' => [
                'anterior' =>
                    (float) ($this->lectura?->lectura_anterior ?? 0),

                'actual' =>
                    (float) ($this->lectura?->lectura_actual ?? 0),

                'consumo_m3' =>
                    (float) ($this->lectura?->consumo_m3 ?? 0),
            ],

            'monto' => (float) $this->monto,

            /*
             * La mora mostrada aquí es la mora actual.
             * Solo aplica mientras el recibo continúa pendiente.
             */
            'mora_actual' =>
                $pendiente
                    ? $this->montoMora()
                    : 0,

            'total_pagar' =>
                $pendiente
                    ? $this->montoConMora()
                    : null,

            /*
             * Si el recibo ya fue pagado, mostramos únicamente
             * la información pública necesaria del pago.
             */
            'pago' =>
                $this->estado === 'PAGADO' && $this->pago
                    ? [
                        'monto' =>
                            (float) $this->pago->monto,

                        'fecha' =>
                            $this->pago->fecha_pago
                                ?->toDateTimeString(),

                        'metodo' =>
                            $this->pago
                                ->metodoPago
                                ?->nombre,
                    ]
                    : null,
        ];
    }
}