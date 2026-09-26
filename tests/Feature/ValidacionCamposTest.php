<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\Support\FlujoTestCase;

class ValidacionCamposTest extends FlujoTestCase
{
    public function test_nuevo_cliente_no_hereda_documento_del_selector_y_edicion_carga_el_propio(): void
    {
        $cliente = $this->cliente(['dpi' => null, 'nit' => '1234K']);

        foreach ([route('clientes.create'), route('alta-servicio.create')] as $url) {
            $respuesta = $this->get($url)->assertOk();
            $documento = new \DOMDocument;
            $documento->loadHTML($respuesta->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($documento);

            $this->assertSame(1, $xpath->query('//input[@id="documento" and @name="dpi" and @value="" and @data-documento-original=""]')->length);
            $this->assertSame(1, $xpath->query('//input[@name="nombre" and @value=""]')->length);
        }

        $respuesta = $this->get(route('clientes.edit', $cliente))->assertOk();
        $documento = new \DOMDocument;
        $documento->loadHTML($respuesta->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($documento);

        $this->assertSame(1, $xpath->query('//input[@id="documento" and @name="nit" and @value="1234K" and @data-documento-original="1234K"]')->length);
        $this->assertSame(1, $xpath->query('//select[@name="tipo_documento"]/option[@value="nit" and @selected]')->length);
    }

    public function test_los_cinco_formularios_integran_validacion_y_contexto_autenticado(): void
    {
        $cliente = $this->cliente();

        foreach ([
            route('clientes.create') => ['documento', 'telefono'],
            route('clientes.edit', $cliente) => ['documento', 'telefono'],
            route('alta-servicio.create') => ['documento', 'telefono'],
            route('usuarios.create') => ['email'],
            route('usuarios.edit', $this->operador) => ['email'],
        ] as $url => $campos) {
            $respuesta = $this->get($url)->assertOk()
                ->assertSee('data-authenticated="true"', false);

            $documento = new \DOMDocument;
            $documento->loadHTML($respuesta->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($documento);
            $this->assertSame(1, $xpath->query('//form[@data-validacion-campos]')->length);

            foreach ($campos as $campo) {
                $this->assertSame(1, $xpath->query('//input[@id="'.$campo.'" and @data-validacion="'.$campo.'"]')->length);
                $this->assertSame(1, $xpath->query('//*[@id="'.$campo.'-feedback" and contains(@class, "visually-hidden")]')->length);
            }

            $this->assertSame(0, $xpath->query('//*[@data-titular-dpi]')->length);

            if (in_array('documento', $campos, true)) {
                $this->assertSame(1, $xpath->query('//select[@name="tipo_documento"]')->length);
                $this->assertSame(1, $xpath->query('//input[@id="documento" and @name="dpi"]')->length);
            }
        }
    }

    public function test_el_login_no_activa_las_funciones_del_panel(): void
    {
        Auth::logout();

        $this->get(route('login'))->assertOk()
            ->assertDontSee('data-authenticated="true"', false)
            ->assertDontSee('data-validacion-campos', false);
    }

    public function test_errores_del_backend_conservan_accesibilidad_sin_agregar_filas(): void
    {
        foreach ([
            'clientes.create' => 'dpi',
            'alta-servicio.create' => 'dpi',
            'clientes.edit' => 'nit',
            'usuarios.create' => 'email',
        ] as $ruta => $campo) {
            $mensaje = 'Este identificador ya fue registrado.';
            $url = $ruta === 'clientes.edit'
                ? route($ruta, $this->cliente(['dpi' => null, 'nit' => '1234K']))
                : route($ruta);
            $respuesta = $this->withSession([
                'errors' => ['default' => [
                    'format' => ':message',
                    'messages' => [$campo => [$mensaje]],
                ]],
                '_old_input' => [$campo => '123', 'tipo_cliente' => 'nuevo', 'tipo_documento' => $campo === 'nit' ? 'nit' : 'dpi'],
            ])->get($url)->assertOk();

            $this->assertSame(1, substr_count($respuesta->getContent(), $mensaje), 'El error debe aparecer una sola vez en '.$ruta);
            $documento = new \DOMDocument;
            $documento->loadHTML($respuesta->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($documento);
            $this->assertSame(1, $xpath->query('//input[@name="'.$campo.'" and @aria-invalid="true"]')->length);
            $id = in_array($campo, ['dpi', 'nit'], true) ? 'documento' : $campo;
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'-error" and contains(@class, "visually-hidden")]')->length);
        }
    }

    public function test_consulta_dpi_devuelve_solo_nombre_incluso_para_cliente_inactivo(): void
    {
        $cliente = $this->cliente([
            'nombre' => 'Titular de prueba',
            'dpi' => '1234567890123',
            'telefono' => '55558888',
            'direccion_principal' => 'Dirección de prueba',
            'activo' => false,
        ]);

        $respuesta = $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => 'dpi', 'dpi' => $cliente->dpi]);

        $respuesta->assertOk()->assertExactJson([
            'encontrado' => true,
            'nombre' => 'Titular de prueba',
        ]);
        $this->assertTrue($respuesta->headers->hasCacheControlDirective('private'));
        $this->assertTrue($respuesta->headers->hasCacheControlDirective('no-store'));
        $this->assertSame(1, Cliente::count());
    }

