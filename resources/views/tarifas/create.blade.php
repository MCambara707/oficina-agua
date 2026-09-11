@extends('adminlte::page')

@section('title', 'Nueva Tarifa')

@section('content_header')
    <h1>Nueva Tarifa</h1>
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

            <form
                action="{{ route('tarifas.store') }}"
                method="POST"
            >
                @csrf

                <div class="form-group">
                    <label for="nombre">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        class="form-control"
                        value="{{ old('nombre') }}"
                        maxlength="100"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="tipo_selector">
                        Tipo *
                    </label>

                    <select
                        name="tipo_selector"
                        id="tipo_selector"
                        class="form-control"
                        required
                    >
                        <option value="">
                            Seleccione un tipo
                        </option>

                        <option
                            value="1/2 paja"
                            {{ old('tipo_selector') === '1/2 paja' ? 'selected' : '' }}
                        >
                            1/2 paja
                        </option>

                        <option
                            value="1 paja"
                            {{ old('tipo_selector') === '1 paja' ? 'selected' : '' }}
                        >
                            1 paja
                        </option>

                        <option
                            value="2 pajas"
                            {{ old('tipo_selector') === '2 pajas' ? 'selected' : '' }}
                        >
                            2 pajas
                        </option>

                        <option
                            value="otra_cantidad"
                            {{ old('tipo_selector') === 'otra_cantidad' ? 'selected' : '' }}
                        >
                            Otra cantidad
                        </option>
                    </select>
                </div>

                <div
                    class="form-group"
                    id="grupo_cantidad_pajas"
                    style="display: none;"
                >
                    <label for="cantidad_pajas">
                        Cantidad de pajas *
                    </label>

                    <input
                        type="number"
                        name="cantidad_pajas"
                        id="cantidad_pajas"
                        class="form-control"
                        min="3"
                        step="1"
                        value="{{ old('cantidad_pajas') }}"
                        placeholder="Ej. 3"
                    >

                    <small class="form-text text-muted">
                        Ingrese únicamente un número entero desde 3.
                        Ejemplo: 3, 4, 5 o 10.
                    </small>
                </div>

                <div class="form-group">
                    <label for="capacidad_visual">
                        Capacidad mensual (m³)
                    </label>

                    <input
                        type="text"
                        id="capacidad_visual"
                        class="form-control"
                        readonly
                        placeholder="Seleccione un tipo"
                    >

                    <small class="form-text text-muted">
                        La capacidad se calcula automáticamente:
                        1 paja equivale a 60 m³ mensuales.
                    </small>
                </div>

                <div class="form-group">
                    <label for="precio_por_m3">
                        Precio por m³ (Q) *
                    </label>

                    <input
                        type="number"
                        name="precio_por_m3"
                        id="precio_por_m3"
                        class="form-control"
                        step="0.01"
                        min="0.01"
                        value="{{ old('precio_por_m3') }}"
                        placeholder="Ej. 5.00"
                        required
                    >

                    <small class="form-text text-muted">
                        Precio aplicado al consumo dentro de la capacidad contratada.
                    </small>
                </div>

                <div class="form-group">
                    <label for="precio_exceso_m3">
                        Precio por exceso por m³ (Q) *
                    </label>

                    <input
                        type="number"
                        name="precio_exceso_m3"
                        id="precio_exceso_m3"
                        class="form-control"
                        step="0.01"
                        min="0.01"
                        value="{{ old('precio_exceso_m3') }}"
                        placeholder="Ej. 8.00"
                        required
                    >

                    <small class="form-text text-muted">
                        Este precio se aplicará solamente a los m³ que superen la capacidad contratada.
                    </small>
                </div>

                <hr>

                <h5 class="mb-3">
                    Configuración de mora
                </h5>

                <div class="alert alert-info">
                    La mora se aplicará cuando el recibo continúe pendiente después del día 10 del mes.
                    Puede utilizarse porcentaje, monto fijo o ambas opciones.
                </div>

                <div class="form-group">
                    <label for="mora_porcentaje">
                        Mora porcentual (%)
                    </label>

                    <input
                        type="number"
                        name="mora_porcentaje"
                        id="mora_porcentaje"
                        class="form-control"
                        step="0.01"
                        min="0.01"
                        max="100"
                        value="{{ old('mora_porcentaje') }}"
                        placeholder="Ej. 5.00"
                    >

                    <small class="form-text text-muted">
                        Ejemplo: 5 representa una mora del 5%.
                    </small>
                </div>

                <div class="form-group">
                    <label for="mora_monto_fijo">
                        Mora fija (Q)
                    </label>

                    <input
                        type="number"
                        name="mora_monto_fijo"
                        id="mora_monto_fijo"
                        class="form-control"
                        step="0.01"
                        min="0.01"
                        value="{{ old('mora_monto_fijo') }}"
                        placeholder="Ej. 10.00"
                    >

                    <small class="form-text text-muted">
                        Si utiliza porcentaje y monto fijo, ambos se sumarán al aplicar la mora.
                    </small>
                </div>

                <small class="form-text text-muted mb-3 d-block">
                    Debe configurar al menos una opción de mora.
                </small>

                <hr>

                <div class="form-group">
                    <label for="vigente_desde">
                        Vigente desde *
                    </label>

                    <input
                        type="date"
                        name="vigente_desde"
                        id="vigente_desde"
                        class="form-control"
                        value="{{ old('vigente_desde') }}"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="vigente_hasta">
                        Vigente hasta
                    </label>

                    <input
                        type="date"
                        name="vigente_hasta"
                        id="vigente_hasta"
                        class="form-control"
                        value="{{ old('vigente_hasta') }}"
                    >

                    <small class="form-text text-muted">
                        Déjelo vacío si seguirá vigente indefinidamente.
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
                        Activa
                    </label>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Guardar
                </button>

                <a
                    href="{{ route('tarifas.index') }}"
                    class="btn btn-secondary"
                >
                    Cancelar
                </a>
            </form>

        </div>
    </div>

