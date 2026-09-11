@extends('adminlte::page')

@section('title', 'Editar Contador')

@section('content_header')
    <h1>Editar Contador</h1>
@stop

@section('content')

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <p class="font-weight-bold mb-1">Por favor corrige los siguientes errores:</p>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                action="{{ route('contadores.update', $contador) }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>

                    <select
                        name="cliente_id"
                        id="cliente_id"
                        class="form-control @error('cliente_id') is-invalid @enderror"
                        required
                        @disabled($contador->lecturas_exists)
                    >
                        @foreach ($clientes as $cliente)
                            <option
                                value="{{ $cliente->id }}"
                                {{ old('cliente_id', $contador->cliente_id) == $cliente->id ? 'selected' : '' }}
                            >
                                {{ $cliente->nombre }}
                            </option>
                        @endforeach
                    </select>

                    @if ($contador->lecturas_exists)
                        <input type="hidden" name="cliente_id" value="{{ $contador->cliente_id }}">
                        <small class="form-text text-muted">El cliente no puede cambiar porque este contador tiene historial.</small>
                    @endif

                    @error('cliente_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="tarifa_id">Tarifa asignada *</label>

                    <select
                        name="tarifa_id"
                        id="tarifa_id"
                        class="form-control @error('tarifa_id') is-invalid @enderror"
                        required
                    >
                        <option value="">-- Selecciona una tarifa --</option>

                        @foreach ($tarifas as $tarifa)
                            <option
                                value="{{ $tarifa->id }}"
                                {{ old('tarifa_id', $contador->tarifa_id) == $tarifa->id ? 'selected' : '' }}
                            >
                                {{ $tarifa->nombre }}
                                - {{ $tarifa->tipo }}

                                @if (!is_null($tarifa->capacidad))
                                    ({{ $tarifa->capacidad }} m³)
                                @endif

                                @if (!$tarifa->activo)
                                    - Inactiva
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('tarifa_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="servicio_id">Servicio</label>

                    <select
                        name="servicio_id"
                        id="servicio_id"
                        class="form-control @error('servicio_id') is-invalid @enderror"
                    >
                        <option value="">-- Sin servicio asignado --</option>

                        @foreach ($servicios as $servicio)
                            <option
                                value="{{ $servicio->id }}"
                                {{ old('servicio_id', $contador->servicio_id) == $servicio->id ? 'selected' : '' }}
                            >
                                {{ $servicio->nombre }}

                                @if (!$servicio->activo)
                                    - Inactivo
                                @endif
                            </option>
                        @endforeach
                    </select>

                    @error('servicio_id')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="numero_registro">
                        Número de registro *
                    </label>

                    <input
                        type="text"
                        name="numero_registro"
                        id="numero_registro"
                        class="form-control @error('numero_registro') is-invalid @enderror"
                        value="{{ old('numero_registro', $contador->numero_registro) }}"
                        maxlength="50"
                        required
                    >

                    @error('numero_registro')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="lectura_inicial">Lectura inicial (m³)</label>
                    <input type="number" name="lectura_inicial" id="lectura_inicial"
                           class="form-control @error('lectura_inicial') is-invalid @enderror"
                           value="{{ $contador->lecturas_exists ? $contador->lectura_inicial : old('lectura_inicial', $contador->lectura_inicial) }}"
                           min="0" max="999999999.999" step="0.001" @readonly($contador->lecturas_exists)>
                    <small class="form-text text-muted">
                        @if ($contador->lecturas_exists)
                            La base está bloqueada por el historial. Las nuevas lecturas utilizan la última lectura registrada.
                        @else
                            Use 0 solo si el contador inicia en cero. Si la base se desconoce, déjela vacía:
                            no podrá registrar la primera lectura hasta establecerla.
                        @endif
                    </small>
                    @error('lectura_inicial')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="direccion_servicio">
                        Dirección de servicio *
                    </label>

                    <input
                        type="text"
                        name="direccion_servicio"
                        id="direccion_servicio"
                        class="form-control @error('direccion_servicio') is-invalid @enderror"
                        value="{{ old('direccion_servicio', $contador->direccion_servicio) }}"
                        maxlength="255"
                        placeholder="Ej. 4a avenida 2-15, zona 1"
                        required
                    >

                    @error('direccion_servicio')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="punto_referencia">
                        Punto de referencia
                    </label>

                    <input
                        type="text"
                        name="punto_referencia"
                        id="punto_referencia"
                        class="form-control @error('punto_referencia') is-invalid @enderror"
                        value="{{ old('punto_referencia', $contador->punto_referencia) }}"
                        maxlength="255"
                        placeholder="Ej. Casa verde, frente a la iglesia"
                    >

                    @error('punto_referencia')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="sector">Sector</label>

                    <input
                        type="text"
                        name="sector"
                        id="sector"
                        class="form-control @error('sector') is-invalid @enderror"
                        value="{{ old('sector', $contador->sector) }}"
                        maxlength="100"
                        placeholder="Ej. Barrio El Centro"
                    >

                    @error('sector')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>
                        Fotografía actual
                    </label>

                    <div class="mb-2">
                        @if ($contador->foto_ruta)
                            <img
                                src="{{ asset('storage/' . $contador->foto_ruta) }}"
                                alt="Fotografía del contador"
                                class="img-thumbnail"
                                style="max-width: 250px; max-height: 200px;"
                            >
                        @else
                            <p class="text-muted mb-0">
                                Este contador no tiene fotografía registrada.
                            </p>
                        @endif
                    </div>
                </div>

                <div class="form-group">
                    <label for="foto">
                        Reemplazar fotografía
                    </label>

                    <input
                        type="file"
                        name="foto"
                        id="foto"
                        class="form-control @error('foto') is-invalid @enderror"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >

                    <small class="form-text text-muted">
                        Déjalo vacío para conservar la fotografía actual.
                        Formatos permitidos: JPG, PNG o WEBP. Máximo 2 MB.
                    </small>

                    @error('foto')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group form-check">
                    <input
                        type="checkbox"
                        name="activo"
                        id="activo"
                        class="form-check-input"
                        value="1"
                        {{ old('activo', $contador->activo) ? 'checked' : '' }}
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
                >
                    Actualizar
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
