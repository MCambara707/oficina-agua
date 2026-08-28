@extends('adminlte::page')

@section('title', 'Editar Contador')

@section('content_header')
    <h1>Editar Contador</h1>
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

            <form action="{{ route('contadores.update', $contador) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="cliente_id">Cliente *</label>
                    <select name="cliente_id" id="cliente_id" class="form-control" required>
                        @foreach ($clientes as $cliente)
                            <option value="{{ $cliente->id }}"
                                {{ old('cliente_id', $contador->cliente_id) == $cliente->id ? 'selected' : '' }}>
                                {{ $cliente->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="numero_registro">Número de registro *</label>
                    <input type="text" name="numero_registro" id="numero_registro"
                           class="form-control" value="{{ old('numero_registro', $contador->numero_registro) }}" required>
                </div>

                <div class="form-group">
                    <label for="direccion_servicio">Dirección de servicio *</label>
                    <input type="text" name="direccion_servicio" id="direccion_servicio"
                           class="form-control" value="{{ old('direccion_servicio', $contador->direccion_servicio) }}" required>
                </div>

                <div class="form-group">
                    <label for="referencia">Referencia</label>
                    <input type="text" name="referencia" id="referencia"
                           class="form-control" value="{{ old('referencia', $contador->referencia) }}">
                </div>

                <div class="form-group">
                    <label for="sector">Sector</label>
                    <input type="text" name="sector" id="sector"
                           class="form-control" value="{{ old('sector', $contador->sector) }}">
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" name="activo" id="activo"
                           class="form-check-input" value="1" {{ $contador->activo ? 'checked' : '' }}>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>

                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('contadores.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>

        </div>
    </div>

@stop
