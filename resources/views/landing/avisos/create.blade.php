@extends('adminlte::page')

@section('title', 'Nuevo aviso')

@section('content_header')
    <h1>Nuevo aviso público</h1>
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
                action="{{ route('landing.avisos.store') }}"
                method="POST"
            >
                @csrf

                <div class="mb-3">
                    <label for="titulo" class="form-label">
                        Título *
                    </label>

                    <input
                        type="text"
                        name="titulo"
                        id="titulo"
                        class="form-control"
                        maxlength="150"
                        value="{{ old('titulo') }}"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="contenido" class="form-label">
                        Contenido *
                    </label>

                    <textarea
                        name="contenido"
                        id="contenido"
                        class="form-control"
                        rows="6"
                        maxlength="5000"
                        required
                    >{{ old('contenido') }}</textarea>
                </div>

                <div class="row">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_inicio" class="form-label">
                                Fecha de inicio
                            </label>

                            <input
                                type="date"
                                name="fecha_inicio"
                                id="fecha_inicio"
                                class="form-control"
                                value="{{ old('fecha_inicio') }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="fecha_fin" class="form-label">
                                Fecha de finalización
                            </label>

                            <input
                                type="date"
                                name="fecha_fin"
                                id="fecha_fin"
                                class="form-control"
                                value="{{ old('fecha_fin') }}"
                            >
                        </div>
                    </div>

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
                        value="{{ old('orden', 0) }}"
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
                        @checked(old('activo', true))
                    >

                    <label
                        for="activo"
                        class="form-check-label"
                    >
                        Aviso activo
                    </label>

                </div>

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Guardar
                    </button>

                    <a
                        href="{{ route('landing.avisos.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>

                </div>

            </form>

        </div>
    </div>

@stop