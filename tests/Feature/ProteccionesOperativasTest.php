<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Recibo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FlujoTestCase;

class ProteccionesOperativasTest extends FlujoTestCase
{
    public function test_flujo_integral_desde_alta_hasta_comprobante_y_estado_de_cuenta(): void
    {
        $this->post(route('alta-servicio.store'), $this->datosAlta(['lectura_inicial' => 450]))
            ->assertSessionHasNoErrors()->assertSessionHas('exito');
        $contador = Contador::sole();
        $this->assertTrue($contador->activo);
        $this->actingAs($this->usuario('Lector'));
        $this->registrarLectura($contador, 465)->assertSessionHasNoErrors();
        $recibo = Recibo::sole();
        $this->get(route('recibos.imprimir', $recibo))->assertOk()
            ->assertViewHas('esComprobante', false)->assertViewHas('totalActual', 30.0)
            ->assertSee($contador->cliente->dpi)->assertSee($contador->numero_registro)
            ->assertSee($this->servicio->nombre)->assertSee($this->tarifa->nombre);

        $this->travelTo(now()->setDate(2026, 9, 11));
        $this->actingAs($this->usuario('Secretaria'));
        $this->get(route('pagos.create', $recibo))->assertOk()->assertSee('Q36.00');
        $this->get(route('dashboard.estado-cuenta'))->assertViewHas('filas', fn ($filas) => $filas->first()['estado_clave'] === 'con-mora');
        $this->pagar($recibo, ['referencia' => 'CAJA-1', 'observacion' => 'Cobro en oficina'])
            ->assertRedirect(route('recibos.imprimir', $recibo));
        $this->get(route('recibos.imprimir', $recibo))->assertOk()
            ->assertViewHas('esComprobante', true)->assertViewHas('moraPagada', 6.0)
            ->assertViewHas('totalPagado', 36.0)->assertSee('Efectivo')->assertSee('CAJA-1');
        $this->get(route('recibos.index', ['q' => $contador->cliente->dpi, 'estado' => 'PAGADO']))
            ->assertOk()->assertSee($recibo->numero_recibo)->assertSee('Comprobante');
        $this->get(route('dashboard.estado-cuenta'))->assertViewHas('filas', fn ($filas) => $filas->first()['estado_clave'] === 'al-dia' && $filas->first()['total'] === 0.0);
    }

    public function test_desactivar_tarifa_importada_conserva_capacidad_historica(): void
    {
        $this->recibo();
        $this->put(route('tarifas.update', $this->tarifa), $this->datosTarifa(['activo' => 0]))
            ->assertSessionHasNoErrors();
        $this->assertFalse($this->tarifa->fresh()->activo);
        $this->assertSame('20.000', $this->tarifa->fresh()->capacidad);
        $this->assertSame('40.00', Recibo::sole()->monto);
    }

    public function test_formulario_no_presenta_base_desconocida_como_cero(): void
    {
        $contador = $this->contador(['lectura_inicial' => null]);
        $this->get(route('lecturas.create', ['contador_id' => $contador->id]))->assertOk()
            ->assertSee('Falta la lectura inicial')->assertDontSee('Guardar lectura');
        $contador->update(['lectura_inicial' => 0]);
        $this->get(route('lecturas.create', ['contador_id' => $contador->id]))->assertOk()
            ->assertSee('Guardar lectura')->assertViewHas('lecturaAnterior', '0.000');
    }

    public function test_caso_solicitado_de_27_metros_cobra_20_normales_y_7_excedentes(): void
    {
        $contador = $this->contador();
        $this->lectura($contador);
        $this->registrarLectura($contador, 127)->assertSessionHasNoErrors();
        $recibo = Recibo::sole();
        $this->assertSame('27.000', $recibo->lectura->consumo_m3);
        $this->assertSame('75.00', $recibo->monto);
        $this->assertStringContainsString('Exceso: 7.000', $recibo->observacion);
    }

    public function test_fallo_del_alta_no_deja_foto_ni_cliente_parcial(): void
    {
        Storage::fake('public');
        DB::unprepared("CREATE TEMP TRIGGER fallo_foto BEFORE INSERT ON contadores BEGIN SELECT RAISE(ABORT, 'fallo de prueba'); END");
        $this->post(route('alta-servicio.store'), $this->datosAlta([
            'foto' => UploadedFile::fake()->image('contador.jpg'),
        ]))->assertSessionHas('error');
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertSame(0, Cliente::count());
    }

