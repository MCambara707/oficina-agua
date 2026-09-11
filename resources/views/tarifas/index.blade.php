@extends('adminlte::page')

@section('title', 'Tarifas')

@section('content_header')
    <h1>Tarifas</h1>
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
                action="{{ route('tarifas.index') }}"
                class="mb-3"
            >
                <div
                    class="d-flex flex-column flex-sm-row gap-2 col-12 col-lg-7"
                >
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Buscar por nombre"
                        value="{{ $busqueda }}"
                    >

                    <button
                        class="btn btn-secondary"
                        type="submit"
                    >
                        Buscar
                    </button>
                </div>
            </form>

            <a
                href="{{ route('tarifas.create') }}"
                class="btn btn-primary mb-3"
            >
                + Nueva Tarifa
            </a>

            <div class="table-responsive">

                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Capacidad</th>
                            <th>Precio/m³</th>
                            <th>Exceso/m³</th>
                            <th>Mora %</th>
                            <th>Mora fija</th>
                            <th>Vigente desde</th>
                            <th>Vigente hasta</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($tarifas as $tarifa)

                            <tr>
                                <td>
                                    {{ $tarifa->nombre }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $tarifa->tipo }}
                                    </strong>
                                </td>

                                <td>
                                    @if ($tarifa->capacidad !== null)
                                        {{ (int) $tarifa->capacidad }} m³
                                    @else
                                        <span class="text-danger">
                                            Sin configurar
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    Q{{ number_format(
                                        (float) $tarifa->precio_por_m3,
                                        2
                                    ) }}
                                </td>

                                <td>
                                    @if ($tarifa->precio_exceso_m3 !== null)
                                        Q{{ number_format(
                                            (float) $tarifa->precio_exceso_m3,
                                            2
                                        ) }}
                                    @else
                                        <span class="text-danger">
                                            Sin configurar
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($tarifa->mora_porcentaje !== null)
                                        {{ number_format(
                                            (float) $tarifa->mora_porcentaje,
                                            2
                                        ) }}%
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($tarifa->mora_monto_fijo !== null)
                                        Q{{ number_format(
                                            (float) $tarifa->mora_monto_fijo,
                                            2
                                        ) }}
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $tarifa->vigente_desde->format('d/m/Y') }}
                                </td>

                                <td>
                                    @if ($tarifa->vigente_hasta)
                                        {{ $tarifa->vigente_hasta->format('d/m/Y') }}
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($tarifa->activo)
                                        <span class="badge text-bg-success">
                                            Activa
                                        </span>
                                    @else
                                        <span class="badge text-bg-secondary">
                                            Inactiva
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a
                                            href="{{ route('tarifas.edit', $tarifa) }}"
                                            class="btn btn-sm btn-warning"
                                        >
                                            Editar
                                        </a>

                                        <form
                                            action="{{ route('tarifas.destroy', $tarifa) }}"
                                            method="POST"
                                            class="d-inline"
                                            onsubmit="return confirm('¿Eliminar esta tarifa?');"
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
                                    </div>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="11"
                                    class="text-center"
                                >
                                    No hay tarifas registradas todavía.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>

            </div>

            {{ $tarifas->links('pagination::bootstrap-5') }}

        </div>
    </div>

@stop
