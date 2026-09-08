@extends('adminlte::page')

@section('title', 'Nuevo Contador')

@section('content_header')
    <h1>Nuevo Contador</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($tarifas->isEmpty())
                <div class="alert alert-warning">
                    No hay tarifas activas registradas.
                    Debes registrar al menos una tarifa antes de crear un contador.
                </div>
            @endif

            <form
                action="{{ route('contadores.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf

                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>

                    <select
                        name="cliente_id"
                        id="cliente_id"
                        class="form-control"
                        required
                    >
                        <option value="">-- Selecciona un cliente --</option>

                        @foreach ($clientes as $cliente)
                            <option
                                value="{{ $cliente->id }}"
                                {{ old('cliente_id') == $cliente->id ? 'selected' : '' }}
                            >
                                {{ $cliente->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="tarifa_id">Tarifa asignada *</label>

                    <select
                        name="tarifa_id"
                        id="tarifa_id"
                        class="form-control"
                        required
                    >
                        <option value="">-- Selecciona una tarifa --</option>

                        @foreach ($tarifas as $tarifa)
                            <option
                                value="{{ $tarifa->id }}"
                                {{ old('tarifa_id') == $tarifa->id ? 'selected' : '' }}
                            >
                                {{ $tarifa->nombre }}
                                - {{ $tarifa->tipo }}
                                @if (!is_null($tarifa->capacidad))
                                    ({{ $tarifa->capacidad }} m³)
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="numero_registro">
                        Número de registro *
                    </label>

                    <input
                        type="text"
                        name="numero_registro"
                        id="numero_registro"
                        class="form-control"
                        value="{{ old('numero_registro') }}"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="direccion_servicio">
                        Dirección de servicio *
                    </label>

                    <input
                        type="text"
                        name="direccion_servicio"
                        id="direccion_servicio"
                        class="form-control"
                        value="{{ old('direccion_servicio') }}"
                        maxlength="255"
                        placeholder="Ej. 4a avenida 2-15, zona 1"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="punto_referencia">
                        Punto de referencia
                    </label>

                    <input
                        type="text"
                        name="punto_referencia"
                        id="punto_referencia"
                        class="form-control"
                        value="{{ old('punto_referencia') }}"
                        maxlength="255"
                        placeholder="Ej. Casa verde, frente a la iglesia"
                    >
                </div>

                <div class="form-group">
                    <label for="sector">Sector</label>

                    <input
                        type="text"
                        name="sector"
                        id="sector"
                        class="form-control"
                        value="{{ old('sector') }}"
                        maxlength="100"
                        placeholder="Ej. Barrio El Centro"
                    >
                </div>

                <div class="form-group">
                    <label for="foto">
                        Fotografía del contador o predio
                    </label>

                    <input
                        type="file"
                        name="foto"
                        id="foto"
                        class="form-control"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small class="form-text text-muted">
                        Formatos permitidos: JPG, PNG o WEBP. Máximo 2 MB.
                    </small>
                </div>

                <div class="form-group form-check">
                    <input
                        type="checkbox"
                        name="activo"
                        id="activo"
                        class="form-check-input"
                        value="1"
                        {{ old('activo', 1) ? 'checked' : '' }}
                    >

                    <label
                        class="form-check-label"
                        for="activo"
                    >
                        Activo
                    </label>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                    {{ $tarifas->isEmpty() ? 'disabled' : '' }}
                >
                    Guardar
                </button>

                <a
                    href="{{ route('contadores.index') }}"
                    class="btn btn-secondary"
                >
                    Cancelar
                </a>
            </form>

        </div>
    </div>

@stop