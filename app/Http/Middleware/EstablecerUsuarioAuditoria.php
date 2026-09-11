<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EstablecerUsuarioAuditoria
{
    public function handle(Request $request, Closure $next): Response
    {
        // Triggers verificados en MariaDB: tarifas, recibos y pagos.
        if ($request->isMethodSafe() || ! $request->routeIs(
            'tarifas.store', 'tarifas.update', 'tarifas.destroy', 'lecturas.store', 'pagos.store'
        )) {
            return $next($request);
        }

        $conexion = DB::connection();
        if (! in_array($conexion->getDriverName(), ['mysql', 'mariadb'], true)) {
            return $next($request);
        }

        $conexion->statement('SET @app_user_id = ?', [$request->user()?->id]);
        try {
            return $next($request);
        } finally {
            // La variable pertenece a la conexión, no a la transacción.
            // Limpiarla también tras rollback evita atribuir otra operación al usuario anterior.
            try {
                $conexion->statement('SET @app_user_id = NULL');
            } catch (\Throwable $e) {
                report($e);
                $conexion->disconnect();
            }
        }
    }
}
