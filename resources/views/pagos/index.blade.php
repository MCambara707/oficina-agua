@extends('adminlte::page')

@section('title', 'Pagos')

@section('content_header')
    <div>
        <h1 class="mb-0">Pagos</h1>
        <small class="text-muted">
            Consulta y registro de recibos pendientes de pago.
        </small>
    </div>
@stop


@section('content')

    {{-- =========================================================
         MENSAJES DEL SISTEMA
    ========================================================== --}}

    @if (session('exito'))
        <div
            class="alert alert-success alert-dismissible fade show"
            role="alert"
        >
            <i class="fas fa-check-circle me-1"></i>
            {{ session('exito') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            >
            </button>
        </div>
    @endif


    @if (session('info'))
        <div
            class="alert alert-info alert-dismissible fade show"
            role="alert"
        >
            <i class="fas fa-info-circle me-1"></i>
            {{ session('info') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            >
            </button>
        </div>
    @endif


    @if (session('error'))
        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >
            <i class="fas fa-exclamation-circle me-1"></i>
            {{ session('error') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            >
            </button>
        </div>
    @endif


    <div class="card">

        {{-- =====================================================
             ENCABEZADO
        ====================================================== --}}
        <div class="card-header">

            <div
                class="d-flex flex-column flex-md-row
                       flex-wrap gap-2 justify-content-between align-items-md-center"
            >
                <div>
                    <h3 class="card-title mb-1">
                        Recibos pendientes
                    </h3>
                </div>

                <div class="mt-2 mt-md-0">
                    <span class="badge text-bg-warning p-2">
                        {{ $recibos->total() }} pendiente(s)
                    </span>
                </div>
            </div>

        </div>


        <div class="card-body">

            {{-- =================================================
                 BUSCADOR
            ================================================== --}}
            <form
                method="GET"
                action="{{ route('pagos.index') }}"
                class="mb-4"
            >

                <div class="row">

                    <div class="col-12 col-lg-7">

                        <label for="q">
                            Buscar recibo
                        </label>

                        <div class="d-flex flex-column flex-sm-row gap-2">

                            <input
                                type="text"
                                name="q"
                                id="q"
                                class="form-control"
                                value="{{ $busqueda ?? '' }}"
                                placeholder="N.° recibo, nombre, DPI o contador"
                                autocomplete="off"
                            >

                            <button
                                type="submit"
                                class="btn btn-primary text-nowrap"
                            >
                                <i class="fas fa-search me-1"></i>
                                Buscar
                            </button>

                        </div>

                    </div>


                    @if (!empty($busqueda))

                        <div
                            class="col-12 col-lg-auto
                                   d-flex align-items-end mt-2 mt-lg-0"
                        >

                            
                                href="{{ route('pagos.index') }}"
                                class="btn btn-outline-secondary"
                            >
                                <i class="fas fa-times me-1"></i>
                                Limpiar
                            </a>

                        </div>

                    @endif

                </div>

            </form>


            {{-- =================================================
                 TABLA DE RECIBOS
            ================================================== --}}
            <div class="table-responsive">

                <table
                    class="table table-bordered table-striped
                           table-hover align-middle"
                >

                    <thead>

                        <tr>

                            <th>N.° recibo</th>

                            <th>Cliente</th>

                            <th>DPI</th>

                            <th>Contador</th>

                            <th>Servicio</th>

                            <th>Período</th>

                            <th>Fecha emisión</th>

                            <th>Monto</th>

                            <th>Atraso</th>

                            <th>Mora</th>

                            <th>Total a pagar</th>

                            <th
                                class="text-center"
                                style="min-width: 210px;"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($recibos as $recibo)

                            @php
                                $lectura = $recibo->lectura;
                                $contador = $lectura?->contador;
                                $cliente = $contador?->cliente;
                                $servicio = $contador?->servicio;

                                $estaAtrasado =
                                    $recibo->estaAtrasado();

                                $diasAtraso =
                                    $recibo->diasAtraso();

                                $mora =
                                    (float) $recibo->montoMora();

                                $totalPagar =
                                    (float) $recibo->montoConMora();
                            @endphp


                            <tr>

                                {{-- NÚMERO RECIBO --}}
                                <td>

                                    <strong>
                                        {{ $recibo->numero_recibo }}
                                    </strong>

                                </td>


                                {{-- CLIENTE --}}
                                <td>

                                    {{ $cliente?->nombre ?? '—' }}

                                </td>


                                {{-- DPI --}}
                                <td>

                                    {{ $cliente?->dpi ?? 'No registrado' }}

                                </td>


                                {{-- CONTADOR --}}
                                <td>

                                    {{ $contador?->numero_registro ?? '—' }}

                                </td>


                                {{-- SERVICIO --}}
                                <td>

                                    @if ($servicio)

                                        <span class="badge text-bg-info">
                                            {{ $servicio->nombre }}
                                        </span>

                                    @else

                                        <span class="text-muted">
                                            No asignado
                                        </span>

                                    @endif

                                </td>


                                {{-- PERÍODO --}}
                                <td>

                                    {{ $lectura?->periodo
                                        ? $lectura->periodo->format('m/Y')
                                        : '—'
                                    }}

                                </td>


                                {{-- FECHA EMISIÓN --}}
                                <td>

                                    {{ $recibo->fecha_emision
                                        ? $recibo->fecha_emision->format('d/m/Y')
                                        : '—'
                                    }}

                                </td>


                                {{-- MONTO ORIGINAL --}}
                                <td>

                                    Q{{ number_format(
                                        (float) $recibo->monto,
                                        2
                                    ) }}

                                </td>


                                {{-- ATRASO --}}
                                <td>

                                    @if ($estaAtrasado)

                                        <span class="badge text-bg-danger">
                                            {{ $diasAtraso }} día(s)
                                        </span>

                                    @else

                                        <span class="badge text-bg-success">
                                            Al día
                                        </span>

                                    @endif

                                </td>


                                {{-- MORA --}}
                                <td>

                                    @if ($mora > 0)

                                        <strong class="text-danger">
                                            Q{{ number_format(
                                                $mora,
                                                2
                                            ) }}
                                        </strong>

                                    @else

                                        Q0.00

                                    @endif

                                </td>


                                {{-- TOTAL --}}
                                <td>

                                    <strong>
                                        Q{{ number_format(
                                            $totalPagar,
                                            2
                                        ) }}
                                    </strong>

                                </td>


                                {{-- ACCIONES --}}
                                <td class="text-center">

                                    <div
                                        class="d-flex flex-column
                                               flex-sm-row
                                               flex-wrap gap-2 justify-content-center"
                                    >

                                        
                                            href="{{ route(
                                                'recibos.imprimir',
                                                $recibo
                                            ) }}"
                                            class="btn btn-sm btn-outline-primary"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Ver o imprimir recibo"
                                        >
                                            <i class="fas fa-print me-1"></i>
                                            Recibo
                                        </a>


                                        
                                            href="{{ route(
                                                'pagos.create',
                                                $recibo
                                            ) }}"
                                            class="btn btn-sm btn-success"
                                            title="Registrar pago"
                                        >
                                            <i class="fas fa-cash-register me-1"></i>
                                            Pagar
                                        </a>

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="12"
                                    class="text-center py-4 text-muted"
                                >

                                    @if (!empty($busqueda))

                                        No se encontraron recibos pendientes
                                        que coincidan con la búsqueda.

                                    @else

                                        No hay recibos pendientes de pago.

                                    @endif

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            {{-- =================================================
                 PAGINACIÓN
            ================================================== --}}
            @if ($recibos->hasPages())

                <div class="mt-3">

                    {{ $recibos->withQueryString()->links('pagination::bootstrap-5') }}

                </div>

            @endif

        </div>

    </div>

@stop