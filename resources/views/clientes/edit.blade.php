@extends('adminlte::page')

@section('title', 'Editar Cliente')

@section('content_header')
    <h1>Editar Cliente</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-body">

            @php($erroresGenerales = collect($errors->getMessages())->except(['dpi', 'nit', 'tipo_documento', 'telefono'])->flatten()->all())
            @if (count($erroresGenerales))
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($erroresGenerales as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('clientes.update', $cliente) }}" method="POST"
                  data-validacion-campos data-contexto-cliente="editar"
                  data-consulta-documento-url="{{ route('clientes.consultar-documento') }}">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre"
                           class="form-control" value="{{ old('nombre', $cliente->nombre) }}" required>
                </div>

                <div class="form-group">
                    @include('partials.documento-cliente', ['clienteDocumento' => $cliente])
                </div>

                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="text" name="telefono" data-validacion="telefono" data-validacion-max="25"
                           aria-describedby="telefono-error telefono-feedback" aria-invalid="{{ $errors->has('telefono') ? 'true' : 'false' }}" maxlength="25" id="telefono"
                           class="form-control @error('telefono') is-invalid @enderror" value="{{ old('telefono', $cliente->telefono) }}">
                    @include('partials.validacion-campo', ['campo' => 'telefono'])
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

                <div class="d-flex flex-column flex-sm-row flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">Actualizar</button>
                    <a href="{{ route('clientes.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>

        </div>
    </div>

@stop