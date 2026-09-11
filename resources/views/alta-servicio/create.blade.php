@extends('adminlte::page')

@section('title', 'Alta de servicio')

@section('content_header')
    <div>
        <h1 class="mb-0">
            Alta de servicio
        </h1>

        <small class="text-muted">
            Registre una nueva conexión utilizando un cliente existente
            o creando un cliente nuevo.
        </small>
    </div>
@stop


@section('content')

    {{-- =========================================================
         MENSAJES DEL SISTEMA
    ========================================================== --}}

    @if (session('error'))

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >
            <i class="fas fa-exclamation-circle mr-1"></i>

            {{ session('error') }}

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


    @if ($errors->any())

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >
            <strong>
                <i class="fas fa-exclamation-triangle mr-1"></i>
                No fue posible registrar el alta.
            </strong>

            <ul class="mb-0 mt-2">

                @foreach ($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

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
         VALIDACIONES PREVIAS
    ========================================================== --}}

    @if ($tarifas->isEmpty())

        <div class="alert alert-warning">

            <i class="fas fa-exclamation-triangle mr-1"></i>

            No existen tarifas activas.

            Debe registrar o activar al menos una tarifa antes de
            realizar una nueva alta.

        </div>

    @endif


    @if ($servicios->isEmpty())

        <div class="alert alert-info">

            <i class="fas fa-info-circle mr-1"></i>

            No existen servicios activos actualmente.

            El alta puede realizarse sin clasificación de servicio
            y asignarse posteriormente.

        </div>

    @endif


    <form
        action="{{ route('alta-servicio.store') }}"
        method="POST"
        enctype="multipart/form-data"
        id="formAltaServicio"
    >

        @csrf


        {{-- =====================================================
             1. CLIENTE
        ====================================================== --}}

        <div class="card">

            <div class="card-header">

                <h3 class="card-title mb-0">

                    <i class="fas fa-user mr-1"></i>

                    1. Cliente

                </h3>

            </div>


            <div class="card-body">

                <p class="text-muted">
                    Indique si la conexión pertenece a un cliente
                    que ya existe o si debe registrar uno nuevo.
                </p>


                {{-- TIPO DE CLIENTE --}}
                <div class="form-group">

                    <label class="d-block">
                        Tipo de cliente
                        <span class="text-danger">*</span>
                    </label>


                    <div class="custom-control custom-radio custom-control-inline">

                        <input
                            type="radio"
                            id="tipo_cliente_existente"
                            name="tipo_cliente"
                            value="existente"
                            class="custom-control-input"
                            @checked(
                                old(
                                    'tipo_cliente',
                                    $clientes->isNotEmpty()
                                        ? 'existente'
                                        : 'nuevo'
                                ) === 'existente'
                            )
                        >

                        <label
                            class="custom-control-label"
                            for="tipo_cliente_existente"
                        >
                            Cliente existente
                        </label>

                    </div>


                    <div class="custom-control custom-radio custom-control-inline">

                        <input
                            type="radio"
                            id="tipo_cliente_nuevo"
                            name="tipo_cliente"
                            value="nuevo"
                            class="custom-control-input"
                            @checked(
                                old(
                                    'tipo_cliente',
                                    $clientes->isNotEmpty()
                                        ? 'existente'
                                        : 'nuevo'
                                ) === 'nuevo'
                            )
                        >

                        <label
                            class="custom-control-label"
                            for="tipo_cliente_nuevo"
                        >
                            Cliente nuevo
                        </label>

                    </div>


                    @error('tipo_cliente')

                        <div class="text-danger small mt-2">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                     CLIENTE EXISTENTE
                ================================================== --}}

                <div id="bloqueClienteExistente">

                    <hr>

                    <div class="form-group">

                        <label for="cliente_id">
                            Seleccione el cliente
                            <span class="text-danger">*</span>
                        </label>

                        <select
                            name="cliente_id"
                            id="cliente_id"
                            class="form-control
                                @error('cliente_id')
                                    is-invalid
                                @enderror"
                        >

                            <option value="">
                                -- Seleccione un cliente --
                            </option>


                            @foreach ($clientes as $cliente)

                                <option
                                    value="{{ $cliente->id }}"
                                    @selected(
                                        old('cliente_id')
                                        == $cliente->id
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


                        @if ($clientes->isEmpty())

                            <small class="form-text text-warning">

                                No hay clientes activos disponibles.
                                Seleccione la opción
                                <strong>Cliente nuevo</strong>.

                            </small>

                        @else

                            <small class="form-text text-muted">

                                Antes de crear una persona nueva,
                                verifique que no exista por nombre o DPI.

                            </small>

                        @endif

                    </div>

                </div>


                {{-- =================================================
                     CLIENTE NUEVO
                ================================================== --}}

                <div id="bloqueClienteNuevo">

                    <hr>


                    <div class="alert alert-light border">

                        <i class="fas fa-info-circle mr-1"></i>

                        El DPI no puede estar registrado previamente.
                        Si ya existe, utilice la opción
                        <strong>Cliente existente</strong>.

                    </div>


                    <div class="row">

                        {{-- NOMBRE --}}
                        <div class="col-12 col-md-6">

                            <div class="form-group">

                                <label for="nombre">
                                    Nombre completo
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="nombre"
                                    id="nombre"
                                    value="{{ old('nombre') }}"
                                    maxlength="150"
                                    class="form-control
                                        @error('nombre')
                                            is-invalid
                                        @enderror"
                                    placeholder="Nombre completo del cliente"
                                >

                                @error('nombre')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- DPI --}}
                        <div class="col-12 col-md-6">

                            <div class="form-group">

                                <label for="dpi">
                                    DPI
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="dpi"
                                    id="dpi"
                                    value="{{ old('dpi') }}"
                                    maxlength="20"
                                    class="form-control
                                        @error('dpi')
                                            is-invalid
                                        @enderror"
                                    placeholder="Número de DPI"
                                    autocomplete="off"
                                >

                                @error('dpi')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>

                    </div>


                    <div class="row">

                        {{-- TELÉFONO --}}
                        <div class="col-12 col-md-5">

                            <div class="form-group">

                                <label for="telefono">
                                    Teléfono
                                </label>

                                <input
                                    type="text"
                                    name="telefono"
                                    id="telefono"
                                    value="{{ old('telefono') }}"
                                    maxlength="25"
                                    class="form-control
                                        @error('telefono')
                                            is-invalid
                                        @enderror"
                                    placeholder="Ej. 5555-5555"
                                >

                                @error('telefono')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>


                        {{-- DIRECCIÓN PRINCIPAL --}}
                        <div class="col-12 col-md-7">

                            <div class="form-group">

                                <label for="direccion_principal">
                                    Dirección principal
                                </label>

                                <input
                                    type="text"
                                    name="direccion_principal"
                                    id="direccion_principal"
                                    value="{{ old('direccion_principal') }}"
                                    maxlength="255"
                                    class="form-control
                                        @error('direccion_principal')
                                            is-invalid
                                        @enderror"
                                    placeholder="Dirección principal del cliente"
                                >

                                @error('direccion_principal')

                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>

                                @enderror

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             2. ASIGNACIÓN DEL SERVICIO
        ====================================================== --}}

        <div class="card">

            <div class="card-header">

                <h3 class="card-title mb-0">

                    <i class="fas fa-tint mr-1"></i>

                    2. Servicio y tarifa

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- SERVICIO --}}
                    <div class="col-12 col-md-6">

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
                                            old('servicio_id')
                                            == $servicio->id
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

                                El servicio identifica qué servicio
                                presta el contador. No modifica el
                                cálculo de consumo.

                            </small>

                        </div>

                    </div>


                    {{-- TARIFA --}}
                    <div class="col-12 col-md-6">

                        <div class="form-group">

                            <label for="tarifa_id">
                                Tarifa
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
                                            old('tarifa_id')
                                            == $tarifa->id
                                        )
                                    >
                                        {{ $tarifa->nombre }}
                                        — {{ $tarifa->tipo }}

                                        @if (! is_null($tarifa->capacidad))
                                            — {{ rtrim(
                                                rtrim(
                                                    number_format(
                                                        (float) $tarifa->capacidad,
                                                        3,
                                                        '.',
                                                        ''
                                                    ),
                                                    '0'
                                                ),
                                                '.'
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

                                La tarifa determina la capacidad,
                                precio normal y precio por exceso.

                            </small>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
             3. CONTADOR Y UBICACIÓN
        ====================================================== --}}

        <div class="card">

            <div class="card-header">

                <h3 class="card-title mb-0">

                    <i class="fas fa-tachometer-alt mr-1"></i>

                    3. Contador y ubicación

                </h3>

            </div>


            <div class="card-body">

                <div class="row">

                    {{-- NÚMERO DEL CONTADOR --}}
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
                                value="{{ old('numero_registro') }}"
                                maxlength="50"
                                class="form-control
                                    @error('numero_registro')
                                        is-invalid
                                    @enderror"
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
                                value="{{ old('sector') }}"
                                maxlength="100"
                                class="form-control
                                    @error('sector')
                                        is-invalid
                                    @enderror"
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

                {{-- DIRECCIÓN DE SERVICIO --}}
                <div class="form-group">

                    <label for="direccion_servicio">
                        Dirección del servicio
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="direccion_servicio"
                        id="direccion_servicio"
                        value="{{ old('direccion_servicio') }}"
                        maxlength="255"
                        class="form-control
                            @error('direccion_servicio')
                                is-invalid
                            @enderror"
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
                        value="{{ old('punto_referencia') }}"
                        maxlength="255"
                        class="form-control
                            @error('punto_referencia')
                                is-invalid
                            @enderror"
                        placeholder="Ej. Casa verde, frente a la iglesia"
                    >


                    @error('punto_referencia')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- FOTOGRAFÍA --}}
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


                {{-- VISTA PREVIA --}}
                <div
                    id="contenedorVistaPrevia"
                    class="d-none mt-3"
                >

                    <small class="text-muted d-block mb-2">
                        Vista previa de la fotografía
                    </small>

                    <img
                        id="vistaPrevia"
                        src=""
                        alt="Vista previa de la fotografía"
                        class="img-thumbnail"
                        style="
                            max-width: 300px;
                            max-height: 230px;
                            object-fit: cover;
                        "
                    >

                </div>

            </div>

        </div>


        {{-- =====================================================
             RESUMEN Y ACCIONES
        ====================================================== --}}

        <div class="card">

            <div class="card-body">

                <div class="alert alert-light border">

                    <div class="font-weight-bold mb-2">

                        <i class="fas fa-info-circle mr-1"></i>

                        Al confirmar esta operación:

                    </div>

                    <div>
                        Se registrará la conexión como
                        <strong>activa</strong> y el contador quedará
                        disponible para registrar futuras lecturas.
                    </div>

                </div>


                <div
                    class="d-flex flex-column flex-sm-row
                           justify-content-between"
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
                        class="btn btn-success"
                        id="btnRegistrarAlta"
                        @disabled($tarifas->isEmpty())
                    >
                        <i class="fas fa-check-circle mr-1"></i>

                        Registrar alta
                    </button>

                </div>

            </div>

        </div>

    </form>

