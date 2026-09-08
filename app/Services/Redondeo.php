<?php

namespace App\Services;

class Redondeo
{
    /**
     * Redondea un monto en quetzales según config/facturacion.php
     * Úsalo en cualquier lugar donde se calcule un monto en quetzales a
     * partir de un consumo con decimales (consumo_m3 × precio_por_m3,
     * exceso × precio_exceso_m3, mora, etc.) — no solo en Lecturas.
     */
    public static function monto(float|string $valor): float
    {
        $decimales = config('facturacion.decimales', 2);
        $modo = config('facturacion.modo', PHP_ROUND_HALF_UP);

        return round((float) $valor, $decimales, $modo);
    }
}