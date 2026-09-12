<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TarifaPublicaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'capacidad' => (float) $this->capacidad,

            /*
             * La landing utiliza "precio_m3",
             * mientras la BD interna utiliza "precio_por_m3".
             */
            'precio_m3' => (float) $this->precio_por_m3,

            'precio_exceso_m3' => (float) $this->precio_exceso_m3,

            'vigente_desde' =>
                $this->vigente_desde?->toDateString(),

            'vigente_hasta' =>
                $this->vigente_hasta?->toDateString(),
        ];
    }
}