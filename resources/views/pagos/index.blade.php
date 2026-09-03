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
                        <th>Estado</th>
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
                                <span class="badge badge-warning">{{ $recibo->estado }}</span>
                            </td>
                            <td>
                                <a href="{{ route('pagos.create', $recibo) }}"
                                   class="btn btn-sm btn-primary">Registrar pago</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
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