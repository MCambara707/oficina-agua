@extends('adminlte::page')

@section('title', 'Editar Cliente')

@section('content_header')
    <h1>Editar Cliente</h1>
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

            <form action="{{ route('clientes.update', $cliente) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control" value="{{ old('nombre', $cliente->nombre) }}" required>
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" name="telefono" id="telefono"
                           class="form-control" value="{{ old('telefono', $cliente->telefono) }}">
                </div>

                <div class="form-group">
                    <label for="direccion_principal">Dirección</label>
                    <input type="text" name="direccion_principal" id="direccion_principal"
                           class="form-control" value="{{ old('direccion_principal', $cliente->direccion_principal) }}">
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" name="activo" id="activo"
                           class="form-check-input" value="1"
                           {{ $cliente->activo ? 'checked' : '' }}>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>

                <button type="submit" class="btn btn-primary">Actualizar</button>
                <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>

        </div>
    </div>

@stop