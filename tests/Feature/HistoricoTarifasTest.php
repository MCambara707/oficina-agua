<?php

namespace Tests\Feature;

use App\Models\Recibo;
use App\Models\Tarifa;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FlujoTestCase;

class HistoricoTarifasTest extends FlujoTestCase
{
    public function test_consulta_general_incluye_todos_los_estados_y_filtra_cliente_contador_y_numero(): void
    {
        $cliente = $this->cliente(['nombre' => 'Ana Perez', 'dpi' => '1234567890123']);
        $contador = $this->contador(['cliente_id' => $cliente->id, 'numero_registro' => 'AGUA-ANA']);
        $ana = $this->recibo($contador, ['numero_recibo' => 'REC-ANA']);
        $pagado = $this->recibo();
        $this->pagar($pagado)->assertSessionHas('exito');
        $anulado = $this->recibo(null, ['estado' => 'ANULADO']);

        $this->get(route('recibos.index'))->assertOk()
            ->assertViewHas('recibos', fn ($recibos) => $recibos->total() === 3)
            ->assertSee($ana->numero_recibo)->assertSee($pagado->numero_recibo)->assertSee($anulado->numero_recibo);

        foreach (['Ana Perez', '1234567890123', 'AGUA-ANA', 'REC-ANA'] as $busqueda) {
            $this->get(route('recibos.index', ['q' => $busqueda]))->assertOk()
                ->assertViewHas('recibos', fn ($recibos) => $recibos->total() === 1 && $recibos->first()->id === $ana->id);
        }

        $this->get(route('recibos.index', ['estado' => 'PAGADO']))->assertOk()
            ->assertViewHas('recibos', fn ($recibos) => $recibos->total() === 1 && $recibos->first()->id === $pagado->id);
        $this->get(route('recibos.index', ['estado' => 'ANULADO']))->assertOk()
            ->assertViewHas('recibos', fn ($recibos) => $recibos->total() === 1 && $recibos->first()->id === $anulado->id);
    }

    public function test_filtros_historicos_combinados_usan_periodo_de_lectura_no_fecha_emision(): void
    {
        $contador = $this->contador();
        $agosto = $this->recibo($contador, ['numero_recibo' => 'AGOSTO'], ['periodo' => '2026-08-01']);
        $this->recibo($contador, ['numero_recibo' => 'SEPTIEMBRE'], ['periodo' => '2026-09-01']);
        $this->recibo(null, ['estado' => 'ANULADO'], ['periodo' => '2026-08-01']);
        $this->get(route('recibos.index', [
            'q' => $contador->numero_registro, 'periodo' => '2026-08', 'estado' => 'PENDIENTE',
        ]))->assertOk()->assertViewHas('recibos', fn ($recibos) => $recibos->total() === 1 && $recibos->first()->id === $agosto->id);
    }

    public function test_consulta_historica_valida_filtros_y_conserva_paginacion(): void
    {
        $this->get(route('recibos.index', ['estado' => 'INVENTADO']))->assertSessionHasErrors('estado');
        $this->get(route('recibos.index', ['periodo' => '2026-99']))->assertSessionHasErrors('periodo');
        for ($i = 0; $i < 17; $i++) {
            $this->recibo(null, ['numero_recibo' => 'LOTE-'.$i]);
        }
        $this->get(route('recibos.index', ['q' => 'LOTE', 'estado' => 'PENDIENTE']))
            ->assertOk()->assertViewHas('recibos', function ($recibos): bool {
                return $recibos->total() === 17
                    && $recibos->hasMorePages()
                    && str_contains($recibos->nextPageUrl(), 'q=LOTE')
                    && str_contains($recibos->nextPageUrl(), 'estado=PENDIENTE');
            });
    }

    public static function cambiosHistoricos(): array
    {
        return [
            'precio normal' => ['precio_por_m3', 9],
            'precio exceso' => ['precio_exceso_m3', 9],
            'mora porcentaje' => ['mora_porcentaje', 25],
            'mora fija' => ['mora_monto_fijo', 10],
            'nombre' => ['nombre', 'Reescribir historia'],
            'tipo capacidad' => ['tipo_selector', '2 pajas'],
            'vigencia inicial' => ['vigente_desde', '2025-12-01'],
        ];
    }

