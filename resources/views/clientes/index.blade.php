@extends('adminlte::page')

@section('title', 'Clientes')

@section('content_header')
    <h1>Clientes</h1>
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
            <div class="d-flex justify-content-between align-items-center">
                <form method="GET" class="form-inline">
                    <input type="text" name="q" value="{{ $busqueda }}"
                           class="form-control mr-2" placeholder="Buscar por nombre o teléfono">
                    <button type="submit" class="btn btn-secondary">Buscar</button>
                </form>

                <a href="{{ route('clientes.create') }}" class="btn btn-primary">
                    + Nuevo Cliente
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                        <th style="width: 160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr>
                            <td>{{ $cliente->nombre }}</td>
                            <td>{{ $cliente->telefono ?? '—' }}</td>
                            <td>{{ $cliente->direccion_principal ?? '—' }}</td>
                            <td>
                                @if ($cliente->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('clientes.edit', $cliente) }}"
                                   class="btn btn-sm btn-warning">Editar</a>

                                <form action="{{ route('clientes.destroy', $cliente) }}"
                                      method="POST" class="d-inline"
                                      onsubmit="return confirm('¿Seguro que querés eliminar este cliente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                No hay clientes registrados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $clientes->links() }}
        </div>
    </div>

@stop