@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const selector =
                document.getElementById('tipo_selector');

            const grupoCantidad =
                document.getElementById('grupo_cantidad_pajas');

            const cantidad =
                document.getElementById('cantidad_pajas');

            const capacidad =
                document.getElementById('capacidad_visual');

            const capacidadPorPaja = 60;

            function calcularCapacidad() {
                let capacidadCalculada = null;

                if (selector.value === '1/2 paja') {
                    capacidadCalculada =
                        capacidadPorPaja / 2;
                }

                if (selector.value === '1 paja') {
                    capacidadCalculada =
                        capacidadPorPaja;
                }

                if (selector.value === '2 pajas') {
                    capacidadCalculada =
                        capacidadPorPaja * 2;
                }

                if (selector.value === 'otra_cantidad') {
                    const numeroPajas =
                        parseInt(cantidad.value);

                    if (
                        !isNaN(numeroPajas)
                        && numeroPajas >= 3
                    ) {
                        capacidadCalculada =
                            numeroPajas * capacidadPorPaja;
                    }
                }

                if (capacidadCalculada !== null) {
                    capacidad.value =
                        capacidadCalculada + ' m³';
                } else {
                    capacidad.value = '';
                }
            }

            function actualizarTipo() {
                const mostrar =
                    selector.value === 'otra_cantidad';

                grupoCantidad.style.display =
                    mostrar ? 'block' : 'none';

                cantidad.required = mostrar;
                cantidad.disabled = !mostrar;

                if (!mostrar) {
                    cantidad.value = '';
                }

                calcularCapacidad();
            }

            selector.addEventListener(
                'change',
                actualizarTipo
            );

            cantidad.addEventListener(
                'input',
                calcularCapacidad
            );

            actualizarTipo();
        });
    </script>
@stop