<?php

namespace Tests\Feature\Api;

use App\Models\Pago;
use Tests\Support\FlujoTestCase;

class ConsultaReciboPublicaTest extends FlujoTestCase
{
    public function test_rechaza_dpi_con_formato_invalido(): void
    {
        $this->cerrarSesion();

        $response = $this->postJson(
            '/api/public/consulta-recibo',
            [
                'dpi' => '12345',
                'numero_contador' => 'CONT-0001',
            ]
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['dpi']);

        $this->assertSame(
            'El DPI debe contener exactamente 13 dígitos.',
            $response->json('errors.dpi.0')
        );
    }

    public function test_devuelve_respuesta_generica_si_dpi_y_contador_no_coinciden(): void
    {
        $cliente = $this->cliente([
            'dpi' => '1234567890123',
        ]);

        $this->contador([
            'cliente_id' => $cliente->id,
            'numero_registro' => 'CONT-PUB-001',
        ]);

        $this->cerrarSesion();

        $response = $this->postJson(
            '/api/public/consulta-recibo',
            [
                'dpi' => '9999999999999',
                'numero_contador' => 'CONT-PUB-001',
            ]
        );

        $response
            ->assertNotFound()
            ->assertExactJson([
                'message' =>
                    'No se encontró información con los datos proporcionados.',
            ]);
    }

    public function test_devuelve_recibos_del_contador_sin_exponer_datos_sensibles(): void
    {
        $cliente = $this->cliente([
            'nombre' => 'Cliente Privado',
            'dpi' => '1234567890123',
            'telefono' => '5555-5555',
            'direccion_principal' => 'Dirección privada',
        ]);

        $contador = $this->contador([
            'cliente_id' => $cliente->id,
            'numero_registro' => 'CONT-PUB-001',
            'direccion_servicio' => 'Dirección del servicio',
        ]);

        $this->recibo(
            $contador,
            [
                'numero_recibo' => 'REC-PUB-001',
                'fecha_emision' => '2026-09-05',
                'monto' => 40,
                'estado' => 'PENDIENTE',
            ],
            [
                'periodo' => '2026-09-01',
                'fecha_lectura' => '2026-09-05',
                'lectura_anterior' => 100,
                'lectura_actual' => 118,
                'consumo_m3' => 18,
            ]
        );

        $this->cerrarSesion();

        $response = $this->postJson(
            '/api/public/consulta-recibo',
            [
                'dpi' => '1234567890123',
                'numero_contador' => 'CONT-PUB-001',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'recibos')
            ->assertJsonPath(
                'numero_contador',
                'CONT-PUB-001'
            )
            ->assertJsonPath(
                'recibos.0.numero_recibo',
                'REC-PUB-001'
            )
            ->assertJsonPath(
                'recibos.0.periodo',
                '2026-09'
            )
            ->assertJsonPath(
                'recibos.0.fecha_emision',
                '2026-09-05'
            )
            ->assertJsonPath(
                'recibos.0.fecha_vencimiento',
                '2026-09-10'
            )
            ->assertJsonPath(
                'recibos.0.estado',
                'PENDIENTE'
            )
            ->assertJsonPath(
                'recibos.0.lectura.consumo_m3',
                18
            )
            ->assertJsonPath(
                'recibos.0.mora_actual',
                0
            )
            ->assertJsonPath(
                'recibos.0.total_pagar',
                40
            )
            ->assertJsonPath(
                'recibos.0.pago',
                null
            );

        $this->assertNoExponeDatosPrivados(
            $response->json()
        );
    }

    public function test_recibo_pagado_muestra_solo_informacion_publica_del_pago(): void
    {
        $cliente = $this->cliente([
            'dpi' => '1234567890123',
        ]);

        $contador = $this->contador([
            'cliente_id' => $cliente->id,
            'numero_registro' => 'CONT-PAGADO-001',
        ]);

        $recibo = $this->recibo(
            $contador,
            [
                'numero_recibo' => 'REC-PAGADO-001',
                'fecha_emision' => '2026-09-05',
                'monto' => 40,
                'estado' => 'PAGADO',
            ],
            [
                'periodo' => '2026-09-01',
            ]
        );

        Pago::create([
            'recibo_id' => $recibo->id,
            'usuario_registro_id' => $this->operador->id,
            'metodo_pago_id' => $this->metodo->id,
            'monto' => 40,
            'fecha_pago' => '2026-09-06 09:30:00',
            'referencia' => 'REF-PRIVADA-001',
            'observacion' => 'Observación interna',
        ]);

        $this->cerrarSesion();

        $response = $this->postJson(
            '/api/public/consulta-recibo',
            [
                'dpi' => '1234567890123',
                'numero_contador' => 'CONT-PAGADO-001',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'recibos.0.estado',
                'PAGADO'
            )
            ->assertJsonPath(
                'recibos.0.pago.monto',
                40
            )
            ->assertJsonPath(
                'recibos.0.pago.fecha',
                '2026-09-06 09:30:00'
            )
            ->assertJsonPath(
                'recibos.0.pago.metodo',
                'Efectivo'
            )
            ->assertJsonPath(
                'recibos.0.mora_actual',
                0
            )
            ->assertJsonPath(
                'recibos.0.total_pagar',
                null
            );

        $this->assertNoExponeDatosPrivados(
            $response->json()
        );
    }

    private function cerrarSesion(): void
    {
        $this->app['auth']
            ->guard()
            ->logout();
    }

    private function assertNoExponeDatosPrivados(
        array $datos
    ): void {
        $clavesProhibidas = [
            'id',
            'dpi',
            'telefono',
            'email',
            'password',
            'direccion_principal',
            'direccion_servicio',
            'punto_referencia',
            'foto_ruta',
            'sector',
            'cliente_id',
            'contador_id',
            'lectura_id',
            'tarifa_id',
            'usuario_lector_id',
            'usuario_registro_id',
            'metodo_pago_id',
            'referencia',
            'observacion',
        ];

        foreach ($datos as $clave => $valor) {
            $this->assertNotContains(
                (string) $clave,
                $clavesProhibidas,
                "La API pública expuso la clave privada: {$clave}"
            );

            if (is_array($valor)) {
                $this->assertNoExponeDatosPrivados(
                    $valor
                );
            }
        }
    }
}