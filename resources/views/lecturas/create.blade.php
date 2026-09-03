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
                <div class="form-group" style="max-width: 500px;">
                    <label for="contador_id">Contador *</label>
                    <select name="contador_id" id="contador_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Selecciona un contador --</option>
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
                <hr>

                <dl class="row">
                    <dt class="col-sm-3">Cliente</dt>
                    <dd class="col-sm-9">{{ $contadorSeleccionado->cliente->nombre }}</dd>

                    <dt class="col-sm-3">Dirección de servicio</dt>
                    <dd class="col-sm-9">{{ $contadorSeleccionado->direccion_servicio }}</dd>

                    <dt class="col-sm-3">Última lectura registrada</dt>
                    <dd class="col-sm-9">{{ number_format($lecturaAnterior, 3) }} m³</dd>
                </dl>

                <form action="{{ route('lecturas.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="contador_id" value="{{ $contadorSeleccionado->id }}">

                    <div class="form-group">
                        <label for="periodo">Período (mes de la lectura) *</label>
                        <input type="month" name="periodo" id="periodo" class="form-control"
                               style="max-width: 250px;" value="{{ old('periodo') }}" required>
                    </div>

                    <div class="form-group">
                        <label for="lectura_actual">Lectura actual (m³) *</label>
                        <input type="number" step="0.001" min="{{ $lecturaAnterior }}" name="lectura_actual"
                               id="lectura_actual" class="form-control" style="max-width: 250px;"
                               value="{{ old('lectura_actual') }}" required>
                        <small class="form-text text-muted">
                            No puede ser menor a {{ number_format($lecturaAnterior, 3) }} m³.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="observacion">Observación</label>
                        <textarea name="observacion" id="observacion" class="form-control" rows="2">{{ old('observacion') }}</textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">Guardar lectura</button>
                    <a href="{{ route('lecturas.index') }}" class="btn btn-secondary">Cancelar</a>
                </form>
            @endif

        </div>
    </div>

@stop
