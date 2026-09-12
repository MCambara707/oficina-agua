<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FlujoTestCase;

class AutenticacionTest extends FlujoTestCase
{
    public static function destinosPorRol(): array
    {
        return [
            'Administrador' => ['Administrador', 'dashboard.estado-cuenta'],
            'Secretaria' => ['Secretaria', 'dashboard.estado-cuenta'],
            'Lector' => ['Lector', 'lecturas.index'],
        ];
    }

    #[DataProvider('destinosPorRol')]
    public function test_inicio_y_login_con_sesion_llevan_a_un_modulo_permitido(
        string $rol,
        string $destino
    ): void {
        $this->actingAs($this->usuario($rol));

        $this->get(route('inicio'))->assertRedirect(route($destino));
        $this->get(route('login'))->assertRedirect(route('inicio'));
        $this->get(route($destino))->assertOk();
    }

    #[DataProvider('destinosPorRol')]
    public function test_login_envia_cada_rol_a_su_modulo_operativo(
        string $rol,
        string $destino
    ): void {
        $usuario = $this->usuario($rol);
        Auth::logout();
        $this->startSession();
        $sesionAnterior = session()->getId();

        $this->post(route('login.procesar'), [
            'email' => $usuario->email,
            'password' => 'clave-solo-pruebas',
        ])->assertRedirect(route($destino))->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($usuario);
        $this->assertNotSame($sesionAnterior, session()->getId());
        $this->get(route($destino))->assertOk();
    }

    public function test_login_conserva_el_destino_intended_permitido(): void
    {
        $usuario = $this->usuario('Lector');
        Auth::logout();
        $destino = route('lecturas.create', ['contador_id' => $this->contador()->id]);

        $this->get($destino)->assertRedirect(route('login'));

        $this->post(route('login.procesar'), [
            'email' => $usuario->email,
            'password' => 'clave-solo-pruebas',
        ])->assertRedirect($destino);

        $this->assertAuthenticatedAs($usuario);
        $this->get($destino)->assertOk();
    }

    public function test_login_descarta_un_destino_demo_guardado_en_una_sesion_anterior(): void
    {
        $usuario = $this->usuario('Lector');
        Auth::logout();

        $this->withSession(['url.intended' => url('/admin-demo/?origen=anterior')])
            ->post(route('login.procesar'), [
                'email' => $usuario->email,
                'password' => 'clave-solo-pruebas',
            ])->assertRedirect(route('lecturas.index'));

        $this->assertAuthenticatedAs($usuario);
        $this->assertFalse(session()->has('url.intended'));
    }

    public static function rolesSinAdministracionDeUsuarios(): array
    {
        return [
            'Secretaria' => ['Secretaria'],
            'Lector' => ['Lector'],
        ];
    }

    #[DataProvider('rolesSinAdministracionDeUsuarios')]
    public function test_intended_no_elude_los_permisos_del_modulo(string $rol): void
    {
        $usuario = $this->usuario($rol);
        Auth::logout();
        $destino = route('usuarios.index');

        $this->get($destino)->assertRedirect(route('login'));
        $this->post(route('login.procesar'), [
            'email' => $usuario->email,
            'password' => 'clave-solo-pruebas',
        ])->assertRedirect($destino);

        $this->get($destino)->assertForbidden();
    }

    public function test_credenciales_incorrectas_conservan_error_y_no_autentican(): void
    {
        Auth::logout();

        $this->from(route('login'))->post(route('login.procesar'), [
            'email' => $this->operador->email,
            'password' => 'clave-incorrecta',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $this->operador->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_usuario_inactivo_no_inicia_sesion(): void
    {
        $this->operador->update(['activo' => false]);
        Auth::logout();

        $this->from(route('login'))->post(route('login.procesar'), [
            'email' => $this->operador->email,
            'password' => 'clave-solo-pruebas',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_validaciones_del_login_siguen_vigentes(): void
    {
        Auth::logout();

        $this->from(route('login'))->post(route('login.procesar'), [
            'email' => 'correo-invalido',
            'password' => '',
        ])->assertRedirect(route('login'))->assertSessionHasErrors(['email', 'password']);

        $this->assertGuest();
    }

    public function test_logout_cierra_la_sesion_y_regenera_el_token(): void
    {
        $this->startSession();
        $tokenAnterior = session()->token();
        session()->put('dato_anterior', 'valor');

        $this->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertFalse(session()->has('dato_anterior'));
        $this->assertNotSame($tokenAnterior, session()->token());
        $this->get(route('lecturas.index'))->assertRedirect(route('login'));
    }

    public function test_la_ruta_demo_esta_retirada_con_y_sin_sesion(): void
    {
        $this->get('/admin-demo')->assertNotFound();

        Auth::logout();

        $this->get('/admin-demo')->assertNotFound();
    }

    #[DataProvider('destinosPorRol')]
    public function test_la_interfaz_operativa_conserva_el_menu_por_rol_sin_demo(
        string $rol,
        string $destino
    ): void {
        $usuario = $this->usuario($rol);
        $this->actingAs($usuario);

        $respuesta = $this->get(route('lecturas.index'))->assertOk()
            ->assertSeeText('AquaTech')
            ->assertSeeText($usuario->nombre)
            ->assertDontSeeText('AdminLTE')
            ->assertDontSeeText('View documentation')
            ->assertDontSeeText('Documentation')
            ->assertDontSeeText('Home');

        $documento = new \DOMDocument;
        $documento->loadHTML($respuesta->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($documento);

        foreach ([
            'alta-servicio.create',
            'dashboard.estado-cuenta',
            'lecturas.index',
            'pagos.index',
            'recibos.index',
            'clientes.index',
            'contadores.index',
            'servicios.index',
            'tarifas.index',
            'usuarios.index',
        ] as $ruta) {
            $visible = $ruta === 'lecturas.index'
                || ($rol !== 'Lector' && ($ruta !== 'usuarios.index' || $rol === 'Administrador'));

            $this->assertSame(
                $visible ? 1 : 0,
                $xpath->query('//aside//a[@href="'.route($ruta).'"]')->length,
                "Visibilidad incorrecta de {$ruta} para {$rol}."
            );
        }

        $this->assertSame(0, $xpath->query('//*[@data-adminlte-search]')->length);
        $this->assertSame(0, $xpath->query('//a[contains(@href, "admin-demo") or contains(@href, "/docs")]')->length);
        $this->assertSame(0, $xpath->query('//img[contains(@src, "vendor/adminlte")]')->length);
        $this->assertSame(1, $xpath->query('//aside//img[@src="'.asset(config('adminlte.logo_img')).'"]')->length);
        $this->assertFileExists(public_path(config('adminlte.logo_img')));
        $this->assertSame(1, $xpath->query('//form[@action="'.route('logout').'" and @method="POST"]//input[@name="_token"]')->length);
    }
}
