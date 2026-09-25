@extends('adminlte::page')

@section('title', 'Editar pregunta frecuente')

@section('content_header')
    <h1>Editar pregunta frecuente</h1>
@stop

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa la información ingresada.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form
                action="{{ route(
                    'landing.preguntas.update',
                    $pregunta
                ) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="pregunta" class="form-label">
                        Pregunta *
                    </label>

                    <input
                        type="text"
                        name="pregunta"
                        id="pregunta"
                        class="form-control"
                        maxlength="255"
                        value="{{ old(
                            'pregunta',
                            $pregunta->pregunta
                        ) }}"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="respuesta" class="form-label">
                        Respuesta *
                    </label>

                    <textarea
                        name="respuesta"
                        id="respuesta"
                        class="form-control"
                        rows="6"
                        maxlength="5000"
                        required
                    >{{ old(
                        'respuesta',
                        $pregunta->respuesta
                    ) }}</textarea>
                </div>

                <div class="mb-3">
                    <label for="orden" class="form-label">
                        Orden
                    </label>

                    <input
                        type="number"
                        name="orden"
                        id="orden"
                        class="form-control"
                        min="0"
                        max="9999"
                        value="{{ old(
                            'orden',
                            $pregunta->orden
                        ) }}"
                    >
                </div>

                <input type="hidden" name="activo" value="0">

                <div class="form-check form-switch mb-4">

                    <input
                        type="checkbox"
                        name="activo"
                        id="activo"
                        class="form-check-input"
                        value="1"
                        @checked(
                            old(
                                'activo',
                                $pregunta->activo
                            )
                        )
                    >

                    <label
                        for="activo"
                        class="form-check-label"
                    >
                        Pregunta activa
                    </label>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Guardar cambios
                    </button>

                    <a
                        href="{{ route('landing.preguntas.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>
    </div>

@stop