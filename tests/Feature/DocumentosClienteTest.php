<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Contador;
use Tests\Support\FlujoTestCase;

class DocumentosClienteTest extends FlujoTestCase
{
    public function test_consulta_nit_normaliza_guion_y_k_y_devuelve_solo_titular(): void
    {
        $this->cliente(['dpi' => null, 'nit' => '1234567K', 'nombre' => 'Titular NIT', 'activo' => false]);

        foreach (['1234567K', '1234567-K', ' 1234567-k '] as $nit) {
            $respuesta = $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => 'nit', 'nit' => $nit]);

            $respuesta->assertOk()->assertExactJson(['encontrado' => true, 'nombre' => 'Titular NIT']);
            $this->assertTrue($respuesta->headers->hasCacheControlDirective('private'));
            $this->assertTrue($respuesta->headers->hasCacheControlDirective('no-store'));
        }
    }

    public function test_nit_admite_longitud_variable_y_dpi_tiene_trece_digitos(): void
    {
        foreach (['12', '1-K', '12345678', '123456789', '1234567890123', '123456789012-K'] as $nit) {
            $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => 'nit', 'nit' => $nit])
                ->assertOk()->assertExactJson(['encontrado' => false]);
        }

        $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => 'dpi', 'dpi' => '1234567890123'])
            ->assertOk()->assertExactJson(['encontrado' => false]);
        $this->assertSame(0, Cliente::count());
    }

    public function test_nit_rechaza_longitud_caracteres_guiones_intermedios_y_consumidor_final(): void
    {
        foreach ([null, '', ['1234'], '1', 'K', '01234', '12345678901234', '12A3', 'K123', '12--3', '1-23', '12 3', 'CF'] as $nit) {
            $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => 'nit', 'nit' => $nit])
                ->assertUnprocessable()->assertJsonValidationErrors('nit');
        }
    }

    public function test_tipo_documento_invalido_no_permite_consultar_otra_columna(): void
    {
        foreach (['nombre', 'pasaporte', '', ['dpi', 'nit']] as $tipo) {
            $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => $tipo, 'dpi' => '1234567890123'])
                ->assertUnprocessable()->assertJsonValidationErrors('tipo_documento');
        }
    }

    public function test_consulta_usa_solo_documento_seleccionado(): void
    {
        $this->cliente(['dpi' => '1234567890123', 'nombre' => 'Titular DPI']);
        $this->cliente(['dpi' => null, 'nit' => '1234K', 'nombre' => 'Titular NIT']);

        $this->postJson(route('clientes.consultar-documento'), [
            'tipo_documento' => 'nit', 'nit' => '1234-K', 'dpi' => '1234567890123',
        ])->assertOk()->assertExactJson(['encontrado' => true, 'nombre' => 'Titular NIT']);

        $this->postJson(route('clientes.consultar-documento'), [
            'tipo_documento' => 'dpi', 'nit' => ['inválido'], 'dpi' => '1234567890123',
        ])->assertOk()->assertExactJson(['encontrado' => true, 'nombre' => 'Titular DPI']);
    }

    public function test_crear_con_nit_normaliza_antes_de_guardar_y_excluye_dpi(): void
    {
        $this->post(route('clientes.store'), [
            'nombre' => 'Cliente NIT', 'tipo_documento' => 'nit', 'nit' => ' 1234567-k ', 'dpi' => 'basura', 'activo' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', ['nombre' => 'Cliente NIT', 'dpi' => null, 'nit' => '1234567K']);
    }

    public function test_crear_con_dpi_sin_selector_conserva_compatibilidad_y_excluye_nit(): void
    {
        $this->post(route('clientes.store'), [
            'nombre' => 'Cliente DPI', 'dpi' => ' 1234567890123 ', 'nit' => ['inválido'],
        ])->assertSessionHasNoErrors()->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', ['nombre' => 'Cliente DPI', 'dpi' => '1234567890123', 'nit' => null]);
    }

    public function test_crear_rechaza_nit_duplicado_aunque_cambien_guion_o_mayusculas(): void
    {
        $this->cliente(['dpi' => null, 'nit' => '1234K']);

        $this->postJson(route('clientes.store'), [
            'nombre' => 'Duplicado', 'tipo_documento' => 'nit', 'nit' => '1234-k',
        ])->assertUnprocessable()->assertJsonValidationErrors('nit');

        $this->assertSame(1, Cliente::count());
    }

    public function test_crear_rechaza_dpi_incompleto_y_nit_sin_valor(): void
    {
        $this->postJson(route('clientes.store'), [
            'nombre' => 'Inválido', 'tipo_documento' => 'dpi', 'dpi' => '123',
        ])->assertUnprocessable()->assertJsonValidationErrors('dpi');

        $this->postJson(route('clientes.store'), [
            'nombre' => 'Inválido', 'tipo_documento' => 'nit', 'dpi' => '1234567890123',
        ])->assertUnprocessable()->assertJsonValidationErrors('nit');

        $this->assertSame(0, Cliente::count());
    }

    public function test_editar_permite_documento_propio_y_cambiar_entre_tipos(): void
    {
        $cliente = $this->cliente();

        foreach ([
            ['tipo_documento' => 'dpi', 'dpi' => $cliente->dpi, 'nit' => null],
            ['tipo_documento' => 'nit', 'nit' => '1234-k', 'dpi' => 'ignorado'],
            ['tipo_documento' => 'nit', 'nit' => '1234K', 'dpi' => null],
            ['tipo_documento' => 'dpi', 'dpi' => '9876543210123', 'nit' => 'ignorado'],
        ] as $documento) {
            $this->put(route('clientes.update', $cliente), ['nombre' => 'Cliente editado', ...$documento])
                ->assertSessionHasNoErrors()->assertRedirect(route('clientes.index'));

            $cliente->refresh();
            $this->assertSame($documento['tipo_documento'] === 'dpi' ? $documento['dpi'] : null, $cliente->dpi);
            $this->assertSame($documento['tipo_documento'] === 'nit' ? '1234K' : null, $cliente->nit);
        }
    }

    public function test_editar_rechaza_documentos_de_otro_cliente_sin_perder_original(): void
    {
        $cliente = $this->cliente();
        $dpiOriginal = $cliente->dpi;
        $otro = $this->cliente();
        $this->cliente(['dpi' => null, 'nit' => '1234K']);

        foreach ([['tipo_documento' => 'dpi', 'dpi' => $otro->dpi], ['tipo_documento' => 'nit', 'nit' => '1234-K']] as $documento) {
            $this->putJson(route('clientes.update', $cliente), ['nombre' => 'Duplicado', ...$documento])
                ->assertUnprocessable()->assertJsonValidationErrors($documento['tipo_documento']);

            $this->assertSame($dpiOriginal, $cliente->refresh()->dpi);
            $this->assertNull($cliente->nit);
        }
    }

    public function test_alta_crea_cliente_nit_y_contador_en_la_misma_operacion(): void
    {
        $this->post(route('alta-servicio.store'), $this->datosAlta([
            'nombre' => 'Alta con NIT', 'tipo_documento' => 'nit', 'nit' => '1234-k',
        ]))->assertSessionHasNoErrors()->assertSessionHas('exito')->assertRedirect();

        $cliente = Cliente::where('nit', '1234K')->sole();
        $this->assertNull($cliente->dpi);
        $this->assertTrue($cliente->activo);
        $this->assertSame($cliente->id, Contador::sole()->cliente_id);
    }

    public function test_alta_rechaza_nit_duplicado_sin_crear_cliente_ni_contador(): void
    {
        $this->cliente(['dpi' => null, 'nit' => '1234K']);

        $this->postJson(route('alta-servicio.store'), $this->datosAlta([
            'tipo_documento' => 'nit', 'nit' => '1234-k',
        ]))->assertUnprocessable()->assertJsonValidationErrors('nit');

        $this->assertSame(1, Cliente::count());
        $this->assertSame(0, Contador::count());
    }

    public function test_alta_cliente_existente_no_exige_documento_nuevo(): void
    {
        $cliente = $this->cliente(['dpi' => null, 'nit' => '1234K']);

        $this->post(route('alta-servicio.store'), $this->datosAlta([
            'tipo_cliente' => 'existente', 'cliente_id' => $cliente->id,
            'tipo_documento' => 'inválido', 'dpi' => ['inválido'], 'nit' => ['inválido'],
        ]))->assertSessionHasNoErrors()->assertSessionHas('exito')->assertRedirect();

        $this->assertSame(1, Cliente::count());
        $this->assertSame($cliente->id, Contador::sole()->cliente_id);
        $this->assertSame('1234K', $cliente->refresh()->nit);
    }
}
