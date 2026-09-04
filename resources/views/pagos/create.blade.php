@extends('adminlte::page')

@section('title', 'Registrar pago')

@section('content_header')
    <h1>Registrar pago</h1>
@stop

@section('content')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">Recibo N.° {{ $recibo->numero_recibo }}</h3>
        </div>

        <div class="card-body">

            @if ($recibo->estaAtrasado())
                <div class="alert alert-warning">
                    <strong>Recibo atrasado</strong> — {{ $recibo->diasAtraso() }} día(s) desde el vencimiento.
                    Se acumuló una mora de <strong>Q{{ number_format($recibo->montoMora(), 2) }}</strong>.
                </div>
            @endif

            <dl class="row">
                <dt class="col-sm-3">Cliente</dt>
                <dd class="col-sm-9">{{ $recibo->lectura->contador->cliente->nombre ?? '—' }}</dd>

                <dt class="col-sm-3">Fecha de emisión</dt>
                <dd class="col-sm-9">{{ $recibo->fecha_emision->format('d/m/Y') }}</dd>

                <dt class="col-sm-3">Monto original</dt>
                <dd class="col-sm-9">Q{{ number_format($recibo->monto, 2) }}</dd>

                @if ($recibo->estaAtrasado())
                    <dt class="col-sm-3">Mora acumulada</dt>
                    <dd class="col-sm-9">Q{{ number_format($recibo->montoMora(), 2) }}</dd>

                    <dt class="col-sm-3">Total a pagar</dt>
                    <dd class="col-sm-9"><strong>Q{{ number_format($recibo->montoConMora(), 2) }}</strong></dd>
                @endif
            </dl>

            <hr>

            <form action="{{ route('pagos.store') }}" method="POST">
                @csrf
                <input type="hidden" name="recibo_id" value="{{ $recibo->id }}">

                <div class="form-group">
                    <label for="metodo_pago_id">Método de pago</label>
                    <select name="metodo_pago_id" id="metodo_pago_id" class="form-control" required>
                        <option value="">Seleccione un método</option>
                        @foreach ($metodosPago as $metodo)
                            <option value="{{ $metodo->id }}">{{ $metodo->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="monto">Monto a pagar</label>
                    <input type="number" step="0.01" name="monto" id="monto"
                           class="form-control" value="{{ $recibo->montoConMora() }}" required>
                </div>

                <div class="form-group">
                    <label for="referencia">Referencia (opcional)</label>
                    <input type="text" name="referencia" id="referencia" class="form-control">
                </div>

                <button type="submit" class="btn btn-primary">Confirmar pago</button>
                <a href="{{ route('pagos.index') }}" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>

@stop