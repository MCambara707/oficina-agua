@extends('adminlte::page')

@section('title', 'Lecturas')

@section('content_header')
    <h1>Lecturas</h1>
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
                action="{{ route('lecturas.index') }}"
                class="mb-3"
            >
                <div
                    class="input-group"
                    style="max-width: 400px;"
                >
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Buscar por número de contador"
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
                href="{{ route('lecturas.create') }}"
                class="btn btn-primary mb-3"
            >
                + Registrar Lectura
            </a>

            <div class="table-responsive">

                <table class="table table-bordered table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Contador</th>
                            <th>Cliente</th>
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
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($lecturas as $lectura)

                            @php
                                /*
                                 * Utilizamos la tarifa asociada al recibo.
                                 * Si no existe recibo, usamos la tarifa
                                 * actual del contador como respaldo.
                                 */
                                $tarifa = $lectura->recibo?->tarifa
                                    ?? $lectura->contador?->tarifa;

                                $consumo = (float) $lectura->consumo_m3;

                                $capacidad = $tarifa
                                    ? (float) $tarifa->capacidad
                                    : 0;

                                $precioBase = $tarifa
                                    ? (float) $tarifa->precio_por_m3
                                    : 0;

                                $precioExceso = $tarifa
                                    ? (float) $tarifa->precio_exceso_m3
                                    : 0;

                                /*
                                 * Cuántos m³ están dentro de la capacidad.
                                 */
                                $consumoNormal = min(
                                    $consumo,
                                    $capacidad
                                );

                                /*
                                 * Cuántos m³ superaron la capacidad.
                                 */
                                $exceso = max(
                                    $consumo - $capacidad,
                                    0
                                );

                                /*
                                 * Dinero correspondiente al consumo normal.
                                 */
                                $montoNormal =
                                    $consumoNormal * $precioBase;

                                /*
                                 * Dinero correspondiente únicamente
                                 * a los m³ excedidos.
                                 */
                                $montoExceso =
                                    $exceso * $precioExceso;

                                /*
                                 * Mostrar m³ sin ceros innecesarios.
                                 *
                                 * 60.000 → 60
                                 * 70.000 → 70
                                 * 70.125 → 70.125
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
                                <td>
                                    {{ $lectura->contador->numero_registro }}
                                </td>

                                <td>
                                    {{ $lectura->contador->cliente->nombre }}
                                </td>

                                <td>
                                    {{ $lectura->periodo->format('m/Y') }}
                                </td>

                                <td>
                                    {{ $formatearM3($lectura->lectura_anterior) }}
                                </td>

                                <td>
                                    {{ $formatearM3($lectura->lectura_actual) }}
                                </td>

                                <td>
                                    <strong>
                                        {{ $formatearM3($consumo) }} m³
                                    </strong>
                                </td>

                                <td>
                                    @if ($tarifa)
                                        {{ $formatearM3($capacidad) }} m³
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                {{-- Exceso --}}
                                <td>
                                    @if ($exceso > 0)
                                        <span class="badge text-bg-danger">
                                            {{ $formatearM3($exceso) }} m³
                                        </span>
                                    @else
                                        <span class="badge text-bg-success">
                                            Sin exceso
                                        </span>
                                    @endif
                                </td>

                                {{-- Monto normal --}}
                                <td>
                                    @if ($tarifa)
                                        Q{{ number_format($montoNormal, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Monto del exceso --}}
                                <td>
                                    @if ($exceso > 0)
                                        <strong class="text-danger">
                                            Q{{ number_format($montoExceso, 2) }}
                                        </strong>
                                    @else
                                        Q0.00
                                    @endif
                                </td>

                                <td>
                                    @if ($lectura->recibo)
                                        {{ $lectura->recibo->numero_recibo }}
                                    @else
                                        <span class="text-muted">
                                            —
                                        </span>
                                    @endif
                                </td>

                                {{-- Total --}}
                                <td>
                                    @if ($lectura->recibo)
                                        <strong>
                                            Q{{ number_format(
                                                (float) $lectura->recibo->monto,
                                                2
                                            ) }}
                                        </strong>
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Estado --}}
                                <td>
                                    @if ($lectura->recibo)

                                        @if ($lectura->recibo->estado === 'PENDIENTE')
                                            <span class="badge text-bg-warning">
                                                PENDIENTE
                                            </span>

                                        @elseif ($lectura->recibo->estado === 'PAGADO')
                                            <span class="badge text-bg-success">
                                                PAGADO
                                            </span>

                                        @else
                                            <span class="badge text-bg-secondary">
                                                {{ $lectura->recibo->estado }}
                                            </span>
                                        @endif

                                    @else
                                        <span class="badge text-bg-secondary">
                                            Sin recibo
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $lectura->usuarioLector->nombre }}
                                </td>

                                <td>
                                    {{ $lectura->fecha_lectura->format('d/m/Y') }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td
                                    colspan="15"
                                    class="text-center"
                                >
                                    No hay lecturas registradas todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>

            {{ $lecturas->links() }}

        </div>
    </div>

@stop