    #[DataProvider('cambiosHistoricos')]
    public function test_tarifa_utilizada_no_permite_reescribir_datos_historicos(string $campo, mixed $valor): void
    {
        // El mantenimiento resuelve 1 paja como 60 m³. El fixture de cálculo usa 20.
        $this->tarifa->update(['capacidad' => 60]);
        $recibo = $this->recibo(null, ['fecha_emision' => '2026-08-05']);
        $antes = $this->tarifa->fresh()->getAttributes();
        $this->put(route('tarifas.update', $this->tarifa), $this->datosTarifa([$campo => $valor]))
            ->assertSessionHasErrors();
        $this->assertSame($antes, $this->tarifa->fresh()->getAttributes());
        $this->assertSame(7.0, $recibo->fresh()->montoMora());
    }

    public function test_tarifa_utilizada_permite_cierre_posterior_sin_excluir_emisiones_historicas(): void
    {
        $this->tarifa->update(['capacidad' => 60]);
        $this->recibo(null, ['fecha_emision' => '2026-09-05']);
        $this->put(route('tarifas.update', $this->tarifa), $this->datosTarifa(['vigente_hasta' => '2026-08-31']))
            ->assertSessionHasErrors('vigente_hasta');
        $this->assertNull($this->tarifa->fresh()->vigente_hasta);
        $this->put(route('tarifas.update', $this->tarifa), $this->datosTarifa(['vigente_hasta' => '2026-09-30']))
            ->assertSessionHasNoErrors();
        $this->assertSame('2026-09-30', $this->tarifa->fresh()->vigente_hasta->toDateString());
        $this->assertSame(1, Recibo::count());
    }

    public function test_nueva_tarifa_cierra_anterior_sin_reescribir_importes_historicos(): void
    {
        $this->tarifa->update(['capacidad' => 60]);
        $recibo = $this->recibo(null, ['fecha_emision' => '2026-08-05']);
        $this->post(route('tarifas.store'), $this->datosTarifa([
            'nombre' => 'Tarifa siguiente', 'vigente_desde' => '2026-09-01', 'precio_por_m3' => 3,
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tarifas', 2);
        $this->assertSame('2026-08-31', $this->tarifa->fresh()->vigente_hasta->toDateString());
        $this->assertSame('2.00', $this->tarifa->fresh()->precio_por_m3);
        $this->assertSame($this->tarifa->id, $recibo->fresh()->tarifa_id);
        $this->assertSame(7.0, $recibo->fresh()->montoMora());
    }

    public function test_nueva_tarifa_no_puede_cerrar_anterior_antes_de_recibo_ya_emitido(): void
    {
        $this->tarifa->update(['capacidad' => 60]);
        $this->recibo(null, ['fecha_emision' => '2026-09-05']);
        $this->post(route('tarifas.store'), $this->datosTarifa([
            'nombre' => 'Tarifa incompatible', 'vigente_desde' => '2026-09-01',
        ]))->assertSessionHasErrors();
        $this->assertDatabaseCount('tarifas', 1);
        $this->assertNull($this->tarifa->fresh()->vigente_hasta);
    }

    public function test_tarifa_en_uso_no_se_elimina_y_tipo_asignado_no_se_cambia(): void
    {
        $this->tarifa->update(['capacidad' => 60]);
        $this->contador();
        $this->delete(route('tarifas.destroy', $this->tarifa))->assertSessionHas('error');
        $this->put(route('tarifas.update', $this->tarifa), $this->datosTarifa(['tipo_selector' => '2 pajas']))
            ->assertSessionHasErrors();
        $this->assertDatabaseCount('tarifas', 1);
        $this->assertSame('1 paja', $this->tarifa->fresh()->tipo);
    }

    public function test_administrador_ve_usuarios_y_cliente_con_historial_no_se_elimina(): void
    {
        $recibo = $this->recibo();
        $this->get(route('usuarios.index'))->assertOk();
        $this->delete(route('clientes.destroy', $recibo->lectura->contador->cliente))->assertSessionHas('error');
        $this->delete(route('contadores.destroy', $recibo->lectura->contador))->assertSessionHas('error');
        $this->assertDatabaseCount('clientes', 1);
        $this->assertDatabaseCount('contadores', 1);
        $this->assertDatabaseCount('recibos', 1);
    }
}
