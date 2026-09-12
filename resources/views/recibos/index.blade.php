@extends('adminlte::page')

@section('title', 'Historial de recibos')

@section('content_header')
    <h1>Historial de recibos</h1>
@stop

@section('content')
    @foreach (['exito' => 'success', 'error' => 'danger'] as $mensaje => $color)
        @if (session($mensaje))
            <div class="alert alert-{{ $color }}">{{ session($mensaje) }}</div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('recibos.index') }}" method="GET" class="mb-4">
                <div class="row align-items-end g-3">
                    <div class="col-12 col-xl-5">
                        <label for="q">Recibo, DPI, nombre o contador</label>
                        <input id="q" name="q" class="form-control" maxlength="150"
                               value="{{ $busqueda }}" placeholder="Buscar en todos los recibos">
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="periodo">Período de lectura</label>
                        <input type="month" id="periodo" name="periodo" class="form-control" value="{{ $periodo }}">
                    </div>
                    <div class="col-12 col-sm-6 col-xl-2">
                        <label for="estado">Estado</label>
                        <select id="estado" name="estado" class="form-control">
                            <option value="">Todos</option>
                            @foreach (['PENDIENTE' => 'Pendiente', 'PAGADO' => 'Pagado', 'ANULADO' => 'Anulado'] as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected($estado === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-xl-3 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit">Buscar</button>
                        <a href="{{ route('recibos.index') }}" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
            </form>

            <p class="text-muted">{{ $recibos->total() }} recibo(s) encontrado(s).</p>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>Recibo</th><th>Cliente / DPI</th><th>Contador</th><th>Período</th>
                            <th>Emisión</th><th>Estado</th><th>Monto original</th><th>Mora actual</th>
                            <th>Saldo pendiente</th><th>Total pagado</th><th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recibos as $recibo)
                            @php
                                $contador = $recibo->lectura->contador;
                                $pendiente = $recibo->estado === 'PENDIENTE';
                                $color = match ($recibo->estado) {
                                    'PAGADO' => 'success',
                                    'ANULADO' => 'secondary',
                                    default => 'warning',
                                };
                            @endphp
                            <tr>
                                <td>{{ $recibo->numero_recibo }}</td>
                                <td>{{ $contador->cliente->nombre }}<br><small>{{ $contador->cliente->dpi }}</small></td>
                                <td>{{ $contador->numero_registro }}</td>
                                <td>{{ $recibo->lectura->periodo->format('m/Y') }}</td>
                                <td>{{ $recibo->fecha_emision->format('d/m/Y') }}</td>
                                <td><span class="badge text-bg-{{ $color }}">{{ $recibo->estado }}</span></td>
                                <td>Q{{ number_format((float) $recibo->monto, 2) }}</td>
                                <td>Q{{ number_format($recibo->montoMora(), 2) }}</td>
                                <td>Q{{ number_format($pendiente ? $recibo->montoConMora() : 0, 2) }}</td>
                                <td>{{ $recibo->pago ? 'Q' . number_format((float) $recibo->pago->monto, 2) : '—' }}</td>
                                <td>
                                    <a href="{{ route('recibos.imprimir', $recibo) }}"
                                       class="btn btn-sm btn-outline-primary mb-1" target="_blank" rel="noopener noreferrer">
                                        {{ $recibo->estado === 'PAGADO' && $recibo->pago ? 'Comprobante' : 'Imprimir recibo' }}
                                    </a>
                                    @if ($pendiente && !$recibo->pago && $recibo->montoConMora() > 0)
                                        <a href="{{ route('pagos.create', $recibo) }}" class="btn btn-sm btn-success mb-1">Pagar</a>
                                    @elseif ($pendiente && !$recibo->pago)
                                        <small class="d-block text-muted">Sin importe para cobrar</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="text-center text-muted py-4">No se encontraron recibos con estos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $recibos->links('pagination::bootstrap-5') }}
        </div>
    </div>
@stop