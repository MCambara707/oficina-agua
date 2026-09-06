@extends('adminlte::page')

@section('title', 'Contadores')

@section('content_header')
    <h1>Contadores</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">
            {{ session('exito') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">

            <form method="GET" action="{{ route('contadores.index') }}" class="mb-3">
                <div class="input-group" style="max-width: 500px;">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Buscar por número, dirección, referencia o sector"
                        value="{{ $busqueda }}"
                    >

                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">
                            Buscar
                        </button>
                    </div>
                </div>
            </form>

            <a
                href="{{ route('contadores.create') }}"
                class="btn btn-primary mb-3"
            >
                + Nuevo Contador
            </a>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>N° Registro</th>
                        <th>Cliente</th>
                        <th>Dirección de servicio</th>
                        <th>Punto de referencia</th>
                        <th>Sector</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($contadores as $contador)
                        <tr>
                            <td>
                                {{ $contador->numero_registro }}
                            </td>

                            <td>
                                {{ $contador->cliente->nombre }}
                            </td>

                            <td>
                                {{ $contador->direccion_servicio ?? '—' }}
                            </td>

                            <td>
                                {{ $contador->punto_referencia ?? '—' }}
                            </td>

                            <td>
                                {{ $contador->sector ?? '—' }}
                            </td>

                            <td>
                                @if ($contador->activo)
                                    <span class="badge badge-success">
                                        Activo
                                    </span>
                                @else
                                    <span class="badge badge-secondary">
                                        Inactivo
                                    </span>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ route('contadores.edit', $contador) }}"
                                    class="btn btn-sm btn-warning"
                                >
                                    Editar
                                </a>

                                <form
                                    action="{{ route('contadores.destroy', $contador) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('¿Eliminar este contador?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-danger"
                                    >
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                No hay contadores registrados todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $contadores->links() }}

        </div>
    </div>

@stop