@extends('adminlte::page')

@section('title', 'Nuevo contador')

@section('content_header')
    <div>
        <h1 class="mb-0">Nuevo contador</h1>

        <small class="text-muted">
            Registre el contador y asígnelo a un cliente, tarifa y servicio.
        </small>
    </div>
@stop


@section('content')

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- =========================================================
         ERRORES DE VALIDACIÓN
    ========================================================== --}}
    @if ($errors->any())

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >
            <strong>
                <i class="fas fa-exclamation-circle mr-1"></i>
                Por favor corrija los siguientes errores:
            </strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

            <button
                type="button"
                class="close"
                data-dismiss="alert"
                aria-label="Cerrar"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

    @endif


    {{-- =========================================================
         VALIDACIONES PREVIAS DEL CATÁLOGO
    ========================================================== --}}

    @if ($clientes->isEmpty())

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>

            No hay clientes activos disponibles.

            Debe registrar o activar al menos un cliente antes de
            crear un contador.
        </div>

    @endif


    @if ($tarifas->isEmpty())

        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle mr-1"></i>

            No hay tarifas activas registradas.

            Debe registrar o activar al menos una tarifa antes de
            crear un contador.
        </div>

    @endif


    @if ($servicios->isEmpty())

        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>

            Actualmente no hay servicios activos registrados.

            Puede crear el contador sin servicio y asignarlo posteriormente.
        </div>

    @endif


    <div class="card">

        <div class="card-header">

            <h3 class="card-title mb-0">
                Datos del contador
            </h3>

        </div>


        <div class="card-body">

            <form
                action="{{ route('contadores.store') }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf


                {{-- =================================================
                     ASIGNACIÓN ADMINISTRATIVA
                ================================================== --}}

                <h5 class="mb-3">
                    Asignación
                </h5>


                <div class="row">

                    {{-- CLIENTE --}}
                    <div class="col-12 col-lg-6">

                        <div class="form-group">

                            <label for="cliente_id">
                                Cliente
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="cliente_id"
                                id="cliente_id"
                                class="form-control
                                    @error('cliente_id')
                                        is-invalid
                                    @enderror"
                                required
                            >

                                <option value="">
                                    -- Seleccione un cliente --
                                </option>

                                @foreach ($clientes as $cliente)

                                    <option
                                        value="{{ $cliente->id }}"
                                        @selected(
                                            old('cliente_id') == $cliente->id
                                        )
                                    >
                                        {{ $cliente->nombre }}

                                        @if ($cliente->dpi)
                                            — DPI: {{ $cliente->dpi }}
                                        @endif
                                    </option>

                                @endforeach

                            </select>


                            @error('cliente_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                            <small class="form-text text-muted">
                                Solo se muestran clientes activos.
                            </small>

                        </div>

                    </div>


                    {{-- SERVICIO --}}
                    <div class="col-12 col-lg-6">

                        <div class="form-group">

                            <label for="servicio_id">
                                Servicio
                            </label>

                            <select
                                name="servicio_id"
                                id="servicio_id"
                                class="form-control
                                    @error('servicio_id')
                                        is-invalid
                                    @enderror"
                            >

                                <option value="">
                                    -- Sin servicio asignado --
                                </option>

                                @foreach ($servicios as $servicio)

                                    <option
                                        value="{{ $servicio->id }}"
                                        @selected(
                                            old('servicio_id') == $servicio->id
                                        )
                                    >
                                        {{ $servicio->nombre }}
                                    </option>

                                @endforeach

                            </select>


                            @error('servicio_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                            <small class="form-text text-muted">
                                El servicio es una clasificación informativa
                                del contador.
                            </small>

                        </div>

                    </div>

                </div>


                {{-- TARIFA --}}
                <div class="form-group">

                    <label for="tarifa_id">
                        Tarifa asignada
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="tarifa_id"
                        id="tarifa_id"
                        class="form-control
                            @error('tarifa_id')
                                is-invalid
                            @enderror"
                        required
                    >

                        <option value="">
                            -- Seleccione una tarifa --
                        </option>

                        @foreach ($tarifas as $tarifa)

                            <option
                                value="{{ $tarifa->id }}"
                                @selected(
                                    old('tarifa_id') == $tarifa->id
                                )
                            >
                                {{ $tarifa->nombre }}
                                — {{ $tarifa->tipo }}

                                @if (! is_null($tarifa->capacidad))
                                    — {{ number_format(
                                        (float) $tarifa->capacidad,
                                        3,
                                        '.',
                                        ''
                                    ) }} m³
                                @endif
                            </option>

                        @endforeach

                    </select>


                    @error('tarifa_id')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                    <small class="form-text text-muted">
                        La tarifa determina la capacidad y los valores
                        utilizados posteriormente para calcular el recibo.
                    </small>

                </div>


                <hr>


                {{-- =================================================
                     IDENTIFICACIÓN DEL CONTADOR
                ================================================== --}}

                <h5 class="mb-3">
                    Identificación y ubicación
                </h5>


                <div class="row">

                    {{-- NÚMERO DE REGISTRO --}}
                    <div class="col-12 col-md-6">

                        <div class="form-group">

                            <label for="numero_registro">
                                Número de registro
                                <span class="text-danger">*</span>
                            </label>

                            <input
                                type="text"
                                name="numero_registro"
                                id="numero_registro"
                                class="form-control
                                    @error('numero_registro')
                                        is-invalid
                                    @enderror"
                                value="{{ old('numero_registro') }}"
                                maxlength="50"
                                placeholder="Ej. CONT-001"
                                autocomplete="off"
                                required
                            >


                            @error('numero_registro')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- SECTOR --}}
                    <div class="col-12 col-md-6">

                        <div class="form-group">

                            <label for="sector">
                                Sector
                            </label>

                            <input
                                type="text"
                                name="sector"
                                id="sector"
                                class="form-control
                                    @error('sector')
                                        is-invalid
                                    @enderror"
                                value="{{ old('sector') }}"
                                maxlength="100"
                                placeholder="Ej. Barrio El Centro"
                            >


                            @error('sector')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>

                </div>


                <div class="form-group">
                    <label for="lectura_inicial">Lectura inicial (m³)</label>
                    <input type="number" name="lectura_inicial" id="lectura_inicial"
                           class="form-control @error('lectura_inicial') is-invalid @enderror"
                           value="{{ old('lectura_inicial') }}" min="0" max="999999999.999" step="0.001">
                    <small class="form-text text-muted">
                        Indique la marca física al instalar el contador. Use 0 solo si inicia en cero.
                        Si la desconoce, déjela vacía: deberá establecerla antes de la primera lectura.
                    </small>
                    @error('lectura_inicial')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                {{-- DIRECCIÓN --}}
                <div class="form-group">

                    <label for="direccion_servicio">
                        Dirección del servicio
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="direccion_servicio"
                        id="direccion_servicio"
                        class="form-control
                            @error('direccion_servicio')
                                is-invalid
                            @enderror"
                        value="{{ old('direccion_servicio') }}"
                        maxlength="255"
                        placeholder="Ej. 4a avenida 2-15, zona 1"
                        required
                    >


                    @error('direccion_servicio')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- PUNTO DE REFERENCIA --}}
                <div class="form-group">

                    <label for="punto_referencia">
                        Punto de referencia
                    </label>

                    <input
                        type="text"
                        name="punto_referencia"
                        id="punto_referencia"
                        class="form-control
                            @error('punto_referencia')
                                is-invalid
                            @enderror"
                        value="{{ old('punto_referencia') }}"
                        maxlength="255"
                        placeholder="Ej. Casa verde, frente a la iglesia"
                    >


                    @error('punto_referencia')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                <hr>


                {{-- =================================================
                     EVIDENCIA
                ================================================== --}}

                <h5 class="mb-3">
                    Fotografía
                </h5>


                <div class="form-group">

                    <label for="foto">
                        Fotografía del contador o predio
                    </label>

                    <input
                        type="file"
                        name="foto"
                        id="foto"
                        class="form-control
                            @error('foto')
                                is-invalid
                            @enderror"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >


                    <small class="form-text text-muted">
                        Formatos permitidos: JPG, JPEG, PNG o WEBP.
                        Tamaño máximo: 2 MB.
                    </small>


                    @error('foto')

                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- PREVISUALIZACIÓN --}}
                <div
                    id="contenedorVistaPrevia"
                    class="mb-3 d-none"
                >
                    <small class="text-muted d-block mb-2">
                        Vista previa
                    </small>

                    <img
                        id="vistaPrevia"
                        src=""
                        alt="Vista previa de la fotografía del contador"
                        class="img-thumbnail"
                        style="
                            max-width: 280px;
                            max-height: 220px;
                            object-fit: cover;
                        "
                    >
                </div>


                <hr>


                {{-- =================================================
                     ESTADO
                ================================================== --}}

                <div class="form-group">

                    <div class="custom-control custom-checkbox">

                        <input
                            type="checkbox"
                            name="activo"
                            id="activo"
                            class="custom-control-input"
                            value="1"
                            @checked(old('activo', 1))
                        >

                        <label
                            class="custom-control-label"
                            for="activo"
                        >
                            Contador activo
                        </label>

                    </div>

                    <small class="form-text text-muted">
                        Solo los contadores activos podrán utilizarse
                        para registrar nuevas lecturas.
                    </small>

                </div>


                {{-- =================================================
                     ACCIONES
                ================================================== --}}

                <div
                    class="d-flex flex-column flex-sm-row
                           justify-content-between mt-4"
                >

                    <a
                        href="{{ route('contadores.index') }}"
                        class="btn btn-secondary mb-2 mb-sm-0"
                    >
                        <i class="fas fa-arrow-left mr-1"></i>
                        Cancelar
                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        @disabled(
                            $clientes->isEmpty()
                            || $tarifas->isEmpty()
                        )
                    >
                        <i class="fas fa-save mr-1"></i>
                        Guardar contador
                    </button>

                </div>

            </form>

        </div>

    </div>

@stop


@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const inputFoto = document.getElementById('foto');
            const vistaPrevia = document.getElementById('vistaPrevia');
            const contenedorVistaPrevia =
                document.getElementById('contenedorVistaPrevia');

            if (
                !inputFoto
                || !vistaPrevia
                || !contenedorVistaPrevia
            ) {
                return;
            }

            inputFoto.addEventListener('change', function (event) {
                const archivo = event.target.files[0];

                if (!archivo) {
                    vistaPrevia.src = '';
                    contenedorVistaPrevia.classList.add('d-none');

                    return;
                }

                const urlTemporal = URL.createObjectURL(archivo);

                vistaPrevia.src = urlTemporal;
                contenedorVistaPrevia.classList.remove('d-none');

                vistaPrevia.onload = function () {
                    URL.revokeObjectURL(urlTemporal);
                };
            });
        });
    </script>
@stop
