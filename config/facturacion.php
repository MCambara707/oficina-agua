<?php

return [

    // Cantidad de decimales a la que se redondean los montos en quetzales.
    'decimales' => 2,

    // Modo de redondeo, usando las constantes nativas de PHP (round()):
    //   PHP_ROUND_HALF_UP   -> redondeo comercial normal (el 0.5 sube). Es el
    //                          default de PHP y el más "tradicional" en facturación.
    //   PHP_ROUND_HALF_DOWN -> el 0.5 baja.
    //   PHP_ROUND_HALF_EVEN -> redondeo bancario (al par más cercano).
    //   PHP_ROUND_HALF_ODD  -> variante rara, casi no se usa en facturación.
    'modo' => PHP_ROUND_HALF_UP,

];