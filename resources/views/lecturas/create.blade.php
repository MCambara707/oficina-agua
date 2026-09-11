@extends('adminlte::page')

@section('title', 'Registrar Lectura')

@section('content_header')
    <h1>Registrar Lectura</h1>
@stop

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card lectura-form">
        <div class="card-body">

            <form method="GET" action="{{ route('lecturas.create') }}" class="mb-4">
                <div class="form-group mb-0">
                    <label for="contador_id">Contador *</label>
                    <select name="contador_id" id="contador_id" class="form-select" aria-describedby="contador-ayuda" onchange="this.form.submit()">
                        <option value="">-- Selecciona un contador --</option>
                        @foreach ($contadores as $contador)
                            <option value="{{ $contador->id }}"
                                {{ optional($contadorSeleccionado)->id == $contador->id ? 'selected' : '' }}>
                                {{ $contador->numero_registro }} — {{ $contador->cliente->nombre }}
                            </option>
                        @endforeach
                    </select>
                    <small id="contador-ayuda" class="form-text text-muted">
                        Al elegir un contador se carga su última lectura registrada.
                    </small>
                </div>
            </form>

            @if ($contadorSeleccionado)
                <hr>

                <dl class="row gy-1 mb-4">
                    <dt class="col-md-4">Cliente</dt>
                    <dd class="col-md-8">{{ $contadorSeleccionado->cliente->nombre }}</dd>

                    <dt class="col-md-4">Dirección de servicio</dt>
                    <dd class="col-md-8">{{ $contadorSeleccionado->direccion_servicio }}</dd>

                    <dt class="col-md-4">Lectura anterior / inicial</dt>
                    <dd class="col-md-8">{{ $lecturaAnterior === null ? 'Sin lectura inicial registrada' : number_format($lecturaAnterior, 3).' m³' }}</dd>
                </dl>

                @if ($lecturaAnterior === null)
                    <div class="alert alert-warning" role="alert">
                        Falta la lectura inicial. Administrador o Secretaria deben registrarla en Contadores antes de la primera lectura.
                    </div>
                @else
                    <form action="{{ route('lecturas.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="contador_id" value="{{ $contadorSeleccionado->id }}">

                        <div class="row">
                            <div class="form-group col-12 col-sm-6 col-lg-4">
                                <label for="periodo">Período (mes de la lectura) *</label>
                                <input type="month" name="periodo" id="periodo" class="form-control"
                                       value="{{ old('periodo') }}" required>
                            </div>

                            <div class="form-group col-12 col-sm-6 col-lg-4">
                                <label for="lectura_actual">Lectura actual (m³) *</label>
                                <input type="number" step="0.001" min="{{ $lecturaAnterior }}" name="lectura_actual"
                                       id="lectura_actual" class="form-control" inputmode="decimal" aria-describedby="lectura-ayuda"
                                       value="{{ old('lectura_actual') }}" required>
                                <small id="lectura-ayuda" class="form-text text-muted">
                                    No puede ser menor a {{ number_format($lecturaAnterior, 3) }} m³.
                                </small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="observacion">Observación</label>
                            <textarea name="observacion" id="observacion" class="form-control" rows="3">{{ old('observacion') }}</textarea>
                        </div>

                        <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Guardar lectura</button>
                            <a href="{{ route('lecturas.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                @endif
            @endif

        </div>
    </div>

@stop
