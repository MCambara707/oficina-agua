@extends('adminlte::page')

@section('title', 'Información principal')

@section('content_header')
    <div>
        <h1 class="mb-1">Información principal</h1>
        <p class="text-muted mb-0">
            Administra el contenido principal mostrado en AquaTech GT.
        </p>
    </div>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No fue posible guardar la información.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-house-door me-1"></i>
                Página de inicio
            </h3>
        </div>

        <div class="card-body">

            <form
                action="{{ route('landing.informacion.update') }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label
                        for="titulo_principal"
                        class="form-label"
                    >
                        Título principal
                    </label>

                    <input
                        type="text"
                        name="titulo_principal"
                        id="titulo_principal"
                        class="form-control"
                        maxlength="150"
                        value="{{ old(
                            'titulo_principal',
                            $configuracion?->titulo_principal
                        ) }}"
                        placeholder="Información del agua al alcance de todos"
                    >

                    <div class="form-text">
                        Máximo 150 caracteres.
                    </div>
                </div>

                <div class="mb-4">
                    <label
                        for="descripcion_principal"
                        class="form-label"
                    >
                        Descripción principal
                    </label>

                    <textarea
                        name="descripcion_principal"
                        id="descripcion_principal"
                        class="form-control"
                        rows="5"
                        maxlength="3000"
                    >{{ old(
                        'descripcion_principal',
                        $configuracion?->descripcion_principal
                    ) }}</textarea>
                </div>

                <input
                    type="hidden"
                    name="activo"
                    value="0"
                >

                <div class="form-check form-switch mb-4">
                    <input
                        type="checkbox"
                        name="activo"
                        id="activo"
                        value="1"
                        class="form-check-input"
                        @checked(
                            old(
                                'activo',
                                $configuracion?->activo ?? true
                            )
                        )
                    >

                    <label
                        for="activo"
                        class="form-check-label"
                    >
                        Publicar configuración en la landing
                    </label>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-save me-1"></i>
                    Guardar cambios
                </button>

            </form>

        </div>
    </div>

@stop