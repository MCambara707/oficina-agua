@extends('adminlte::page')

@section('title', 'Lecturas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">Lecturas</h1>
            <small class="text-muted">
                Registro y consulta de consumo de los contadores.
            </small>
        </div>
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
            <div
                class="d-flex flex-column flex-md-row
                    justify-content-between align-items-md-center"
            >
                <div>
                    <i class="fas fa-check-circle mr-1"></i>
                    {{ session('exito') }}
                </div>

                @if (session('recibo_generado_id'))
                    <div class="mt-2 mt-md-0 mr-md-4">
                        <a
                            href="{{ route(
                                'recibos.imprimir',
                                session('recibo_generado_id')
                            ) }}"
                            class="btn btn-sm btn-success"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <i class="fas fa-print mr-1"></i>
                            Imprimir recibo
                        </a>
                    </div>
                @endif
            </div>

            <button
                type="button"
                class="close"
                data-dismiss="alert"
                aria-label="Cerrar"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif


    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}

            <button
                type="button"
                class="close"
                data-dismiss="alert"
                aria-label="Cerrar"
            >
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif


    <div class="card">

        {{-- =====================================================
             ENCABEZADO DEL MÓDULO
        ====================================================== --}}
        <div class="card-header">

            <div
                class="d-flex flex-column flex-md-row
                       justify-content-between align-items-md-center"
            >

                <div>
                    <h3 class="card-title mb-1">
                        Historial de lecturas
                    </h3>
                </div>

                <div class="mt-2 mt-md-0">

                    <a
                        href="{{ route('lecturas.create') }}"
                        class="btn btn-primary"
                    >
                        <i class="fas fa-plus mr-1"></i>
                        Registrar lectura
                    </a>

                </div>

            </div>

        </div>


        <div class="card-body">

            {{-- =================================================
                 BUSCADOR
            ================================================== --}}
            <form
                method="GET"
                action="{{ route('lecturas.index') }}"
                class="mb-4"
            >

                <div class="row">

                    <div class="col-12 col-md-6 col-lg-5">

                        <div class="input-group">

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Buscar por número de contador"
                                value="{{ $busqueda ?? '' }}"
                                autocomplete="off"
                            >

                            <div class="input-group-append">

                                <button
                                    class="btn btn-secondary"
                                    type="submit"
                                >
                                    <i class="fas fa-search mr-1"></i>
                                    Buscar
                                </button>

                            </div>

                        </div>

                    </div>


                    @if (!empty($busqueda))

                        <div class="col-12 col-md-auto mt-2 mt-md-0">

                            <a
                                href="{{ route('lecturas.index') }}"
                                class="btn btn-outline-secondary"
                            >
                                Limpiar búsqueda
                            </a>

                        </div>

                    @endif

                </div>

            </form>


            {{-- =================================================
                 TABLA
            ================================================== --}}
            <div class="table-responsive">

                <table
                    class="table table-bordered table-striped
                           table-hover align-middle"
                >

                    <thead>

                        <tr>

                            <th>Contador</th>

                            <th>Cliente</th>

                            <th>Servicio</th>

                            <th>Período</th>

                            <th>Lectura anterior</th>

                            <th>Lectura actual</th>

                            <th>Consumo</th>

                            <th>Capacidad</th>

                            <th>Exceso</th>

                            <th>Monto normal</th>

                            <th>Monto exceso</th>

                            <th>N.° recibo</th>

                            <th>Total recibo</th>

                            <th>Estado</th>

                            <th>Lector</th>

                            <th>Fecha</th>

                            <th
                                class="text-center"
                                style="min-width: 145px;"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($lecturas as $lectura)

                            @php

                                /*
                                 * =================================================
                                 * RELACIONES PRINCIPALES
                                 * =================================================
                                 */

                                $contador = $lectura->contador;

                                $cliente = $contador?->cliente;

                                $servicio = $contador?->servicio;

                                $recibo = $lectura->recibo;


                                /*
                                 * La tarifa histórica correcta es la asociada
                                 * al recibo.
                                 *
                                 * Si excepcionalmente no existe recibo,
                                 * utilizamos la tarifa actual del contador
                                 * únicamente como respaldo visual.
                                 */
                                $tarifa = $recibo?->tarifa
                                    ?? $contador?->tarifa;


                                /*
                                 * =================================================
                                 * CÁLCULOS DEL CONSUMO
                                 * =================================================
                                 */

                                $consumo = (float) $lectura->consumo_m3;

                                $capacidad = $tarifa
                                    ? (float) $tarifa->capacidad
                                    : 0.00;

                                $precioBase = $tarifa
                                    ? (float) $tarifa->precio_por_m3
                                    : 0.00;

                                $precioExceso = $tarifa
                                    ? (float) $tarifa->precio_exceso_m3
                                    : 0.00;


                                /*
                                 * Consumo que se encuentra dentro de la
                                 * capacidad contratada/configurada.
                                 */
                                $consumoNormal = min(
                                    $consumo,
                                    $capacidad
                                );


                                /*
                                 * Consumo que supera la capacidad.
                                 */
                                $exceso = max(
                                    $consumo - $capacidad,
                                    0
                                );


                                /*
                                 * Monto correspondiente al consumo normal.
                                 */
                                $montoNormal =
                                    $consumoNormal * $precioBase;


                                /*
                                 * Monto correspondiente exclusivamente
                                 * al consumo excedido.
                                 */
                                $montoExceso =
                                    $exceso * $precioExceso;


                                /*
                                 * =================================================
                                 * FORMATO DE METROS CÚBICOS
                                 * =================================================
                                 *
                                 * Ejemplos:
                                 *
                                 * 60.000  → 60
                                 * 70.500  → 70.5
                                 * 70.125  → 70.125
                                 */
                                $formatearM3 = function ($valor) {

                                    return rtrim(
                                        rtrim(
                                            number_format(
                                                (float) $valor,
                                                3,
                                                '.',
                                                ''
                                            ),
                                            '0'
                                        ),
                                        '.'
                                    );
                                };

                            @endphp


                            <tr>

                                {{-- CONTADOR --}}
                                <td>

                                    <strong>
                                        {{ $contador?->numero_registro ?? '—' }}
                                    </strong>

                                </td>


                                {{-- CLIENTE --}}
                                <td>

                                    {{ $cliente?->nombre ?? 'Cliente no disponible' }}

                                </td>


                                {{-- SERVICIO --}}
                                <td>

                                    @if ($servicio)

                                        <span class="badge badge-info">
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

                                    {{ $lectura->periodo
                                        ? $lectura->periodo->format('m/Y')
                                        : '—'
                                    }}

                                </td>


                                {{-- LECTURA ANTERIOR --}}
                                <td>

                                    {{ $formatearM3(
                                        $lectura->lectura_anterior
                                    ) }}

                                </td>


                                {{-- LECTURA ACTUAL --}}
                                <td>

                                    {{ $formatearM3(
                                        $lectura->lectura_actual
                                    ) }}

                                </td>


                                {{-- CONSUMO --}}
                                <td>

                                    <strong>
                                        {{ $formatearM3($consumo) }} m³
                                    </strong>

                                </td>


                                {{-- CAPACIDAD --}}
                                <td>

                                    @if ($tarifa)

                                        {{ $formatearM3($capacidad) }} m³

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- EXCESO --}}
                                <td>

                                    @if ($tarifa && $exceso > 0)

                                        <span class="badge badge-danger">
                                            {{ $formatearM3($exceso) }} m³
                                        </span>

                                    @elseif ($tarifa)

                                        <span class="badge badge-success">
                                            Sin exceso
                                        </span>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- MONTO NORMAL --}}
                                <td>

                                    @if ($tarifa)

                                        Q{{ number_format(
                                            $montoNormal,
                                            2
                                        ) }}

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- MONTO EXCESO --}}
                                <td>

                                    @if ($tarifa && $exceso > 0)

                                        <strong class="text-danger">
                                            Q{{ number_format(
                                                $montoExceso,
                                                2
                                            ) }}
                                        </strong>

                                    @elseif ($tarifa)

                                        Q0.00

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- NÚMERO DE RECIBO --}}
                                <td>

                                    @if ($recibo)

                                        <strong>
                                            {{ $recibo->numero_recibo }}
                                        </strong>

                                    @else

                                        <span class="text-muted">
                                            Sin recibo
                                        </span>

                                    @endif

                                </td>


                                {{-- TOTAL DEL RECIBO --}}
                                <td>

                                    @if ($recibo)

                                        <strong>
                                            Q{{ number_format(
                                                (float) $recibo->monto,
                                                2
                                            ) }}
                                        </strong>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- ESTADO --}}
                                <td>

                                    @if ($recibo)

                                        @if ($recibo->estado === 'PAGADO')

                                            <span class="badge badge-success">
                                                PAGADO
                                            </span>


                                        @elseif ($recibo->estado === 'ANULADO')

                                            <span class="badge badge-secondary">
                                                ANULADO
                                            </span>


                                        @elseif (
                                            $recibo->estado === 'PENDIENTE'
                                            && $recibo->estaAtrasado()
                                        )

                                            <span class="badge badge-danger">
                                                CON MORA
                                            </span>


                                        @elseif ($recibo->estado === 'PENDIENTE')

                                            <span class="badge badge-warning">
                                                PENDIENTE
                                            </span>


                                        @else

                                            <span class="badge badge-secondary">
                                                {{ $recibo->estado }}
                                            </span>

                                        @endif

                                    @else

                                        <span class="badge badge-secondary">
                                            Sin recibo
                                        </span>

                                    @endif

                                </td>


                                {{-- LECTOR --}}
                                <td>

                                    {{ $lectura->usuarioLector?->nombre
                                        ?? 'No registrado'
                                    }}

                                </td>


                                {{-- FECHA --}}
                                <td>

                                    {{ $lectura->fecha_lectura
                                        ? $lectura->fecha_lectura->format('d/m/Y')
                                        : '—'
                                    }}

                                </td>


                                {{-- ACCIONES --}}
                                <td class="text-center">

                                    @if ($recibo)

                                        <a
                                            href="{{ route(
                                                'recibos.imprimir',
                                                $recibo
                                            ) }}"
                                            class="btn btn-sm btn-primary"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            title="Ver e imprimir recibo"
                                        >
                                            <i class="fas fa-print mr-1"></i>
                                            Imprimir
                                        </a>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="17"
                                    class="text-center py-4 text-muted"
                                >

                                    @if (!empty($busqueda))

                                        No se encontraron lecturas para
                                        el contador buscado.

                                    @else

                                        No hay lecturas registradas todavía.

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
            @if ($lecturas->hasPages())

                <div class="mt-3">
                    {{ $lecturas->withQueryString()->links() }}
                </div>

            @endif

        </div>

    </div>

@stop