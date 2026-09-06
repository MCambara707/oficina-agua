@extends('adminlte::page')

@section('title', 'Pagos')

@section('content_header')
    <h1>Pagos pendientes</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">{{ session('exito') }}</div>
    @endif

    @if (session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Recibos pendientes de pago</h3>
        </div>

        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>N.° recibo</th>
                        <th>Cliente</th>
                        <th>Fecha emisión</th>
                        <th>Monto</th>
                        <th>Atraso</th>
                        <th>Mora</th>
                        <th>Total con mora</th>
                        <th style="width: 160px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recibos as $recibo)
                        <tr>
                            <td>{{ $recibo->numero_recibo }}</td>
                            <td>{{ $recibo->lectura->contador->cliente->nombre ?? '—' }}</td>
                            <td>{{ $recibo->fecha_emision->format('d/m/Y') }}</td>
                            <td>Q{{ number_format($recibo->monto, 2) }}</td>
                            <td>
                                @if ($recibo->estaAtrasado())
                                    <span class="badge badge-danger">
                                        {{ $recibo->diasAtraso() }} día(s)
                                    </span>
                                @else
                                    <span class="badge badge-success">Al día</span>
                                @endif
                            </td>
                            <td>
                                @if ($recibo->estaAtrasado())
                                    Q{{ number_format($recibo->montoMora(), 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <strong>Q{{ number_format($recibo->montoConMora(), 2) }}</strong>
                            </td>
                            <td>
                                <a href="{{ route('pagos.create', $recibo) }}"
                                   class="btn btn-sm btn-primary">Registrar pago</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                No hay recibos pendientes de pago.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $recibos->links() }}
        </div>
    </div>

@stop