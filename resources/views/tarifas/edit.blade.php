@extends('adminlte::page')

@section('title', 'Editar Tarifa')

@section('content_header')
    <h1>Editar Tarifa</h1>
@stop

@section('content')

    @php
        $tipoBloqueado = $tieneRecibos || $tieneContadores;
        $tiposBase = [
            '1/2 paja',
            '1 paja',
            '2 pajas',
        ];

        $esTipoBase = in_array(
            $tarifa->tipo,
            $tiposBase,
            true
        );

        $cantidadActual = null;

        if (
            !$esTipoBase
            && preg_match(
                '/^(\d+)\s+pajas$/',
                trim($tarifa->tipo),
                $coincidencia
            )
        ) {
            $cantidadActual =
                (int) $coincidencia[1];
        }

        $tipoSelectorActual = $tipoBloqueado ? ($esTipoBase ? $tarifa->tipo : 'otra_cantidad') : old(
            'tipo_selector',
            $esTipoBase
                ? $tarifa->tipo
                : 'otra_cantidad'
        );

        $cantidadPajasActual = $tipoBloqueado ? $cantidadActual : old(
            'cantidad_pajas',
            $cantidadActual
        );
    @endphp

    <div class="card">
        <div class="card-body">

            @if ($tieneRecibos)
                <div class="alert alert-info">
                    Esta tarifa tiene recibos emitidos. Su nombre, tipo, capacidad, precios, mora y fecha inicial
                    se conservan para proteger el historial. Para cambiarlos, cree una nueva tarifa.
                    Puede ajustar la fecha final sin excluir recibos existentes o desactivar la tarifa.
                </div>
            @elseif ($tieneContadores)
                <div class="alert alert-info">
                    El tipo está protegido porque hay contadores asignados a esta tarifa.
                </div>
            @endif

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
                action="{{ route('tarifas.update', $tarifa) }}"
                method="POST"
            >
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="nombre">
                        Nombre *
                    </label>

                    <input
                        type="text"
                        name="nombre"
                        id="nombre"
                        class="form-control"
                        value="{{ $tieneRecibos ? $tarifa->nombre : old('nombre', $tarifa->nombre) }}"
                        @readonly($tieneRecibos)
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
                        @disabled($tipoBloqueado)
                    >
                        <option value="">
                            Seleccione un tipo
                        </option>

                        <option
                            value="1/2 paja"
                            {{ $tipoSelectorActual === '1/2 paja' ? 'selected' : '' }}
                        >
                            1/2 paja
                        </option>

                        <option
                            value="1 paja"
                            {{ $tipoSelectorActual === '1 paja' ? 'selected' : '' }}
                        >
                            1 paja
                        </option>

                        <option
                            value="2 pajas"
                            {{ $tipoSelectorActual === '2 pajas' ? 'selected' : '' }}
                        >
                            2 pajas
                        </option>

                        <option
                            value="otra_cantidad"
                            {{ $tipoSelectorActual === 'otra_cantidad' ? 'selected' : '' }}
                        >
                            Otra cantidad
                        </option>
                    </select>
                    @if ($tipoBloqueado)
                        <input type="hidden" name="tipo_selector" value="{{ $tipoSelectorActual }}">
                    @endif
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
                        value="{{ $cantidadPajasActual }}"
                        @readonly($tipoBloqueado)
                        placeholder="Ej. 3"
                    >

                    <small class="form-text text-muted">
                        Ingrese únicamente un número entero desde 3.
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
                        value="{{ number_format((float) $tarifa->capacidad, 3, '.', '') }} m³"
                        readonly
                    >

                    <small class="form-text text-muted">
                        @if ($tieneRecibos)
                            Se conserva la capacidad utilizada en los recibos históricos.
                        @else
                            Se calcula automáticamente según la cantidad de pajas.
                            1 paja equivale a 60 m³ mensuales.
                        @endif
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
                        value="{{ $tieneRecibos ? $tarifa->precio_por_m3 : old('precio_por_m3', $tarifa->precio_por_m3) }}"
                        @readonly($tieneRecibos)
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
                        value="{{ $tieneRecibos ? $tarifa->precio_exceso_m3 : old('precio_exceso_m3', $tarifa->precio_exceso_m3) }}"
                        @readonly($tieneRecibos)
                        required
                    >

                    <small class="form-text text-muted">
                        Se aplicará únicamente a los m³ que superen la capacidad mensual.
                    </small>
                </div>

                <hr>

                <h5 class="mb-3">
                    Configuración de mora
                </h5>

                <div class="alert alert-info">
                    La mora se aplicará cuando el recibo continúe pendiente después del día 10 del mes.
                    Puede configurarse porcentaje, monto fijo o ambas opciones.
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
                        value="{{ $tieneRecibos ? $tarifa->mora_porcentaje : old('mora_porcentaje', $tarifa->mora_porcentaje) }}"
                        @readonly($tieneRecibos)
                        placeholder="Ej. 5.00"
                    >
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
                        value="{{ $tieneRecibos ? $tarifa->mora_monto_fijo : old('mora_monto_fijo', $tarifa->mora_monto_fijo) }}"
                        @readonly($tieneRecibos)
                        placeholder="Ej. 10.00"
                    >

                    <small class="form-text text-muted">
                        Si existen ambas formas de mora, ambas se sumarán.
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
                        value="{{ $tieneRecibos ? $tarifa->vigente_desde->format('Y-m-d') : old('vigente_desde', $tarifa->vigente_desde->format('Y-m-d')) }}"
                        @readonly($tieneRecibos)
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
                        value="{{ old('vigente_hasta', $tarifa->vigente_hasta?->format('Y-m-d')) }}"
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
                        {{ old('activo', $tarifa->activo) ? 'checked' : '' }}
                    >

                    <label
                        class="form-check-label"
                        for="activo"
                    >
                        Activa
                    </label>
                </div>

                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Actualizar
                    </button>

                    <a
                        href="{{ route('tarifas.index') }}"
                        class="btn btn-secondary"
                    >
                        Cancelar
                    </a>
                </div>
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
            const tieneRecibos = @json($tieneRecibos);

            function calcularCapacidad() {
                if (tieneRecibos) {
                    return;
                }
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
