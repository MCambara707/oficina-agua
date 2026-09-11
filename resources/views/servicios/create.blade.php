@extends('adminlte::page')

@section('title', 'Nuevo Servicio')

@section('content_header')
    <h1>Nuevo Servicio</h1>
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

            <form action="{{ route('servicios.store') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control" value="{{ old('nombre') }}" required>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea name="descripcion" id="descripcion"
                              class="form-control" rows="3">{{ old('descripcion') }}</textarea>
                </div>

                <div class="form-group form-check">
                    <input type="checkbox" name="activo" id="activo"
                           class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="activo">Activo</label>
                </div>

                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <a href="{{ route('servicios.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@stop