    public function test_consulta_dpi_inexistente_no_expone_datos_ni_crea_cliente(): void
    {
        $this->postJson(route('clientes.consultar-documento'), ['dpi' => '1234567890123'])
            ->assertOk()->assertExactJson(['encontrado' => false]);

        $this->assertSame(0, Cliente::count());
    }

    public function test_consulta_dpi_exige_trece_digitos_sin_modificar_registros_historicos(): void
    {
        foreach (['123', 'REG-ABCD-1234', str_repeat('á', 20)] as $dpi) {
            $this->cliente(['dpi' => $dpi, 'nombre' => 'Titular']);

            $this->postJson(route('clientes.consultar-documento'), ['tipo_documento' => 'dpi', 'dpi' => $dpi])
                ->assertUnprocessable()->assertJsonValidationErrors('dpi');

            $this->assertDatabaseHas('clientes', ['dpi' => $dpi, 'nombre' => 'Titular']);
        }
    }

    public function test_consulta_dpi_rechaza_vacio_tipo_invalido_longitud_y_caracteres_incorrectos(): void
    {
        foreach ([null, '', '   ', ['123'], str_repeat('1', 12), str_repeat('1', 14), '123456789012K', '１２３４５６７８９０１２３'] as $dpi) {
            $this->postJson(route('clientes.consultar-documento'), ['dpi' => $dpi])
                ->assertUnprocessable()->assertJsonValidationErrors('dpi');
        }
    }

    public function test_secretaria_puede_consultar_dpi_pero_no_validar_correo_de_usuarios(): void
    {
        $this->actingAs($this->usuario('Secretaria'));

        $this->postJson(route('clientes.consultar-documento'), ['dpi' => '1234567890123'])
            ->assertOk()->assertExactJson(['encontrado' => false]);
        $this->postJson(route('usuarios.validar-correo'), ['email' => 'usuario@example.com'])
            ->assertForbidden();
    }

    public function test_lector_y_usuario_inactivo_no_pueden_usar_las_consultas(): void
    {
        $inactivo = $this->usuario('Administrador');
        $inactivo->update(['activo' => false]);

        foreach ([$this->usuario('Lector'), $inactivo] as $usuario) {
            $this->actingAs($usuario);

            $this->postJson(route('clientes.consultar-documento'), ['dpi' => '1234567890123'])->assertForbidden();
            $this->postJson(route('usuarios.validar-correo'), ['email' => 'usuario@example.com'])
                ->assertForbidden();
        }
    }

    public function test_invitado_no_puede_usar_las_consultas(): void
    {
        Auth::logout();

        $this->postJson(route('clientes.consultar-documento'), ['dpi' => '1234567890123'])->assertUnauthorized();
        $this->postJson(route('usuarios.validar-correo'), ['email' => 'usuario@example.com'])
            ->assertUnauthorized();
    }

    public function test_formato_correo_usa_laravel_incluido_dominio_local(): void
    {
        $correoMaximo = str_repeat('a', 60).'@'.str_repeat('b', 60).'.'.str_repeat('c', 24).'.com';
        $this->assertSame(150, strlen($correoMaximo));

        foreach (['usuario@example.com', 'usuario@localhost', $correoMaximo] as $email) {
            $this->postJson(route('usuarios.validar-correo'), ['email' => $email])
                ->assertOk()->assertExactJson(['valido' => true]);
        }
    }

    public function test_formato_correo_rechaza_vacio_formato_incorrecto_y_limite_excedido(): void
    {
        $correoLargo = str_repeat('a', 61).'@'.str_repeat('b', 60).'.'.str_repeat('c', 24).'.com';
        $this->assertSame(151, strlen($correoLargo));

        foreach ([null, '', 'sin-arroba', 'user..name@example.com', 'user@example..com', $correoLargo] as $email) {
            $this->postJson(route('usuarios.validar-correo'), ['email' => $email])
                ->assertUnprocessable()->assertJsonValidationErrors('email');
        }
    }

    public function test_correo_duplicado_conserva_formato_valido_y_store_aplica_unicidad(): void
    {
        $this->postJson(route('usuarios.validar-correo'), ['email' => $this->operador->email])
            ->assertOk()->assertExactJson(['valido' => true]);

        $this->postJson(route('usuarios.store'), [
            'nombre' => 'Usuario duplicado',
            'email' => $this->operador->email,
            'rol_id' => $this->operador->rol_id,
            'password' => 'clave-de-prueba',
            'password_confirmation' => 'clave-de-prueba',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertSame(1, User::count());
    }

    public function test_consultas_usan_post_con_sesion_csrf_roles_y_limite_de_solicitudes(): void
    {
        app(Kernel::class);

        foreach ([
            'clientes.consultar-documento' => 'rol:Administrador,Secretaria',
            'usuarios.validar-correo' => 'rol:Administrador',
        ] as $nombre => $rol) {
            $ruta = Route::getRoutes()->getByName($nombre);

            $this->assertSame(['POST'], $ruta->methods());
            $this->assertContains('web', $ruta->gatherMiddleware());
            $this->assertContains('auth', $ruta->gatherMiddleware());
            $this->assertContains($rol, $ruta->gatherMiddleware());
            $this->assertContains('throttle:60,1', $ruta->gatherMiddleware());
            // Laravel omite CSRF durante las peticiones HTTP de PHPUnit;
            // comprobamos que sigue presente en la pila real de ambas rutas.
            $this->assertContains(PreventRequestForgery::class, app('router')->gatherRouteMiddleware($ruta));
        }
    }
}
