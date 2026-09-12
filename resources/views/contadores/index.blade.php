@extends('adminlte::page')

@section('title', 'Contadores')

@section('content_header')
    <h1>Contadores</h1>
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
                <form method="GET" action="{{ route('contadores.index') }}" class="d-flex flex-column flex-sm-row gap-2 col-12 col-lg-7">
                    <input type="text" name="q" value="{{ $busqueda }}"
                           class="form-control" placeholder="Buscar por número, dirección, referencia o sector">
                    <button type="submit" class="btn btn-secondary text-nowrap">Buscar</button>
                </form>

                <a href="{{ route('contadores.create') }}" class="btn btn-primary">
                    + Nuevo Contador
                </a>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>N° Registro</th>
                            <th>Lectura inicial</th>
                            <th>Cliente</th>
                            <th>Tarifa</th>
                            <th>Servicio</th>
                            <th>Dirección de servicio</th>
                            <th>Punto de referencia</th>
                            <th>Sector</th>
                            <th>Fotografía</th>
                            <th>Estado</th>
                            <th style="width: 160px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contadores as $contador)
                            <tr>
                                <td>{{ $contador->numero_registro }}</td>
                                <td>
                                    @if ($contador->lectura_inicial === null)
                                        <span class="text-muted">Sin establecer</span>
                                    @else
                                        {{ number_format((float) $contador->lectura_inicial, 3) }} m³
                                    @endif
                                </td>
                                <td>{{ $contador->cliente->nombre }}</td>
                                <td>
                                    @if ($contador->tarifa)
                                        <strong>{{ $contador->tarifa->nombre }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            {{ $contador->tarifa->tipo }}
                                            @if (!is_null($contador->tarifa->capacidad))
                                                · {{ $contador->tarifa->capacidad }} m³
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-danger">Sin tarifa</span>
                                    @endif
                                </td>
                                <td>{{ $contador->servicio->nombre ?? '—' }}</td>
                                <td>{{ $contador->direccion_servicio }}</td>
                                <td>{{ $contador->punto_referencia ?? '—' }}</td>
                                <td>{{ $contador->sector ?? '—' }}</td>
                                <td class="text-center">
                                    @if ($contador->foto_ruta)
                                        <img src="{{ asset('storage/' . $contador->foto_ruta) }}"
                                             alt="Fotografía del contador" class="img-thumbnail"
                                             style="width: 80px; height: 60px; object-fit: cover;">
                                    @else
                                        <span class="text-muted">Sin foto</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($contador->activo)
                                        <span class="badge text-bg-success">Activo</span>
                                    @else
                                        <span class="badge text-bg-secondary">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="{{ route('contadores.edit', $contador) }}"
                                           class="btn btn-sm btn-warning">Editar</a>

                                        <form action="{{ route('contadores.destroy', $contador) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar este contador?');">
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
                                <td colspan="11" class="text-center py-4">
                                    No hay contadores registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $contadores->links('pagination::bootstrap-5') }}
        </div>
    </div>

@stop