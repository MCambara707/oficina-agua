<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Pago;
use App\Models\Recibo;
use App\Models\Tarifa;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FlujoTestCase;

class FlujoFacturacionTest extends FlujoTestCase
{
    public function test_alta_nueva_y_existente_reutiliza_cliente_sin_duplicar_dpi(): void
    {
        $this->post(route('alta-servicio.store'), $this->datosAlta())->assertSessionHasNoErrors();
        $cliente = Cliente::sole();
        $this->assertDatabaseHas('contadores', ['cliente_id' => $cliente->id, 'lectura_inicial' => 450]);

        $datos = $this->datosAlta(['tipo_cliente' => 'existente', 'cliente_id' => $cliente->id]);
        unset($datos['nombre'], $datos['dpi']);
        $this->post(route('alta-servicio.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('contadores', 2);

        $this->post(route('alta-servicio.store'), $this->datosAlta())->assertSessionHasErrors('dpi');
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('contadores', 2);
    }

    public function test_alta_es_atomica_si_falla_la_insercion_del_contador(): void
    {
        DB::unprepared("CREATE TEMP TRIGGER fallo_contador BEFORE INSERT ON contadores BEGIN SELECT RAISE(ABORT, 'fallo controlado del fixture'); END");
        $response = $this->post(route('alta-servicio.store'), $this->datosAlta());
        $this->assertLessThan(500, $response->status());
        $this->assertDatabaseCount('clientes', 0);
        $this->assertDatabaseCount('contadores', 0);
    }

    public static function basesValidas(): array
    {
        return ['desconocida' => [null], 'cero real' => [0], 'tres decimales' => [450.125]];
    }

    #[DataProvider('basesValidas')]
    public function test_alta_y_mantenimiento_aceptan_bases_validas(?float $base): void
    {
        $this->post(route('alta-servicio.store'), $this->datosAlta(['lectura_inicial' => $base]))
            ->assertSessionHasNoErrors();
        $contador = Contador::sole();
        $this->assertSame($base === null ? null : number_format($base, 3, '.', ''), $contador->lectura_inicial);
        $datos = $this->datosContador($contador, ['numero_registro' => 'MANT-001']);
        $this->post(route('contadores.store'), $datos)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contadores', 2);
    }

    public static function basesInvalidas(): array
    {
        return ['negativa' => [-1], 'precision' => [0.0001], 'fuera de rango' => [1000000000]];
    }

    #[DataProvider('basesInvalidas')]
    public function test_alta_y_mantenimiento_rechazan_base_invalida(float $base): void
    {
        $this->post(route('alta-servicio.store'), $this->datosAlta(['lectura_inicial' => $base]))
            ->assertSessionHasErrors('lectura_inicial');
        $this->assertDatabaseCount('clientes', 0);
        $contador = $this->contador();
        $this->post(route('contadores.store'), $this->datosContador($contador, [
            'numero_registro' => 'MANT-INVALID', 'lectura_inicial' => $base,
        ]))->assertSessionHasErrors('lectura_inicial');
        $this->put(route('contadores.update', $contador), $this->datosContador($contador, ['lectura_inicial' => $base]))
            ->assertSessionHasErrors('lectura_inicial');
        $this->assertSame('0.000', $contador->fresh()->lectura_inicial);
    }

    public function test_primera_lectura_exige_base_conocida_y_muestra_mensaje(): void
    {
        $contador = $this->contador(['lectura_inicial' => null]);
        $response = $this->registrarLectura($contador, 465);
        $response->assertSessionHasErrors();
        $this->assertStringContainsString('lectura inicial', implode(' ', session('errors')->all()));
        $this->assertDatabaseCount('lecturas', 0);
        $this->assertDatabaseCount('recibos', 0);
    }

    public function test_primera_lectura_450_a_465_cobra_15_e_ignora_base_del_navegador(): void
    {
        $contador = $this->contador(['lectura_inicial' => 450]);
        $this->registrarLectura($contador, 465, '2026-09', [
            'lectura_anterior' => 0, 'lectura_inicial' => 0, 'consumo_m3' => 465, 'monto' => 99999,
        ])->assertRedirect(route('lecturas.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lecturas', ['lectura_anterior' => 450, 'lectura_actual' => 465, 'consumo_m3' => 15]);
        $this->assertSame('30.00', Recibo::sole()->monto);
        $this->assertSame('450.000', $contador->fresh()->lectura_inicial);
    }

    public function test_base_cero_permite_primera_lectura(): void
    {
        $this->registrarLectura($this->contador(), 15)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lecturas', ['lectura_anterior' => 0, 'consumo_m3' => 15]);
    }

    public function test_base_se_puede_completar_antes_del_historial_y_se_bloquea_despues(): void
    {
        $contador = $this->contador(['lectura_inicial' => null]);
        $this->put(route('contadores.update', $contador), $this->datosContador($contador, ['lectura_inicial' => 450]))
            ->assertSessionHasNoErrors();
        $this->registrarLectura($contador, 465)->assertSessionHasNoErrors();
        $this->put(route('contadores.update', $contador), $this->datosContador($contador->fresh(), ['lectura_inicial' => 0]))
            ->assertSessionHasErrors('lectura_inicial');
        $this->assertSame('450.000', $contador->fresh()->lectura_inicial);

        $datos = $this->datosContador($contador->fresh(), ['direccion_servicio' => 'Zona 3']);
        unset($datos['lectura_inicial']);
        $this->put(route('contadores.update', $contador), $datos)->assertSessionHasNoErrors();
        $this->assertSame('450.000', $contador->fresh()->lectura_inicial);
    }

    public static function consumos(): array
    {
        return ['normal' => [115, 15, 30], 'limite' => [120, 20, 40], 'exceso' => [125, 25, 65], 'sin consumo' => [100, 0, 0]];
    }

    #[DataProvider('consumos')]
    public function test_historial_prevalece_sobre_base_y_calcula_tarifa(float $actual, float $consumo, float $monto): void
    {
        $contador = $this->contador(['lectura_inicial' => 900]);
        $this->lectura($contador);
        $this->registrarLectura($contador, $actual)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lecturas', ['contador_id' => $contador->id, 'periodo' => '2026-09-01 00:00:00', 'lectura_anterior' => 100, 'consumo_m3' => $consumo]);
        $this->assertEquals($monto, (float) Recibo::sole()->monto);
    }

    public function test_historial_antiguo_con_base_null_continua_desde_ultima_lectura(): void
    {
        $contador = $this->contador(['lectura_inicial' => null]);
        $this->lectura($contador);
        $this->registrarLectura($contador)->assertSessionHasNoErrors();
        $this->assertSame('100.000', Lectura::latest('id')->first()->lectura_anterior);
    }

    public function test_rechaza_periodo_duplicado_anterior_y_lectura_menor_sin_datos_parciales(): void
    {
        $contador = $this->contador();
        $this->lectura($contador);
        $this->registrarLectura($contador, 115, '2026-08')->assertSessionHasErrors('periodo');
        $this->registrarLectura($contador, 115, '2026-07')->assertSessionHasErrors('periodo');
        $this->registrarLectura($contador, 99)->assertSessionHasErrors('lectura_actual');
        $this->assertDatabaseCount('lecturas', 1);
        $this->assertDatabaseCount('recibos', 0);
    }

    public function test_mes_siguiente_usa_ultima_lectura_y_permite_nuevo_recibo(): void
    {
        $contador = $this->contador(['lectura_inicial' => 450]);
        $this->registrarLectura($contador, 465)->assertSessionHasNoErrors();
        $this->registrarLectura($contador, 480, '2026-10')->assertSessionHasNoErrors();
        $this->assertDatabaseCount('recibos', 2);
        $this->assertSame('465.000', Lectura::latest('id')->first()->lectura_anterior);
        $this->assertSame('15.000', Lectura::latest('id')->first()->consumo_m3);
    }

    public function test_lecturas_y_contador_se_consultan_dentro_de_transaccion(): void
    {
        $contador = $this->contador();
        $this->lectura($contador);
        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            if (str_starts_with(strtolower($query->sql), 'select')
                && preg_match('/from "(lecturas|contadores)"/', $query->sql)
                && ! str_contains(strtolower($query->sql), 'count(*)')) {
                $queries[] = [$query->sql, $query->connection->transactionLevel()];
            }
        });
        $this->registrarLectura($contador)->assertSessionHasNoErrors();
        $this->assertNotEmpty($queries);
        foreach ($queries as [$sql, $level]) {
            $this->assertGreaterThanOrEqual(1, $level, $sql);
        }
    }

    public function test_fallo_de_recibo_revierte_lectura_y_no_expone_sql(): void
    {
        DB::unprepared("CREATE TEMP TRIGGER fallo_recibo BEFORE INSERT ON recibos BEGIN SELECT RAISE(ABORT, 'detalle SQL privado'); END");
        $this->registrarLectura($this->contador(), 15)->assertSessionHasErrors();
        $this->assertDatabaseCount('lecturas', 0);
        $this->assertDatabaseCount('recibos', 0);
        $this->assertStringNotContainsString('SQL', implode(' ', session('errors')->all()));
    }

    public function test_factura_con_tarifa_vigente_a_fecha_emision_sin_cambiar_pasado(): void
    {
        $contador = $this->contador();
        $anterior = $this->recibo($contador, ['fecha_emision' => '2026-08-05']);
        $this->tarifa->update(['vigente_hasta' => '2026-08-31']);
        $vigente = Tarifa::create(array_merge($this->tarifa->getAttributes(), [
            'nombre' => 'Tarifa septiembre', 'precio_por_m3' => 3,
            'vigente_desde' => '2026-09-01', 'vigente_hasta' => null,
        ]));
        $this->registrarLectura($contador)->assertSessionHasNoErrors();
        $nuevo = Recibo::latest('id')->first();
        $this->assertSame($vigente->id, $nuevo->tarifa_id);
        $this->assertSame('45.00', $nuevo->monto);
        $this->assertSame($this->tarifa->id, $anterior->fresh()->tarifa_id);
        $this->assertSame('40.00', $anterior->fresh()->monto);
    }

    public function test_no_factura_contador_inactivo_ni_sin_tarifa_vigente(): void
    {
        $contador = $this->contador(['activo' => false]);
        $this->registrarLectura($contador)->assertSessionHasErrors('contador_id');
        $contador->update(['activo' => true]);
        $this->tarifa->update(['vigente_hasta' => '2026-08-31']);
        $this->registrarLectura($contador)->assertSessionHasErrors('contador_id');
        $this->assertDatabaseCount('lecturas', 0);
    }

    public function test_mora_aplica_despues_del_dia_diez_una_vez_y_respeta_emision_tardia(): void
    {
        $recibo = $this->recibo();
        $this->travelTo(now()->setDate(2026, 9, 10)->endOfDay());
        $this->assertSame(0.0, $recibo->montoMora());
        $this->travelTo(now()->setDate(2026, 9, 11)->startOfDay());
        $this->assertSame(7.0, $recibo->montoMora());
        $this->travelTo(now()->addMonths(2));
        $this->assertSame(7.0, $recibo->montoMora());
        $recibo->fecha_emision = '2026-09-11';
        $this->assertSame('2026-10-10', $recibo->fechaVencimiento()->toDateString());
        $recibo->estado = 'ANULADO';
        $this->assertSame(0.0, $recibo->montoMora());
    }

    public function test_pago_calcula_total_real_con_mora_y_comprobante_guarda_importes_historicos(): void
    {
        $recibo = $this->recibo();
        $this->get(route('recibos.imprimir', $recibo))->assertOk()->assertViewHas('esComprobante', false);
        $this->travelTo(now()->setDate(2026, 9, 11));
        $this->pagar($recibo, ['referencia' => ' REF-1 '])->assertRedirect(route('recibos.imprimir', $recibo));
        $this->assertDatabaseHas('pagos', ['recibo_id' => $recibo->id, 'monto' => 47, 'referencia' => 'REF-1', 'usuario_registro_id' => $this->operador->id]);
        $this->assertSame('PAGADO', $recibo->fresh()->estado);
        $this->assertSame(0.0, $recibo->fresh()->montoMora());
        $this->travelTo(now()->addMonths(3));
        $this->get(route('recibos.imprimir', $recibo))->assertOk()
            ->assertViewHas('esComprobante', true)->assertViewHas('totalPagado', 47.0)->assertViewHas('moraPagada', 7.0);
    }

    public function test_pago_antes_de_vencer_cobra_solo_monto_y_rechaza_segundo_pago(): void
    {
        $recibo = $this->recibo();
        $this->pagar($recibo)->assertSessionHas('exito');
        $this->assertSame('40.00', Pago::sole()->monto);
        $this->pagar($recibo)->assertSessionHas('error');
        $this->assertDatabaseCount('pagos', 1);
        $this->get(route('pagos.create', $recibo))->assertRedirect(route('pagos.index'));
    }

    public function test_cero_anulado_y_metodo_inactivo_no_registran_pago(): void
    {
        $cero = $this->recibo(null, ['monto' => 0]);
        $this->pagar($cero)->assertSessionHas('error');
        $this->assertSame('PENDIENTE', $cero->fresh()->estado);
        $anulado = $this->recibo(null, ['estado' => 'ANULADO']);
        $this->pagar($anulado)->assertSessionHas('error');
        $this->metodo->update(['activo' => false]);
        $this->pagar($this->recibo())->assertSessionHasErrors('metodo_pago_id');
        $this->assertDatabaseCount('pagos', 0);
    }

    public function test_query_exception_al_actualizar_recibo_revierte_pago_y_no_revela_sql(): void
    {
        $recibo = $this->recibo();
        DB::unprepared("CREATE TEMP TRIGGER fallo_estado BEFORE UPDATE ON recibos BEGIN SELECT RAISE(ABORT, 'detalle SQL privado'); END");
        $this->pagar($recibo)->assertRedirect(route('pagos.index'))->assertSessionHas('error');
        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame('PENDIENTE', $recibo->fresh()->estado);
        $this->assertStringNotContainsString('SQL', session('error'));
        $this->assertStringNotContainsString('detalle', session('error'));
    }

    public function test_error_inesperado_en_pago_revierte_transaccion_sin_mostrar_detalles(): void
    {
        $recibo = $this->recibo();
        Recibo::updating(function (): void {
            throw new \LogicException('detalle privado del servidor');
        });
        $this->pagar($recibo)->assertSessionHas('error');
        $this->assertDatabaseCount('pagos', 0);
        $this->assertSame('PENDIENTE', $recibo->fresh()->estado);
        $this->assertStringNotContainsString('detalle privado', session('error'));
    }

    public function test_estado_de_cuenta_consolida_todos_los_contadores_y_excluye_pagados_y_anulados(): void
    {
        $cliente = $this->cliente();
        $a = $this->contador(['cliente_id' => $cliente->id]);
        $b = $this->contador(['cliente_id' => $cliente->id]);
        $pendiente = $this->recibo($a, ['fecha_emision' => '2026-08-05']);
        $pagado = $this->recibo($b);
        $this->pagar($pagado)->assertSessionHas('exito');
        $this->recibo($b, ['estado' => 'ANULADO', 'monto' => 200], ['periodo' => '2026-09-01']);
        $response = $this->get(route('dashboard.estado-cuenta', ['q' => $a->numero_registro]));
        $response->assertOk();
        $fila = $response->viewData('filas')->sole();
        $this->assertSame(2, $fila['contadores_total']);
        $this->assertSame(3, $fila['total_recibos']);
        $this->assertSame(1, $fila['recibos_pendientes']);
        $this->assertSame(1, $fila['recibos_pagados']);
        $this->assertSame(1, $fila['recibos_anulados']);
        $this->assertSame(47.0, $fila['total']);
        $this->assertSame('con-mora', $fila['estado_clave']);
        $this->pagar($pendiente)->assertSessionHas('exito');
        $this->get(route('dashboard.estado-cuenta', ['q' => $cliente->dpi]))
            ->assertViewHas('resumenGeneral', fn ($resumen) => $resumen['saldo_total'] === 0.0 && $resumen['al_dia'] === 1);
    }

    public function test_lector_solo_accede_lecturas_e_impresion_y_no_altera_base(): void
    {
        $contador = $this->contador(['lectura_inicial' => 450]);
        $this->actingAs($this->usuario('Lector'));
        foreach (['alta-servicio.create', 'contadores.index', 'clientes.index', 'tarifas.index', 'servicios.index', 'pagos.index', 'recibos.index', 'usuarios.index', 'dashboard.estado-cuenta'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->put(route('contadores.update', $contador), $this->datosContador($contador, ['lectura_inicial' => 0]))->assertForbidden();
        $this->post(route('alta-servicio.store'), $this->datosAlta())->assertForbidden();
        $this->registrarLectura($contador, 465, '2026-09', ['lectura_inicial' => 0])->assertSessionHasNoErrors();
        $this->assertSame('450.000', $contador->fresh()->lectura_inicial);
        $recibo = Recibo::sole();
        $this->pagar($recibo)->assertForbidden();
        $this->get(route('recibos.imprimir', $recibo))->assertOk();
        $this->get(route('lecturas.index'))->assertOk()
            ->assertDontSee('href="'.route('alta-servicio.create').'"', false)
            ->assertDontSee('href="'.route('pagos.index').'"', false)
            ->assertDontSee('href="'.route('recibos.index').'"', false);
    }

    public function test_secretaria_tiene_operacion_y_menu_sin_administrar_usuarios(): void
    {
        $this->actingAs($this->usuario('Secretaria'));
        foreach (['alta-servicio.create', 'contadores.index', 'pagos.index', 'recibos.index', 'dashboard.estado-cuenta', 'lecturas.index'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('usuarios.index'))->assertForbidden();
        $this->get(route('lecturas.index'))
            ->assertSee('href="'.route('alta-servicio.create').'"', false)
            ->assertSee('href="'.route('pagos.index').'"', false)
            ->assertSee('href="'.route('recibos.index').'"', false)
            ->assertDontSee('href="'.route('usuarios.index').'"', false);
    }

    public function test_usuario_o_rol_inactivo_no_puede_facturar(): void
    {
        $contador = $this->contador();
        $this->operador->update(['activo' => false]);
        $this->registrarLectura($contador)->assertForbidden();
        $this->operador->update(['activo' => true]);
        $this->operador->rol->update(['activo' => false]);
        $this->registrarLectura($contador)->assertForbidden();
        $this->assertDatabaseCount('lecturas', 0);
    }
}
