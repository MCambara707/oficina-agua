@extends('adminlte::page')

@section('title', 'Lecturas')

@section('content_header')
    <h1>Lecturas</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">{{ session('exito') }}</div>
    @endif

    @if (session('advertencia'))
        <div class="alert alert-warning">{{ session('advertencia') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <form method="GET" action="{{ route('lecturas.index') }}" class="d-flex" style="max-width: 400px;">
                    <input type="text" name="q" class="form-control me-2"
                           placeholder="Buscar por número de contador" value="{{ $busqueda }}">
                    <button class="btn btn-secondary text-nowrap" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <a href="{{ route('lecturas.create') }}" class="btn btn-primary text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>Registrar Lectura
                </a>
            </div>
        </div>

        <div class="card-body p-0">

            @if ($lecturas->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-droplet" style="font-size: 2.5rem;"></i>
                    <p class="mt-2 mb-3">No hay lecturas registradas todavía.</p>
                    <a href="{{ route('lecturas.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>Registrar la primera lectura
                    </a>
                </div>
            @else

                {{-- Escritorio / tablet: tabla completa, con scroll horizontal si hace falta --}}
                <div class="d-none d-md-block table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Contador</th>
                                <th>Cliente</th>
                                <th>Período</th>
                                <th class="text-end">Anterior</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end">Consumo</th>
                                <th>Tarifa vigente</th>
                                <th class="text-end">Monto*</th>
                                <th>Lector</th>
                                <th>Fecha</th>
                                <th>Recibo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($lecturas as $lectura)
                                <tr>
                                    <td>{{ $lectura->contador->numero_registro }}</td>
                                    <td>{{ $lectura->contador->cliente->nombre }}</td>
                                    <td>{{ $lectura->periodo->format('m/Y') }}</td>
                                    <td class="text-end">{{ number_format($lectura->lectura_anterior, 3) }}</td>
                                    <td class="text-end">{{ number_format($lectura->lectura_actual, 3) }}</td>
                                    <td class="text-end">{{ number_format($lectura->consumo_m3, 3) }} m³</td>
                                    <td>
                                        @if ($lectura->tarifa_vigente)
                                            <span class="badge text-bg-info">{{ $lectura->tarifa_vigente->tipo }}</span>
                                            <div class="text-muted small mt-1">
                                                Q{{ number_format($lectura->tarifa_vigente->precio_por_m3, 2) }}/m³
                                            </div>
                                        @else
                                            <span class="badge text-bg-secondary">Sin tarifa vigente</span>
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ $lectura->monto_estimado !== null ? 'Q'.number_format($lectura->monto_estimado, 2) : '—' }}
                                    </td>
                                    <td>{{ $lectura->usuarioLector->nombre }}</td>
                                    <td>{{ $lectura->fecha_lectura->format('d/m/Y') }}</td>
                                    <td>
                                        @if ($lectura->recibo)
                                            <a href="{{ route('recibos.imprimir', $lectura->recibo) }}"
                                               class="btn btn-sm btn-outline-primary text-nowrap" target="_blank">
                                                <i class="bi bi-receipt me-1"></i>{{ $lectura->recibo->numero_recibo }}
                                            </a>
                                        @else
                                            <span class="badge text-bg-secondary">Sin recibo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Móvil: una tarjeta por lectura en vez de una tabla apretada --}}
                <div class="d-md-none">
                    @foreach ($lecturas as $lectura)
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">{{ $lectura->contador->numero_registro }}</div>
                                    <div class="text-muted small">{{ $lectura->contador->cliente->nombre }}</div>
                                </div>
                                <span class="badge text-bg-light text-dark border">
                                    {{ $lectura->periodo->format('m/Y') }}
                                </span>
                            </div>

                            <div class="row gy-1 mt-2 small">
                                <div class="col-6 text-muted">Anterior → Actual</div>
                                <div class="col-6 text-end">
                                    {{ number_format($lectura->lectura_anterior, 3) }} → {{ number_format($lectura->lectura_actual, 3) }}
                                </div>

                                <div class="col-6 text-muted">Consumo</div>
                                <div class="col-6 text-end">{{ number_format($lectura->consumo_m3, 3) }} m³</div>

                                <div class="col-6 text-muted">Tarifa</div>
                                <div class="col-6 text-end">
                                    @if ($lectura->tarifa_vigente)
                                        <span class="badge text-bg-info">{{ $lectura->tarifa_vigente->tipo }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">Sin tarifa</span>
                                    @endif
                                </div>

                                <div class="col-6 text-muted">Monto estimado*</div>
                                <div class="col-6 text-end fw-semibold">
                                    {{ $lectura->monto_estimado !== null ? 'Q'.number_format($lectura->monto_estimado, 2) : '—' }}
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="text-muted small">
                                    {{ $lectura->usuarioLector->nombre }} · {{ $lectura->fecha_lectura->format('d/m/Y') }}
                                </div>

                                @if ($lectura->recibo)
                                    <a href="{{ route('recibos.imprimir', $lectura->recibo) }}"
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-receipt me-1"></i>Recibo
                                    </a>
                                @else
                                    <span class="badge text-bg-secondary">Sin recibo</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            @endif
        </div>

        @if (! $lecturas->isEmpty())
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        * Monto estimado = consumo × precio por m³ de la tarifa vigente. No incluye exceso sobre la
                        capacidad contratada ni mora.
                    </small>
                    {{ $lecturas->links() }}
                </div>
            </div>
        @endif
    </div>

@stop