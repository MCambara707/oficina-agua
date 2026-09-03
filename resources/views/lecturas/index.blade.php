@extends('adminlte::page')

@section('title', 'Lecturas')

@section('content_header')
    <h1>Lecturas</h1>
@stop

@section('content')

    @if (session('exito'))
        <div class="alert alert-success">{{ session('exito') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body">

            <form method="GET" action="{{ route('lecturas.index') }}" class="mb-3">
                <div class="input-group" style="max-width: 400px;">
                    <input type="text" name="q" class="form-control"
                           placeholder="Buscar por número de contador" value="{{ $busqueda }}">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="submit">Buscar</button>
                    </div>
                </div>
            </form>

            <a href="{{ route('lecturas.create') }}" class="btn btn-primary mb-3">+ Registrar Lectura</a>

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Contador</th>
                        <th>Cliente</th>
                        <th>Período</th>
                        <th>Lectura anterior</th>
                        <th>Lectura actual</th>
                        <th>Consumo (m³)</th>
                        <th>Lector</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lecturas as $lectura)
                        <tr>
                            <td>{{ $lectura->contador->numero_registro }}</td>
                            <td>{{ $lectura->contador->cliente->nombre }}</td>
                            <td>{{ $lectura->periodo->format('m/Y') }}</td>
                            <td>{{ number_format($lectura->lectura_anterior, 3) }}</td>
                            <td>{{ number_format($lectura->lectura_actual, 3) }}</td>
                            <td>{{ number_format($lectura->consumo_m3, 3) }}</td>
                            <td>{{ $lectura->usuarioLector->nombre }}</td>
                            <td>{{ $lectura->fecha_lectura->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay lecturas registradas todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $lecturas->links() }}

        </div>
    </div>

@stop
