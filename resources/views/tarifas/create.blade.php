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

            <form action="{{ route('tarifas.store') }}" method="POST">
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
                        Ejemplo: 3, 10 o 30.
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
                        min="0"
                        value="{{ old('precio_por_m3') }}"
                        required
                    >
                </div>

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
                        Déjalo vacío si sigue vigente indefinidamente.
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
            const selector = document.getElementById('tipo_selector');
            const grupoCantidad = document.getElementById('grupo_cantidad_pajas');
            const cantidad = document.getElementById('cantidad_pajas');

            function actualizarCantidad() {
                const mostrar = selector.value === 'otra_cantidad';

                grupoCantidad.style.display = mostrar ? 'block' : 'none';

                cantidad.required = mostrar;
                cantidad.disabled = !mostrar;

                if (!mostrar) {
                    cantidad.value = '';
                }
            }

            selector.addEventListener('change', actualizarCantidad);

            actualizarCantidad();
        });
    </script>
@stop