    public function test_fallo_de_edicion_conserva_foto_anterior_y_elimina_nueva(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('contadores/anterior.jpg', 'foto de prueba');
        $contador = $this->contador(['foto_ruta' => 'contadores/anterior.jpg']);
        DB::unprepared("CREATE TEMP TRIGGER fallo_edicion BEFORE UPDATE ON contadores BEGIN SELECT RAISE(ABORT, 'fallo de prueba'); END");
        $this->put(route('contadores.update', $contador), $this->datosContador($contador, [
            'foto' => UploadedFile::fake()->image('nueva.jpg'),
        ]))->assertSessionHas('error');
        $this->assertSame(['contadores/anterior.jpg'], Storage::disk('public')->allFiles());
        $this->assertSame('contadores/anterior.jpg', $contador->fresh()->foto_ruta);
    }

    public function test_contador_sin_historial_se_elimina_junto_con_su_foto(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('contadores/vigente.jpg', 'foto de prueba');
        $contador = $this->contador(['foto_ruta' => 'contadores/vigente.jpg']);
        $this->delete(route('contadores.destroy', $contador))->assertSessionHas('exito');
        $this->assertSame(0, Contador::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_historial_impide_trasladar_deuda_a_otro_cliente(): void
    {
        $contador = $this->contador();
        $recibo = $this->recibo($contador);
        $otro = $this->cliente();
        $this->put(route('contadores.update', $contador), $this->datosContador($contador, ['cliente_id' => $otro->id]))
            ->assertSessionHasErrors('cliente_id');
        $this->assertSame($contador->cliente_id, $recibo->fresh()->lectura->contador->cliente_id);
    }

    public function test_asignaciones_inactivas_se_rechazan_en_alta_y_edicion(): void
    {
        $contador = $this->contador();
        $inactivo = $this->cliente(['activo' => false]);
        $this->post(route('alta-servicio.store'), $this->datosAlta([
            'tipo_cliente' => 'existente', 'cliente_id' => $inactivo->id,
        ]))->assertSessionHasErrors('cliente_id');
        $this->put(route('contadores.update', $contador), $this->datosContador($contador, ['cliente_id' => $inactivo->id]))
            ->assertSessionHasErrors('cliente_id');
        $this->servicio->update(['activo' => false]);
        $this->post(route('alta-servicio.store'), $this->datosAlta())->assertSessionHasErrors('servicio_id');
        $this->tarifa->update(['activo' => false]);
        $this->post(route('contadores.store'), $this->datosContador($contador, ['numero_registro' => 'INACTIVO']))
            ->assertSessionHasErrors(['tarifa_id', 'servicio_id']);
    }

    public function test_busqueda_clientes_y_estado_de_cuenta_filtran_cliente_correcto(): void
    {
        $cliente = $this->cliente(['nombre' => 'Elena Agua', 'dpi' => '5555555555555', 'telefono' => '55558888']);
        $this->cliente(['nombre' => 'Otro cliente']);
        $contador = $this->contador(['cliente_id' => $cliente->id, 'numero_registro' => 'ELENA-01']);
        foreach (['Elena Agua', '5555555555555', '55558888'] as $busqueda) {
            $this->get(route('clientes.index', ['q' => $busqueda]))->assertOk()
                ->assertViewHas('clientes', fn ($clientes) => $clientes->total() === 1 && $clientes->first()->id === $cliente->id);
        }
        foreach (['Elena Agua', '5555555555555', $contador->numero_registro] as $busqueda) {
            $this->get(route('dashboard.estado-cuenta', ['busqueda' => $busqueda]))->assertOk()
                ->assertViewHas('filas', fn ($filas) => $filas->count() === 1 && $filas->first()['cliente']->id === $cliente->id);
        }
    }

    public function test_secretaria_cobra_y_estado_pasa_de_pendiente_a_al_dia(): void
    {
        $recibo = $this->recibo();
        $this->actingAs($this->usuario('Secretaria'));
        $this->get(route('dashboard.estado-cuenta'))->assertViewHas('filas', fn ($filas) => $filas->first()['estado_clave'] === 'pendiente');
        $this->pagar($recibo)->assertRedirect(route('recibos.imprimir', $recibo));
        $this->get(route('dashboard.estado-cuenta'))->assertViewHas('filas', fn ($filas) => $filas->first()['estado_clave'] === 'al-dia');
    }
}
