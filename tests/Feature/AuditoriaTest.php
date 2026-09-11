<?php

namespace Tests\Feature;

use App\Http\Middleware\EstablecerUsuarioAuditoria;
use App\Models\User;
use App\Services\Auditoria;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    private function solicitud(string $nombre, string $metodo = 'POST'): Request
    {
        $request = Request::create('/operacion', $metodo);
        $route = new Route($metodo, 'operacion', fn () => null);
        $route->name($nombre);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 42]));
        return $request;
    }

    public function test_auditoria_usa_usuario_y_limpia_conexion_si_falla_la_operacion(): void
    {
        $connection = Mockery::mock(Connection::class);
        DB::shouldReceive('connection')->once()->andReturn($connection);
        $connection->shouldReceive('getDriverName')->once()->andReturn('mysql');
        $connection->shouldReceive('statement')->with('SET @app_user_id = ?', [42])->once()->ordered()->andReturnTrue();
        $connection->shouldReceive('statement')->with('SET @app_user_id = NULL')->once()->ordered()->andReturnTrue();
        $this->expectException(\DomainException::class);
        (new EstablecerUsuarioAuditoria)->handle($this->solicitud('pagos.store'), function () {
            throw new \DomainException('Operación fallida');
        });
    }

    public function test_auditoria_no_consulta_conexion_en_rutas_sin_triggers(): void
    {
        DB::shouldReceive('connection')->never();
        $middleware = new EstablecerUsuarioAuditoria;
        $this->assertSame(200, $middleware->handle($this->solicitud('alta-servicio.store'), fn () => new Response)->getStatusCode());
        $this->assertSame(200, $middleware->handle($this->solicitud('tarifas.index', 'GET'), fn () => new Response)->getStatusCode());
    }

    public function test_auditoria_no_envia_sql_de_mariadb_a_sqlite(): void
    {
        $connection = Mockery::mock(Connection::class);
        DB::shouldReceive('connection')->andReturn($connection);
        $connection->shouldReceive('getDriverName')->andReturn('sqlite');
        $connection->shouldReceive('statement')->never();
        Auditoria::establecerUsuario();
        $this->assertSame(200, (new EstablecerUsuarioAuditoria)->handle(
            $this->solicitud('lecturas.store'), fn () => new Response
        )->getStatusCode());
    }

    public function test_reestablecer_usuario_usa_la_conexion_actual_tras_reconexion(): void
    {
        $this->actingAs((new User)->forceFill(['id' => 42]));
        $antes = Mockery::mock(Connection::class);
        $despues = Mockery::mock(Connection::class);
        DB::shouldReceive('connection')->twice()->andReturn($antes, $despues);
        foreach ([$antes, $despues] as $connection) {
            $connection->shouldReceive('getDriverName')->andReturn('mysql');
            $connection->shouldReceive('statement')->with('SET @app_user_id = ?', [42])->once()->andReturnTrue();
        }
        Auditoria::establecerUsuario();
        Auditoria::establecerUsuario();
    }
}
