<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigracionDocumentoClienteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('testing') || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || filled(config('database.connections.sqlite.url'))) {
            throw new \LogicException('La prueba de migración requiere SQLite :memory: en testing.');
        }

        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('dpi', 20)->unique();
            $table->string('telefono', 25)->nullable();
        });

        DB::table('clientes')->insert(['nombre' => 'Titular histórico', 'dpi' => 'DPI-LEGADO', 'telefono' => '55558888']);
    }

    private function migracion(): object
    {
        return require database_path('migrations/2026_09_26_000001_add_nit_to_clientes_table.php');
    }

    public function test_migracion_preserva_datos_historicos_y_permite_clientes_con_nit(): void
    {
        $this->migracion()->up();

        $this->assertDatabaseHas('clientes', ['nombre' => 'Titular histórico', 'dpi' => 'DPI-LEGADO', 'telefono' => '55558888', 'nit' => null]);
        DB::table('clientes')->insert(['nombre' => 'Titular NIT', 'dpi' => null, 'nit' => '1234K']);
        DB::table('clientes')->insert(['nombre' => 'Otro NIT', 'dpi' => null, 'nit' => '5678K']);
        $this->assertSame(3, DB::table('clientes')->count());
    }

    public function test_migracion_conserva_unicidad_de_dpi(): void
    {
        $this->migracion()->up();

        $this->expectException(QueryException::class);
        DB::table('clientes')->insert(['nombre' => 'Duplicado', 'dpi' => 'DPI-LEGADO']);
    }

    public function test_migracion_impone_unicidad_de_nit(): void
    {
        $this->migracion()->up();
        DB::table('clientes')->insert(['nombre' => 'Primero', 'dpi' => null, 'nit' => '1234K']);

        $this->expectException(QueryException::class);
        DB::table('clientes')->insert(['nombre' => 'Duplicado', 'dpi' => null, 'nit' => '1234K']);
    }

    public function test_rollback_no_descarta_documentos_nit(): void
    {
        $migracion = $this->migracion();
        $migracion->up();
        DB::table('clientes')->insert(['nombre' => 'Titular NIT', 'dpi' => null, 'nit' => '1234K']);

        try {
            $migracion->down();
            $this->fail('El rollback no debe perder documentos registrados.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('No se puede retirar NIT', $exception->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('clientes', 'nit'));
        $this->assertDatabaseHas('clientes', ['nombre' => 'Titular NIT', 'dpi' => null, 'nit' => '1234K']);
    }

    public function test_rollback_sin_nit_restaura_esquema_y_conserva_dpi_historico(): void
    {
        $migracion = $this->migracion();
        $migracion->up();
        $migracion->down();

        $this->assertFalse(Schema::hasColumn('clientes', 'nit'));
        $this->assertDatabaseHas('clientes', ['nombre' => 'Titular histórico', 'dpi' => 'DPI-LEGADO']);

        $this->expectException(QueryException::class);
        DB::table('clientes')->insert(['nombre' => 'Sin documento', 'dpi' => null]);
    }
}
