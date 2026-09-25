<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarConfiguracionLandingRequest;
use App\Models\ConfiguracionLanding;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingAdminController extends Controller
{
    /**
     * Muestra la información principal de la landing.
     */
    public function informacion(): View
    {
        $configuracion = $this->obtenerConfiguracion();

        return view(
            'landing.informacion',
            compact('configuracion')
        );
    }

    /**
     * Actualiza la información principal.
     */
    public function actualizarInformacion(
        ActualizarConfiguracionLandingRequest $request
    ): RedirectResponse {
        $configuracion = $this->obtenerConfiguracion();

        $configuracion->fill(
            $request->safe()->only([
                'titulo_principal',
                'descripcion_principal',
                'activo',
            ])
        );

        $configuracion->save();

        return redirect()
            ->route('landing.informacion')
            ->with(
                'success',
                'La información principal se actualizó correctamente.'
            );
    }

    /**
     * Muestra la información institucional.
     */
    public function quienesSomos(): View
    {
        $configuracion = $this->obtenerConfiguracion();

        return view(
            'landing.quienes-somos',
            compact('configuracion')
        );
    }

    /**
     * Actualiza la información institucional.
     */
    public function actualizarQuienesSomos(
        ActualizarConfiguracionLandingRequest $request
    ): RedirectResponse {
        $configuracion = $this->obtenerConfiguracion();

        $configuracion->fill(
            $request->safe()->only([
                'historia',
                'mision',
                'vision',
                'diferenciadores',
            ])
        );

        $configuracion->save();

        return redirect()
            ->route('landing.quienes-somos')
            ->with(
                'success',
                'La información institucional se actualizó correctamente.'
            );
    }

    /**
     * Muestra la información de contacto.
     */
    public function contacto(): View
    {
        $configuracion = $this->obtenerConfiguracion();

        return view(
            'landing.contacto',
            compact('configuracion')
        );
    }

    /**
     * Actualiza la información de contacto.
     */
    public function actualizarContacto(
        ActualizarConfiguracionLandingRequest $request
    ): RedirectResponse {
        $configuracion = $this->obtenerConfiguracion();

        $configuracion->fill(
            $request->safe()->only([
                'telefono',
                'whatsapp',
                'correo',
                'direccion',
                'horario',
            ])
        );

        $configuracion->save();

        return redirect()
            ->route('landing.contacto')
            ->with(
                'success',
                'La información de contacto se actualizó correctamente.'
            );
    }

    /**
     * Obtiene la configuración más reciente.
     *
     * Si no existe todavía, prepara una instancia nueva
     * con la publicación activada por defecto.
     */
    private function obtenerConfiguracion(): ConfiguracionLanding
    {
        return ConfiguracionLanding::query()
            ->orderByDesc('id')
            ->first()
            ?? new ConfiguracionLanding([
                'activo' => true,
            ]);
    }
}