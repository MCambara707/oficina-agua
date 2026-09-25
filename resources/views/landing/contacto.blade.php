@extends('adminlte::page')

@section('title', 'Contacto')

@section('content_header')
    <div>
        <h1 class="mb-1">Contacto</h1>
        <p class="text-muted mb-0">
            Administra los medios oficiales de contacto publicados en AquaTech GT.
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

    <form
        action="{{ route('landing.contacto.update') }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <i class="bi bi-telephone me-1"></i>
                    Información de contacto
                </h3>
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label
                                for="telefono"
                                class="form-label"
                            >
                                Teléfono
                            </label>

                            <input
                                type="text"
                                name="telefono"
                                id="telefono"
                                class="form-control"
                                maxlength="30"
                                value="{{ old(
                                    'telefono',
                                    $configuracion?->telefono
                                ) }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label
                                for="whatsapp"
                                class="form-label"
                            >
                                WhatsApp
                            </label>

                            <input
                                type="text"
                                name="whatsapp"
                                id="whatsapp"
                                class="form-control"
                                maxlength="30"
                                value="{{ old(
                                    'whatsapp',
                                    $configuracion?->whatsapp
                                ) }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label
                                for="correo"
                                class="form-label"
                            >
                                Correo electrónico
                            </label>

                            <input
                                type="email"
                                name="correo"
                                id="correo"
                                class="form-control"
                                maxlength="150"
                                value="{{ old(
                                    'correo',
                                    $configuracion?->correo
                                ) }}"
                            >
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label
                                for="horario"
                                class="form-label"
                            >
                                Horario de atención
                            </label>

                            <input
                                type="text"
                                name="horario"
                                id="horario"
                                class="form-control"
                                maxlength="255"
                                value="{{ old(
                                    'horario',
                                    $configuracion?->horario
                                ) }}"
                            >
                        </div>
                    </div>

                </div>

                <div class="mb-4">
                    <label
                        for="direccion"
                        class="form-label"
                    >
                        Dirección
                    </label>

                    <textarea
                        name="direccion"
                        id="direccion"
                        class="form-control"
                        rows="4"
                        maxlength="1000"
                    >{{ old(
                        'direccion',
                        $configuracion?->direccion
                    ) }}</textarea>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-save me-1"></i>
                    Guardar cambios
                </button>

            </div>
        </div>

    </form>

@stop