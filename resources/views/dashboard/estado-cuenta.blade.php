@extends('adminlte::page')

@section('title', 'Estado de cuenta')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-1">Dashboard de estado de cuenta</h1>
            <p class="text-muted mb-0">
                Consulta el estado de pago de los clientes.
            </p>
        </div>
    </div>
@stop

@section('content')

    {{-- Filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-funnel me-1"></i>
                Filtros
            </h3>
        </div>

        <div class="card-body">
            <form method="GET"
                  action="{{ route('dashboard.estado-cuenta') }}"
                  class="row g-3">

                <div class="col-md-6">
                    <label for="busqueda" class="form-label">
                        Cliente
                    </label>

                    <input
                        type="text"
                        name="busqueda"
                        id="busqueda"
                        class="form-control"
                        value="{{ $busqueda }}"
                        placeholder="Buscar por nombre del cliente"
                    >
                </div>

                <div class="col-md-4">
                    <label for="estado" class="form-label">
                        Estado
                    </label>

                    <select
                        name="estado"
                        id="estado"
                        class="form-select"
                    >
                        <option value="">Todos</option>

                        <option value="al-dia"
                            @selected($estado === 'al-dia')>
                            Al día
                        </option>

                        <option value="pendiente"
                            @selected($estado === 'pendiente')>
                            Pendiente
                        </option>

                        <option value="con-mora"
                            @selected($estado === 'con-mora')>
                            Con mora
                        </option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                    >
                        <i class="bi bi-search me-1"></i>
                        Filtrar
                    </button>
                </div>

                @if ($busqueda !== '' || $estado !== '')
                    <div class="col-12">
                        <a
                            href="{{ route('dashboard.estado-cuenta') }}"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            <i class="bi bi-x-circle me-1"></i>
                            Limpiar filtros
                        </a>
                    </div>
                @endif

            </form>
        </div>
    </div>

    {{-- Resumen --}}
    @php
        $totalClientes = $filas->count();
        $totalAlDia = $filas->where('estado_clave', 'al-dia')->count();
        $totalPendientes = $filas->where('estado_clave', 'pendiente')->count();
        $totalConMora = $filas->where('estado_clave', 'con-mora')->count();
    @endphp

    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        Clientes mostrados
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ $totalClientes }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        Al día
                    </div>

                    <div class="fs-3 fw-bold text-success">
                        {{ $totalAlDia }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        Pendientes
                    </div>

                    <div class="fs-3 fw-bold text-warning">
                        {{ $totalPendientes }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">
                        Con mora
                    </div>

                    <div class="fs-3 fw-bold text-danger">
                        {{ $totalConMora }}
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Tabla --}}
    <div class="card shadow-sm">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-table me-1"></i>
                Estado de cuenta por cliente
            </h3>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Recibos pendientes</th>
                            <th class="text-end">Monto pendiente</th>
                            <th class="text-end">Mora</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($filas as $fila)

                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $fila['cliente']->nombre }}
                                    </div>

                                    @if ($fila['cliente']->telefono)
                                        <small class="text-muted">
                                            {{ $fila['cliente']->telefono }}
                                        </small>
                                    @endif
                                </td>

                                <td class="text-center">

                                    @if ($fila['estado_clave'] === 'al-dia')

                                        <span class="badge text-bg-success">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Al día
                                        </span>

                                    @elseif ($fila['estado_clave'] === 'pendiente')

                                        <span class="badge text-bg-warning">
                                            <i class="bi bi-clock me-1"></i>
                                            Pendiente
                                        </span>

                                    @else

                                        <span class="badge text-bg-danger">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            Con mora
                                        </span>

                                    @endif

                                </td>

                                <td class="text-center">
                                    {{ $fila['recibos_pendientes'] }}
                                </td>

                                <td class="text-end">
                                    Q{{ number_format($fila['monto_pendiente'], 2) }}
                                </td>

                                <td class="text-end">
                                    @if ($fila['mora'] > 0)
                                        <span class="text-danger fw-semibold">
                                            Q{{ number_format($fila['mora'], 2) }}
                                        </span>
                                    @else
                                        Q0.00
                                    @endif
                                </td>

                                <td class="text-end">
                                    <strong>
                                        Q{{ number_format($fila['total'], 2) }}
                                    </strong>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="6"
                                    class="text-center py-5"
                                >
                                    <i class="bi bi-info-circle fs-3 d-block mb-2 text-muted"></i>

                                    <span class="text-muted">
                                        No se encontraron clientes con los filtros seleccionados.
                                    </span>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>

            </div>
        </div>
    </div>

@stop