@extends('adminlte::page')

@section('title', 'Tarifas')

@section('content_header')
    <h1>Tarifas</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">{{ session('exito') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">

            <form method="GET" action="{{ route('tarifas.index') }}" class="mb-3">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" name="q" class="form-control"
                           placeholder="Buscar por nombre" value="{{ $busqueda }}">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">Buscar</button>
                    </div>
                </div>
            </form>

            <a href="{{ route('tarifas.create') }}" class="btn btn-primary mb-3">+ Nueva Tarifa</a>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio/m³</th>
                        <th>Vigente desde</th>
                        <th>Vigente hasta</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tarifas as $tarifa)
                        <tr>
                            <td>{{ $tarifa->nombre }}</td>
                            <td>Q{{ number_format($tarifa->precio_por_m3, 2) }}</td>
                            <td>{{ $tarifa->vigente_desde->format('d/m/Y') }}</td>
                            <td>{{ $tarifa->vigente_hasta ? $tarifa->vigente_hasta->format('d/m/Y') : '—' }}</td>
                            <td>
                                @if ($tarifa->activo)
                                    <span class="badge badge-success">Activa</span>
                                @else
                                    <span class="badge badge-secondary">Inactiva</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tarifas.edit', $tarifa) }}" class="btn btn-sm btn-warning">Editar</a>
                                <form action="{{ route('tarifas.destroy', $tarifa) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar esta tarifa?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay tarifas registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $tarifas->links() }}

        </div>
    </div>

@stop