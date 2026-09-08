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

            <form
                method="GET"
                action="{{ route('contadores.index') }}"
                class="mb-3"
            >
                <div class="input-group" style="max-width: 500px;">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Buscar por número, dirección, referencia o sector"
                        value="{{ $busqueda }}"
                    >

                    <div class="input-group-append">
                        <button
                            class="btn btn-secondary"
                            type="submit"
                        >
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

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>N° Registro</th>
                            <th>Cliente</th>
                            <th>Tarifa</th>
                            <th>Dirección de servicio</th>
                            <th>Punto de referencia</th>
                            <th>Sector</th>
                            <th>Fotografía</th>
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
                                    @if ($contador->tarifa)
                                        <strong>
                                            {{ $contador->tarifa->nombre }}
                                        </strong>

                                        <br>

                                        <small class="text-muted">
                                            {{ $contador->tarifa->tipo }}

                                            @if (!is_null($contador->tarifa->capacidad))
                                                · {{ $contador->tarifa->capacidad }} m³
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-danger">
                                            Sin tarifa
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $contador->direccion_servicio }}
                                </td>

                                <td>
                                    {{ $contador->punto_referencia ?? '—' }}
                                </td>

                                <td>
                                    {{ $contador->sector ?? '—' }}
                                </td>

                                <td class="text-center">
                                    @if ($contador->foto_ruta)
                                        <img
                                            src="{{ asset('storage/' . $contador->foto_ruta) }}"
                                            alt="Fotografía del contador"
                                            class="img-thumbnail"
                                            style="width: 80px; height: 60px; object-fit: cover;"
                                        >
                                    @else
                                        <span class="text-muted">
                                            Sin foto
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($contador->activo)
                                        <span class="badge text-bg-success">
                                            Activo
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">
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
                                <td
                                    colspan="9"
                                    class="text-center"
                                >
                                    No hay contadores registrados todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $contadores->links() }}

        </div>
    </div>

@stop