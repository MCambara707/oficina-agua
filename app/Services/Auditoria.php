<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Auditoria
{
    public static function establecerUsuario(): void
    {
        $conexion = DB::connection();
        if (in_array($conexion->getDriverName(), ['mysql', 'mariadb'], true)) {
            $conexion->statement('SET @app_user_id = ?', [auth()->id()]);
        }
    }
}
