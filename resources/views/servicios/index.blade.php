@extends('adminlte::page')

@section('title', 'Servicios')

@section('content_header')
    <h1>Servicios</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">{{ session('exito') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <form method="GET" class="d-flex flex-column flex-sm-row gap-2 col-12 col-lg-7">
                    <input type="text" name="q" value="{{ $busqueda }}"
                           class="form-control" placeholder="Buscar por nombre">
                    <button type="submit" class="btn btn-secondary text-nowrap">Buscar</button>
                </form>

                <a href="{{ route('servicios.create') }}" class="btn btn-primary">
                    + Nuevo Servicio
                </a>
            </div>
        </div>

        <div class="card-body p-0 table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th style="width: 160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servicios as $servicio)
                        <tr>
                            <td>{{ $servicio->nombre }}</td>
                            <td>{{ $servicio->descripcion ?? '—' }}</td>
                            <td>
                                @if ($servicio->activo)
                                    <span class="badge text-bg-success">Activo</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('servicios.edit', $servicio) }}"
                                       class="btn btn-sm btn-warning">Editar</a>

                                    <form action="{{ route('servicios.destroy', $servicio) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Seguro que querés eliminar este servicio?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                No hay servicios registrados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $servicios->links('pagination::bootstrap-5') }}
        </div>
    </div>

@stop