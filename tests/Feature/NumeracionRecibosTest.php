<?php

namespace Tests\Feature;

use App\Models\Recibo;
use Illuminate\Support\Facades\DB;
use Tests\Support\FlujoTestCase;

class NumeracionRecibosTest extends FlujoTestCase
{
    public function test_genera_formato_definitivo_con_correlativo_global(): void
    {
        $contadorUno = $this->contador([
            'numero_registro' => 'CONT-4821',
            'lectura_inicial' => 0,
        ]);

        $contadorDos = $this->contador([
            'numero_registro' => 'CONT-7354',
            'lectura_inicial' => 0,
        ]);

        /*
         * Primer recibo.
         */
        $this->registrarLectura(
            $contadorUno,
            10,
            '2026-09'
        )->assertSessionHasNoErrors();

        /*
         * Segundo recibo, perteneciente a otro contador.
         */
        $this->registrarLectura(
            $contadorDos,
            12,
            '2026-09'
        )->assertSessionHasNoErrors();

        /*
         * Tercer recibo, nuevamente para el primer contador.
         */
        $this->registrarLectura(
            $contadorUno,
            20,
            '2026-10'
        )->assertSessionHasNoErrors();

        $numeros = Recibo::query()
            ->orderBy('id')
            ->pluck('numero_recibo')
            ->all();

        $this->assertSame([
            'REC-2026-4821-000001',
            'REC-2026-7354-000002',
            'REC-2026-4821-000003',
        ], $numeros);

        $this->assertDatabaseHas(
            'correlativos_documentos',
            [
                'tipo' => 'RECIBO',
                'ultimo_numero' => 3,
            ]
        );
    }

    public function test_reimprimir_recibo_no_consume_nuevo_correlativo(): void
    {
        $contador = $this->contador([
            'numero_registro' => 'MED-4821',
            'lectura_inicial' => 0,
        ]);

        $this->registrarLectura(
            $contador,
            15,
            '2026-09'
        )->assertSessionHasNoErrors();

        $recibo = Recibo::sole();

        $this->assertSame(
            'REC-2026-4821-000001',
            $recibo->numero_recibo
        );

        /*
         * Se imprime varias veces el mismo recibo.
         * Ninguna impresión debe generar otro número.
         */
        $this->get(
            route('recibos.imprimir', $recibo)
        )->assertOk();

        $this->get(
            route('recibos.imprimir', $recibo)
        )->assertOk();

        $this->get(
            route('recibos.imprimir', $recibo)
        )->assertOk();

        $this->assertDatabaseCount(
            'recibos',
            1
        );

        $this->assertSame(
            'REC-2026-4821-000001',
            $recibo->fresh()->numero_recibo
        );

        $this->assertDatabaseHas(
            'correlativos_documentos',
            [
                'tipo' => 'RECIBO',
                'ultimo_numero' => 1,
            ]
        );
    }

    public function test_fallo_al_crear_recibo_revierte_correlativo(): void
    {
        $contador = $this->contador([
            'numero_registro' => 'CONT-9902',
            'lectura_inicial' => 0,
        ]);

        /*
         * Simula un fallo de BD después de haber solicitado
         * el siguiente correlativo.
         *
         * Todo ocurre dentro de la misma transacción, por lo
         * que lectura y correlativo deben revertirse.
         */
        DB::unprepared(
            "CREATE TEMP TRIGGER fallo_recibo_aq71
            BEFORE INSERT ON recibos
            BEGIN
                SELECT RAISE(ABORT, 'fallo controlado AQ-71');
            END"
        );

        $this->registrarLectura(
            $contador,
            10,
            '2026-09'
        )->assertSessionHasErrors();

        $this->assertDatabaseCount(
            'lecturas',
            0
        );

        $this->assertDatabaseCount(
            'recibos',
            0
        );

        $this->assertDatabaseHas(
            'correlativos_documentos',
            [
                'tipo' => 'RECIBO',
                'ultimo_numero' => 0,
            ]
        );
    }

    public function test_correlativo_no_se_reinicia_al_cambiar_de_anio(): void
    {
        $contador2026 = $this->contador([
            'numero_registro' => 'CONT-4821',
            'lectura_inicial' => 0,
        ]);

        $this->registrarLectura(
            $contador2026,
            10,
            '2026-09'
        )->assertSessionHasNoErrors();

        $primerRecibo = Recibo::sole();

        $this->assertSame(
            'REC-2026-4821-000001',
            $primerRecibo->numero_recibo
        );

        /*
         * Cambiamos el reloj de la aplicación al siguiente año.
         */
        $this->travelTo(
            now()
                ->setDate(2027, 1, 5)
                ->setTime(9, 0)
        );

        $contador2027 = $this->contador([
            'numero_registro' => 'CONT-7354',
            'lectura_inicial' => 0,
        ]);

        $this->registrarLectura(
            $contador2027,
            8,
            '2027-01'
        )->assertSessionHasNoErrors();

        $segundoRecibo = Recibo::query()
            ->orderByDesc('id')
            ->firstOrFail();

        /*
         * Cambia el año, pero el correlativo global continúa.
         */
        $this->assertSame(
            'REC-2027-7354-000002',
            $segundoRecibo->numero_recibo
        );

        $this->assertDatabaseHas(
            'correlativos_documentos',
            [
                'tipo' => 'RECIBO',
                'ultimo_numero' => 2,
            ]
        );
    }

    public function test_numero_corto_de_contador_se_completa_a_cuatro_digitos(): void
    {
        $contador = $this->contador([
            'numero_registro' => 'CONT-25',
            'lectura_inicial' => 0,
        ]);

        $this->registrarLectura(
            $contador,
            10,
            '2026-09'
        )->assertSessionHasNoErrors();

        $this->assertSame(
            'REC-2026-0025-000001',
            Recibo::sole()->numero_recibo
        );
    }
}