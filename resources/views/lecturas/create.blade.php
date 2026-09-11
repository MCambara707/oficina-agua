@extends('adminlte::page')

@section('title', 'Registrar Lectura')

@section('content_header')
    <h1>Registrar Lectura</h1>
@stop

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form method="GET" action="{{ route('lecturas.create') }}" class="mb-4">
                <div class="form-group mb-0" style="max-width: 500px;">
                    <label for="contador_id" class="mb-1">
                        <i class="bi bi-speedometer2 me-1"></i>Contador *
                    </label>
                    <select name="contador_id" id="contador_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Selecciona un contador…</option>
                        @foreach ($contadores as $contador)
                            <option value="{{ $contador->id }}"
                                {{ optional($contadorSeleccionado)->id == $contador->id ? 'selected' : '' }}>
                                {{ $contador->numero_registro }} — {{ $contador->cliente->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <small class="form-text text-muted">
                        Al elegir un contador se carga su última lectura registrada.
                    </small>
                </div>
            </form>

            @if ($contadorSeleccionado)
                <div class="bg-light border rounded p-3 mb-4">
                    <div class="row gy-2">
                        <div class="col-12 col-md-4">
                            <div class="text-muted small"><i class="bi bi-person me-1"></i>Cliente</div>
                            <div class="fw-semibold">{{ $contadorSeleccionado->cliente->nombre }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="text-muted small"><i class="bi bi-geo-alt me-1"></i>Dirección</div>
                            <div class="fw-semibold">{{ $contadorSeleccionado->direccion_servicio }}</div>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="text-muted small"><i class="bi bi-clock-history me-1"></i>Última lectura</div>
                            <div class="fw-semibold">{{ number_format($lecturaAnterior, 3) }} m³</div>
                        </div>
                    </div>
                </div>

                <form action="{{ route('lecturas.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="contador_id" value="{{ $contadorSeleccionado->id }}">

                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="periodo">
                                    <i class="bi bi-calendar3 me-1"></i>Período (mes de la lectura) *
                                </label>
                                <input type="month" name="periodo" id="periodo" class="form-control"
                                       value="{{ old('periodo') }}" required>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <div class="form-group mb-3">
                                <label for="lectura_actual">
                                    <i class="bi bi-droplet me-1"></i>Lectura actual (m³) *
                                </label>
                                <input type="number" step="0.001" min="{{ $lecturaAnterior }}" name="lectura_actual"
                                       id="lectura_actual" class="form-control"
                                       value="{{ old('lectura_actual') }}" required>
                                <small class="form-text text-muted">
                                    No puede ser menor a {{ number_format($lecturaAnterior, 3) }} m³.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="observacion">Observación</label>
                        <textarea name="observacion" id="observacion" class="form-control" rows="2"
                                  maxlength="255">{{ old('observacion') }}</textarea>
                    </div>

                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Guardar lectura
                        </button>
                        <a href="{{ route('lecturas.index') }}" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            @else
                <div class="text-center text-muted py-5">
                    <i class="bi bi-speedometer2" style="font-size: 2.5rem;"></i>
                    <p class="mt-2 mb-0">Elige un contador arriba para empezar a registrar la lectura.</p>
                </div>
            @endif

        </div>
    </div>

@stop