@extends('adminlte::page')

@section('title', 'Editar Tarifa')

@section('content_header')
    <h1>Editar Tarifa</h1>
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

            <form action="{{ route('tarifas.update', $tarifa) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control" value="{{ old('nombre', $tarifa->nombre) }}" required>
                </div>

                <div class="form-group">
                    <label for="precio_por_m3">Precio por m³ (Q) *</label>
                    <input type="number" step="0.01" min="0" name="precio_por_m3" id="precio_por_m3"
                           class="form-control" value="{{ old('precio_por_m3', $tarifa->precio_por_m3) }}" required>
                </div>

                <div class="form-group">
                    <label for="vigente_desde">Vigente desde *</label>
                    <input type="date" name="vigente_desde" id="vigente_desde"
                           class="form-control" value="{{ old('vigente_desde', $tarifa->vigente_desde->format('Y-m-d')) }}" required>
                </div>

                <div class="form-group">
                    <label for="vigente_hasta">Vigente hasta</label>
                    <input type="date" name="vigente_hasta" id="vigente_hasta"
                           class="form-control" value="{{ old('vigente_hasta', $tarifa->vigente_hasta?->format('Y-m-d')) }}">
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" name="activo" id="activo"
                           class="form-check-input" value="1" {{ $tarifa->activo ? 'checked' : '' }}>
                    <label class="form-check-label" for="activo">Activa</label>
                </div>

                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('tarifas.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>

        </div>
    </div>

@stop