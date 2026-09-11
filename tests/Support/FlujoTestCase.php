<?php

namespace Tests\Support;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\MetodoPago;
use App\Models\Recibo;
use App\Models\Role;
use App\Models\Servicio;
use App\Models\Tarifa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fixture aislado del esquema legado; nunca ejecuta migraciones ni limpia la BD real.
 * SQLite verifica HTTP, reglas, atomicidad y restricciones básicas. No reproduce
 * los triggers de auditoría ni los bloqueos de filas de MariaDB.
 */
abstract class FlujoTestCase extends TestCase
{
    protected User $operador;
    protected Tarifa $tarifa;
    protected Servicio $servicio;
    protected MetodoPago $metodo;

    private int $secuencia = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            ! app()->environment('testing')
            || config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || filled(config('database.connections.sqlite.url'))
        ) {
            throw new \LogicException(
                'Estas pruebas solo pueden usar SQLite :memory: en testing.'
            );
        }

        $this->withoutVite();

        $this->travelTo(
            now()
                ->setDate(2026, 9, 5)
                ->setTime(9, 0)
        );

        DB::statement('PRAGMA foreign_keys = ON');

        $tables = [

            'CREATE TABLE roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(50),
                descripcion TEXT,
                activo BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                rol_id INTEGER REFERENCES roles(id),
                nombre VARCHAR(150),
                email VARCHAR(255) UNIQUE,
                password VARCHAR(255),
                activo BOOLEAN NOT NULL DEFAULT 1,
                remember_token VARCHAR(100),
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE clientes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(150),
                dpi VARCHAR(20) UNIQUE,
                telefono VARCHAR(25),
                direccion_principal VARCHAR(255),
                activo BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE servicios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100),
                descripcion TEXT,
                activo BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE tarifas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(100),
                tipo VARCHAR(50),
                capacidad DECIMAL(12,3),
                precio_por_m3 DECIMAL(12,2),
                precio_exceso_m3 DECIMAL(12,2),
                mora_porcentaje DECIMAL(12,2),
                mora_monto_fijo DECIMAL(12,2),
                vigente_desde DATE,
                vigente_hasta DATE,
                activo BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE contadores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cliente_id INTEGER NOT NULL REFERENCES clientes(id),
                tarifa_id INTEGER REFERENCES tarifas(id),
                servicio_id INTEGER REFERENCES servicios(id),
                numero_registro VARCHAR(50) UNIQUE,
                direccion_servicio VARCHAR(255),
                punto_referencia VARCHAR(255),
                foto_ruta VARCHAR(255),
                sector VARCHAR(100),
                lectura_inicial DECIMAL(12,3) NULL
                    CHECK (
                        lectura_inicial IS NULL
                        OR lectura_inicial >= 0
                    ),
                activo BOOLEAN NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE lecturas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                contador_id INTEGER NOT NULL REFERENCES contadores(id),
                usuario_lector_id INTEGER NOT NULL REFERENCES users(id),
                periodo DATE NOT NULL,
                fecha_lectura DATE NOT NULL,
                lectura_anterior DECIMAL(12,3) NOT NULL,
                lectura_actual DECIMAL(12,3) NOT NULL,
                consumo_m3 DECIMAL(12,3) NOT NULL
                    CHECK (consumo_m3 >= 0),
                observacion VARCHAR(255),
                created_at DATETIME,
                updated_at DATETIME,
                UNIQUE (contador_id, periodo)
            )',

            'CREATE TABLE recibos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lectura_id INTEGER NOT NULL UNIQUE
                    REFERENCES lecturas(id),
                tarifa_id INTEGER NOT NULL
                    REFERENCES tarifas(id),
                numero_recibo VARCHAR(50) NOT NULL UNIQUE,
                fecha_emision DATE NOT NULL,
                monto DECIMAL(12,2) NOT NULL
                    CHECK (monto >= 0),
                estado VARCHAR(20) NOT NULL
                    CHECK (
                        estado IN (
                            \'PENDIENTE\',
                            \'PAGADO\',
                            \'ANULADO\'
                        )
                    ),
                observacion VARCHAR(255),
                created_at DATETIME,
                updated_at DATETIME
            )',

            /*
             * AQ-71
             *
             * Tabla independiente para manejar correlativos
             * de documentos sin depender del ID de lectura.
             */
            'CREATE TABLE correlativos_documentos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo VARCHAR(30) NOT NULL UNIQUE,
                ultimo_numero INTEGER NOT NULL DEFAULT 0,
                created_at DATETIME,
                updated_at DATETIME
            )',

            'CREATE TABLE metodos_pago (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(50),
                descripcion TEXT,
                activo BOOLEAN NOT NULL DEFAULT 1
            )',

            'CREATE TABLE pagos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                recibo_id INTEGER NOT NULL UNIQUE
                    REFERENCES recibos(id),
                usuario_registro_id INTEGER NOT NULL
                    REFERENCES users(id),
                metodo_pago_id INTEGER NOT NULL
                    REFERENCES metodos_pago(id),
                monto DECIMAL(12,2) NOT NULL
                    CHECK (monto > 0),
                fecha_pago DATETIME NOT NULL,
                referencia VARCHAR(100),
                observacion VARCHAR(255),
                created_at DATETIME,
                updated_at DATETIME
            )',
        ];

        foreach ($tables as $sql) {
            DB::statement($sql);
        }

        /*
         * AQ-71
         *
         * Inicializa el correlativo de recibos para cada
         * prueba independiente.
         */
        DB::table('correlativos_documentos')->insert([
            'tipo' => 'RECIBO',
            'ultimo_numero' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->operador = $this->usuario('Administrador');

        $this->actingAs($this->operador);

        $this->tarifa = Tarifa::create([
            'nombre' => 'Tarifa de prueba',
            'tipo' => '1 paja',
            'capacidad' => 20,
            'precio_por_m3' => 2,
            'precio_exceso_m3' => 5,
            'mora_porcentaje' => 10,
            'mora_monto_fijo' => 3,
            'vigente_desde' => '2026-01-01',
            'activo' => true,
        ]);

        $this->servicio = Servicio::create([
            'nombre' => 'Agua potable',
            'activo' => true,
        ]);

        $this->metodo = MetodoPago::create([
            'nombre' => 'Efectivo',
            'activo' => true,
        ]);
    }

    protected function usuario(string $rol): User
    {
        return User::create([
            'rol_id' => Role::firstOrCreate(
                ['nombre' => $rol],
                ['activo' => true]
            )->id,

            'nombre' => $rol . ' de prueba',

            'email' =>
                uniqid('operador') . '@example.test',

            'password' =>
                'clave-solo-pruebas',

            'activo' => true,
        ]);
    }

    protected function cliente(
        array $attributes = []
    ): Cliente {
        return Cliente::create(
            array_merge(
                [
                    'nombre' =>
                        'Cliente ' . ++$this->secuencia,

                    'dpi' =>
                        str_pad(
                            (string) $this->secuencia,
                            13,
                            '0',
                            STR_PAD_LEFT
                        ),

                    'activo' => true,
                ],
                $attributes
            )
        );
    }

    protected function contador(
        array $attributes = []
    ): Contador {
        return Contador::create(
            array_merge(
                [
                    'cliente_id' =>
                        $attributes['cliente_id']
                        ?? $this->cliente()->id,

                    'tarifa_id' =>
                        $this->tarifa->id,

                    'servicio_id' =>
                        $this->servicio->id,

                    'numero_registro' =>
                        'CONT-' . ++$this->secuencia,

                    'direccion_servicio' =>
                        'Zona 1',

                    'lectura_inicial' =>
                        0,

                    'activo' =>
                        true,
                ],
                $attributes
            )
        );
    }

    protected function lectura(
        Contador $contador,
        array $attributes = []
    ): Lectura {
        return Lectura::create(
            array_merge(
                [
                    'contador_id' =>
                        $contador->id,

                    'usuario_lector_id' =>
                        $this->operador->id,

                    'periodo' =>
                        '2026-08-01',

                    'fecha_lectura' =>
                        '2026-08-05',

                    'lectura_anterior' =>
                        80,

                    'lectura_actual' =>
                        100,

                    'consumo_m3' =>
                        20,
                ],
                $attributes
            )
        );
    }

    protected function recibo(
        ?Contador $contador = null,
        array $attributes = [],
        array $lectura = []
    ): Recibo {
        return Recibo::create(
            array_merge(
                [
                    'lectura_id' =>
                        $this->lectura(
                            $contador ?? $this->contador(),
                            $lectura
                        )->id,

                    'tarifa_id' =>
                        $this->tarifa->id,

                    'numero_recibo' =>
                        'TEST-' . ++$this->secuencia,

                    'fecha_emision' =>
                        '2026-09-05',

                    'monto' =>
                        40,

                    'estado' =>
                        'PENDIENTE',
                ],
                $attributes
            )
        );
    }

    protected function datosAlta(
        array $attributes = []
    ): array {
        return array_merge(
            [
                'tipo_cliente' =>
                    'nuevo',

                'nombre' =>
                    'Cliente Alta',

                'dpi' =>
                    '9876543210123',

                'numero_registro' =>
                    'ALTA-' . ++$this->secuencia,

                'tarifa_id' =>
                    $this->tarifa->id,

                'servicio_id' =>
                    $this->servicio->id,

                'direccion_servicio' =>
                    'Zona 2',

                'lectura_inicial' =>
                    450,
            ],
            $attributes
        );
    }

    protected function datosContador(
        Contador $contador,
        array $attributes = []
    ): array {
        return array_merge(
            $contador->only([
                'cliente_id',
                'tarifa_id',
                'servicio_id',
                'numero_registro',
                'direccion_servicio',
                'lectura_inicial',
                'activo',
            ]),
            $attributes
        );
    }

    protected function datosTarifa(
        array $attributes = []
    ): array {
        return array_merge(
            [
                'nombre' =>
                    $this->tarifa->nombre,

                'tipo_selector' =>
                    '1 paja',

                'precio_por_m3' =>
                    2,

                'precio_exceso_m3' =>
                    5,

                'mora_porcentaje' =>
                    10,

                'mora_monto_fijo' =>
                    3,

                'vigente_desde' =>
                    '2026-01-01',

                'vigente_hasta' =>
                    null,

                'activo' =>
                    1,
            ],
            $attributes
        );
    }

    protected function registrarLectura(
        Contador $contador,
        float $actual = 115,
        string $periodo = '2026-09',
        array $extra = []
    ) {
        return $this
            ->from(route('lecturas.create'))
            ->post(
                route('lecturas.store'),
                array_merge(
                    [
                        'contador_id' =>
                            $contador->id,

                        'periodo' =>
                            $periodo,

                        'lectura_actual' =>
                            $actual,
                    ],
                    $extra
                )
            );
    }

    protected function pagar(
        Recibo $recibo,
        array $extra = []
    ) {
        return $this->post(
            route('pagos.store'),
            array_merge(
                [
                    'recibo_id' =>
                        $recibo->id,

                    'metodo_pago_id' =>
                        $this->metodo->id,

                    'monto' =>
                        0.01,
                ],
                $extra
            )
        );
    }
}