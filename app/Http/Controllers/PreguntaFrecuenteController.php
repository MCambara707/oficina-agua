<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActualizarPreguntaFrecuenteRequest;
use App\Http\Requests\GuardarPreguntaFrecuenteRequest;
use App\Models\PreguntaFrecuente;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PreguntaFrecuenteController extends Controller
{
    /**
     * Lista las preguntas frecuentes.
     */
    public function index(): View
    {
        $preguntas = PreguntaFrecuente::query()
            ->orderBy('orden')
            ->orderBy('id')
            ->paginate(15);

        return view(
            'landing.preguntas.index',
            compact('preguntas')
        );
    }

    /**
     * Formulario para crear una pregunta frecuente.
     */
    public function create(): View
    {
        return view('landing.preguntas.create');
    }

    /**
     * Guarda una pregunta frecuente.
     */
    public function store(
        GuardarPreguntaFrecuenteRequest $request
    ): RedirectResponse {
        PreguntaFrecuente::create(
            $request->validated()
        );

        return redirect()
            ->route('landing.preguntas.index')
            ->with(
                'success',
                'La pregunta frecuente se creó correctamente.'
            );
    }

    /**
     * Formulario para editar una pregunta frecuente.
     */
    public function edit(
        PreguntaFrecuente $pregunta
    ): View {
        return view(
            'landing.preguntas.edit',
            compact('pregunta')
        );
    }

    /**
     * Actualiza una pregunta frecuente.
     */
    public function update(
        ActualizarPreguntaFrecuenteRequest $request,
        PreguntaFrecuente $pregunta
    ): RedirectResponse {
        $pregunta->update(
            $request->validated()
        );

        return redirect()
            ->route('landing.preguntas.index')
            ->with(
                'success',
                'La pregunta frecuente se actualizó correctamente.'
            );
    }

    /**
     * Elimina una pregunta frecuente.
     */
    public function destroy(
        PreguntaFrecuente $pregunta
    ): RedirectResponse {
        $pregunta->delete();

        return redirect()
            ->route('landing.preguntas.index')
            ->with(
                'success',
                'La pregunta frecuente se eliminó correctamente.'
            );
    }
}