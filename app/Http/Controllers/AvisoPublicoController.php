<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarAvisoPublicoRequest;
use App\Http\Requests\GuardarAvisoPublicoRequest;
use App\Models\AvisoPublico;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AvisoPublicoController extends Controller
{
    /**
     * Lista los avisos públicos.
     */
    public function index(): View
    {
        $avisos = AvisoPublico::query()
            ->orderBy('orden')
            ->orderByDesc('id')
            ->paginate(15);

        return view(
            'landing.avisos.index',
            compact('avisos')
        );
    }

    /**
     * Formulario para crear un aviso.
     */
    public function create(): View
    {
        return view('landing.avisos.create');
    }

    /**
     * Guarda un nuevo aviso.
     */
    public function store(
        GuardarAvisoPublicoRequest $request
    ): RedirectResponse {
        AvisoPublico::create(
            $request->validated()
        );

        return redirect()
            ->route('landing.avisos.index')
            ->with(
                'success',
                'El aviso público se creó correctamente.'
            );
    }

    /**
     * Formulario para editar un aviso.
     */
    public function edit(
        AvisoPublico $aviso
    ): View {
        return view(
            'landing.avisos.edit',
            compact('aviso')
        );
    }

    /**
     * Actualiza un aviso existente.
     */
    public function update(
        ActualizarAvisoPublicoRequest $request,
        AvisoPublico $aviso
    ): RedirectResponse {
        $aviso->update(
            $request->validated()
        );

        return redirect()
            ->route('landing.avisos.index')
            ->with(
                'success',
                'El aviso público se actualizó correctamente.'
            );
    }

    /**
     * Elimina un aviso.
     */
    public function destroy(
        AvisoPublico $aviso
    ): RedirectResponse {
        $aviso->delete();

        return redirect()
            ->route('landing.avisos.index')
            ->with(
                'success',
                'El aviso público se eliminó correctamente.'
            );
    }
}