@stop


@section('js')

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /*
             * =====================================================
             * ELEMENTOS DEL TIPO DE CLIENTE
             * =====================================================
             */
            const tipoExistente =
                document.getElementById('tipo_cliente_existente');

            const tipoNuevo =
                document.getElementById('tipo_cliente_nuevo');

            const bloqueExistente =
                document.getElementById('bloqueClienteExistente');

            const bloqueNuevo =
                document.getElementById('bloqueClienteNuevo');

            const clienteId =
                document.getElementById('cliente_id');

            const nombre =
                document.getElementById('nombre');

            const dpi =
                document.getElementById('dpi');

            const telefono =
                document.getElementById('telefono');

            const direccionPrincipal =
                document.getElementById('direccion_principal');


            /*
             * =====================================================
             * CAMBIAR MODALIDAD DEL CLIENTE
             * =====================================================
             *
             * Además de ocultar la sección que no corresponde,
             * deshabilitamos sus campos.
             *
             * De esta manera el navegador no envía datos
             * innecesarios al controlador.
             */
            function actualizarTipoCliente() {

                if (tipoExistente && tipoExistente.checked) {

                    bloqueExistente.classList.remove('d-none');
                    bloqueNuevo.classList.add('d-none');

                    clienteId.disabled = false;
                    clienteId.required = true;

                    nombre.disabled = true;
                    nombre.required = false;

                    dpi.disabled = true;
                    dpi.required = false;

                    telefono.disabled = true;
                    direccionPrincipal.disabled = true;

                    return;
                }


                bloqueExistente.classList.add('d-none');
                bloqueNuevo.classList.remove('d-none');

                clienteId.disabled = true;
                clienteId.required = false;

                nombre.disabled = false;
                nombre.required = true;

                dpi.disabled = false;
                dpi.required = true;

                telefono.disabled = false;
                direccionPrincipal.disabled = false;
            }


            if (tipoExistente) {
                tipoExistente.addEventListener(
                    'change',
                    actualizarTipoCliente
                );
            }


            if (tipoNuevo) {
                tipoNuevo.addEventListener(
                    'change',
                    actualizarTipoCliente
                );
            }


            /*
             * Aplicar configuración inicial.
             *
             * Esto también respeta old('tipo_cliente')
             * cuando Laravel devuelve el formulario por error.
             */
            actualizarTipoCliente();


            /*
             * =====================================================
             * VISTA PREVIA DE FOTOGRAFÍA
             * =====================================================
             */
            const inputFoto =
                document.getElementById('foto');

            const vistaPrevia =
                document.getElementById('vistaPrevia');

            const contenedorVistaPrevia =
                document.getElementById(
                    'contenedorVistaPrevia'
                );


            if (
                inputFoto
                && vistaPrevia
                && contenedorVistaPrevia
            ) {

                inputFoto.addEventListener(
                    'change',
                    function (event) {

                        const archivo =
                            event.target.files[0];


                        if (!archivo) {

                            vistaPrevia.src = '';

                            contenedorVistaPrevia
                                .classList
                                .add('d-none');

                            return;
                        }


                        /*
                         * Validación visual previa.
                         *
                         * El servidor continúa siendo responsable
                         * de la validación definitiva.
                         */
                        if (!archivo.type.startsWith('image/')) {

                            inputFoto.value = '';

                            vistaPrevia.src = '';

                            contenedorVistaPrevia
                                .classList
                                .add('d-none');

                            alert(
                                'El archivo seleccionado debe ser una imagen.'
                            );

                            return;
                        }


                        const urlTemporal =
                            URL.createObjectURL(archivo);


                        vistaPrevia.src =
                            urlTemporal;


                        contenedorVistaPrevia
                            .classList
                            .remove('d-none');


                        vistaPrevia.onload =
                            function () {

                                URL.revokeObjectURL(
                                    urlTemporal
                                );
                            };
                    }
                );
            }


            /*
             * =====================================================
             * EVITAR DOBLE ENVÍO
             * =====================================================
             */
            const formulario =
                document.getElementById(
                    'formAltaServicio'
                );

            const botonRegistrar =
                document.getElementById(
                    'btnRegistrarAlta'
                );


            if (
                formulario
                && botonRegistrar
            ) {

                formulario.addEventListener(
                    'submit',
                    function () {

                        if (!formulario.checkValidity()) {
                            return;
                        }

                        botonRegistrar.disabled =
                            true;

                        botonRegistrar.innerHTML =
                            '<i class="fas fa-spinner fa-spin mr-1"></i>'
                            + ' Registrando...';
                    }
                );
            }

        });
    </script>

@stop
