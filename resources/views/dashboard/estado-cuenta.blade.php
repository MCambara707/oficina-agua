@extends('adminlte::page')

@section('title', 'Estado de cuenta')

@section('content_header')

    <div>
        <h1 class="mb-0">
            Estado de cuenta
        </h1>

        <small class="text-muted">
            Consulta de saldos, mora y situación de pago de los clientes.
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
            <i class="fas fa-check-circle mr-1"></i>

            {{ session('exito') }}

            <button
                type="button"
                class="close"
                data-dismiss="alert"
                aria-label="Cerrar"
            >
                <span aria-hidden="true">
                    &times;
                </span>
            </button>

        </div>

    @endif


    @if (session('error'))

        <div
            class="alert alert-danger alert-dismissible fade show"
            role="alert"
        >
            <i class="fas fa-exclamation-circle mr-1"></i>

            {{ session('error') }}

            <button
                type="button"
                class="close"
                data-dismiss="alert"
                aria-label="Cerrar"
            >
                <span aria-hidden="true">
                    &times;
                </span>
            </button>

        </div>

    @endif


    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <div class="card mb-4">

        <div class="card-header">

            <h3 class="card-title mb-0">

                <i class="fas fa-filter mr-1"></i>

                Filtros

            </h3>

        </div>


        <div class="card-body">

            <form
                method="GET"
                action="{{ route('dashboard.estado-cuenta') }}"
            >

                <div class="row">

                    {{-- BÚSQUEDA --}}
                    <div class="col-12 col-md-6">

                        <div class="form-group">

                            <label for="busqueda">
                                Buscar cliente
                            </label>

                            <input
                                type="text"
                                name="busqueda"
                                id="busqueda"
                                class="form-control"
                                value="{{ $busqueda }}"
                                placeholder="Nombre, DPI o número de contador"
                                autocomplete="off"
                            >

                        </div>

                    </div>


                    {{-- ESTADO --}}
                    <div class="col-12 col-md-4">

                        <div class="form-group">

                            <label for="estado">
                                Estado de cuenta
                            </label>

                            <select
                                name="estado"
                                id="estado"
                                class="form-control"
                            >

                                <option value="">
                                    Todos
                                </option>

                                <option
                                    value="al-dia"
                                    @selected($estado === 'al-dia')
                                >
                                    Al día
                                </option>

                                <option
                                    value="pendiente"
                                    @selected($estado === 'pendiente')
                                >
                                    Pendiente
                                </option>

                                <option
                                    value="con-mora"
                                    @selected($estado === 'con-mora')
                                >
                                    Con mora
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- BOTÓN FILTRAR --}}
                    <div
                        class="col-12 col-md-2
                               d-flex align-items-end"
                    >

                        <div class="form-group w-100">

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                <i class="fas fa-search mr-1"></i>

                                Buscar
                            </button>

                        </div>

                    </div>

                </div>


                @if ($busqueda !== '' || $estado !== '')

                    <div class="mt-1">

                        <a
                            href="{{ route('dashboard.estado-cuenta') }}"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            <i class="fas fa-times mr-1"></i>

                            Limpiar filtros
                        </a>

                    </div>

                @endif

            </form>

        </div>

    </div>


    {{-- =========================================================
         RESUMEN GENERAL
    ========================================================== --}}

    <div class="row">

        {{-- CLIENTES --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div class="small-box bg-light">

                <div class="inner">

                    <h3>
                        {{ $resumenGeneral['clientes'] }}
                    </h3>

                    <p>
                        Clientes mostrados
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>

            </div>

        </div>


        {{-- AL DÍA --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div class="small-box bg-light">

                <div class="inner">

                    <h3 class="text-success">
                        {{ $resumenGeneral['al_dia'] }}
                    </h3>

                    <p>
                        Al día
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>

            </div>

        </div>


        {{-- PENDIENTES --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div class="small-box bg-light">

                <div class="inner">

                    <h3 class="text-warning">
                        {{ $resumenGeneral['pendientes'] }}
                    </h3>

                    <p>
                        Pendientes
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>

            </div>

        </div>


        {{-- CON MORA --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div class="small-box bg-light">

                <div class="inner">

                    <h3 class="text-danger">
                        {{ $resumenGeneral['con_mora'] }}
                    </h3>

                    <p>
                        Con mora
                    </p>

                </div>

                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         SALDO GENERAL
    ========================================================== --}}

    <div class="card mb-4">

        <div
            class="card-body
                   d-flex flex-column flex-md-row
                   justify-content-between align-items-md-center"
        >

            <div>

                <small class="text-muted">
                    Saldo pendiente de los clientes mostrados
                </small>

                <div
                    class="font-weight-bold text-primary"
                    style="font-size: 1.8rem;"
                >
                    Q{{ number_format(
                        $resumenGeneral['saldo_total'],
                        2
                    ) }}
                </div>

            </div>

            <div class="mt-3 mt-md-0 text-muted">

                <i class="fas fa-info-circle mr-1"></i>

                Incluye mora vigente cuando corresponde.

            </div>

        </div>

    </div>


    {{-- =========================================================
         ESTADO DE CUENTA POR CLIENTE
    ========================================================== --}}

    <div class="card">

        <div class="card-header">

            <h3 class="card-title mb-0">

                <i class="fas fa-file-invoice-dollar mr-1"></i>

                Estado de cuenta por cliente

            </h3>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-bordered
                           table-striped table-hover
                           align-middle mb-0"
                >

                    <thead>

                        <tr>

                            <th>Cliente</th>

                            <th>DPI</th>

                            <th>Contadores</th>

                            <th>Servicios</th>

                            <th class="text-center">
                                Estado
                            </th>

                            <th class="text-center">
                                Pendientes
                            </th>

                            <th class="text-center">
                                Pagados
                            </th>

                            <th class="text-right">
                                Monto pendiente
                            </th>

                            <th class="text-right">
                                Mora
                            </th>

                            <th class="text-right">
                                Total
                            </th>

                            <th
                                class="text-center"
                                style="min-width: 160px;"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($filas as $fila)

                            @php

                                $cliente = $fila['cliente'];

                                $contadores = $cliente->contadores;

                                $servicios = $fila['servicios'];

                                $ultimoRecibo =
                                    $fila['ultimo_recibo'];

                            @endphp


                            <tr>

                                {{-- CLIENTE --}}
                                <td>

                                    <strong>
                                        {{ $cliente->nombre }}
                                    </strong>

                                    @if ($cliente->telefono)

                                        <br>

                                        <small class="text-muted">

                                            <i class="fas fa-phone mr-1"></i>

                                            {{ $cliente->telefono }}

                                        </small>

                                    @endif

                                </td>


                                {{-- DPI --}}
                                <td>

                                    {{ $cliente->dpi
                                        ?? 'No registrado'
                                    }}

                                </td>


                                {{-- CONTADORES --}}
                                <td>

                                    @if ($contadores->isNotEmpty())

                                        @foreach ($contadores as $contador)

                                            <div class="mb-1">

                                                <strong>
                                                    {{ $contador->numero_registro }}
                                                </strong>

                                                @if ($contador->activo)

                                                    <span
                                                        class="badge badge-success ml-1"
                                                    >
                                                        Activo
                                                    </span>

                                                @else

                                                    <span
                                                        class="badge badge-secondary ml-1"
                                                    >
                                                        Inactivo
                                                    </span>

                                                @endif

                                            </div>

                                        @endforeach

                                    @else

                                        <span class="text-muted">
                                            Sin contador
                                        </span>

                                    @endif

                                </td>


                                {{-- SERVICIOS --}}
                                <td>

                                    @if ($servicios->isNotEmpty())

                                        @foreach ($servicios as $servicio)

                                            <span
                                                class="badge badge-info
                                                       d-inline-block mb-1"
                                            >
                                                {{ $servicio }}
                                            </span>

                                        @endforeach

                                    @else

                                        <span class="text-muted">
                                            No asignado
                                        </span>

                                    @endif

                                </td>


                                {{-- ESTADO --}}
                                <td class="text-center">

                                    @if (
                                        $fila['estado_clave']
                                        === 'al-dia'
                                    )

                                        <span class="badge badge-success">

                                            <i
                                                class="fas fa-check-circle mr-1"
                                            ></i>

                                            Al día

                                        </span>


                                    @elseif (
                                        $fila['estado_clave']
                                        === 'pendiente'
                                    )

                                        <span class="badge badge-warning">

                                            <i
                                                class="fas fa-clock mr-1"
                                            ></i>

                                            Pendiente

                                        </span>


                                    @else

                                        <span class="badge badge-danger">

                                            <i
                                                class="fas fa-exclamation-triangle mr-1"
                                            ></i>

                                            Con mora

                                        </span>

                                    @endif

                                </td>


                                {{-- RECIBOS PENDIENTES --}}
                                <td class="text-center">

                                    <strong>
                                        {{ $fila['recibos_pendientes'] }}
                                    </strong>

                                </td>


                                {{-- RECIBOS PAGADOS --}}
                                <td class="text-center">

                                    {{ $fila['recibos_pagados'] }}

                                </td>


                                {{-- MONTO PENDIENTE --}}
                                <td class="text-right">

                                    Q{{ number_format(
                                        $fila['monto_pendiente'],
                                        2
                                    ) }}

                                </td>


                                {{-- MORA --}}
                                <td class="text-right">

                                    @if ($fila['mora'] > 0)

                                        <strong class="text-danger">

                                            Q{{ number_format(
                                                $fila['mora'],
                                                2
                                            ) }}

                                        </strong>

                                    @else

                                        Q0.00

                                    @endif

                                </td>


                                {{-- TOTAL --}}
                                <td class="text-right">

                                    <strong>

                                        Q{{ number_format(
                                            $fila['total'],
                                            2
                                        ) }}

                                    </strong>

                                </td>


                                {{-- ACCIONES --}}
                                <td class="text-center">

                                    <div
                                        class="d-flex flex-column
                                               justify-content-center"
                                    >

                                        @if (
                                            $fila['recibos_pendientes']
                                            > 0
                                        )

                                            <a
                                                href="{{ route(
                                                    'pagos.index',
                                                    [
                                                        'q' =>
                                                            $cliente->dpi
                                                            ?: $cliente->nombre,
                                                    ]
                                                ) }}"
                                                class="btn btn-sm
                                                       btn-success mb-1"
                                            >
                                                <i
                                                    class="fas fa-cash-register mr-1"
                                                ></i>

                                                Ver deuda
                                            </a>

                                        @endif


                                        @if ($ultimoRecibo)

                                            <a
                                                href="{{ route(
                                                    'recibos.imprimir',
                                                    $ultimoRecibo
                                                ) }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="btn btn-sm
                                                       btn-outline-primary"
                                            >
                                                <i
                                                    class="fas fa-print mr-1"
                                                ></i>

                                                Último recibo
                                            </a>

                                        @endif


                                        @if (
                                            $fila['recibos_pendientes']
                                            === 0
                                            && ! $ultimoRecibo
                                        )

                                            <span class="text-muted">
                                                Sin movimientos
                                            </span>

                                        @endif

                                    </div>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="11"
                                    class="text-center py-5"
                                >

                                    <i
                                        class="fas fa-info-circle
                                               fa-2x
                                               d-block mb-2
                                               text-muted"
                                    ></i>

                                    <span class="text-muted">

                                        @if (
                                            $busqueda !== ''
                                            || $estado !== ''
                                        )

                                            No se encontraron clientes
                                            con los filtros seleccionados.

                                        @else

                                            No hay clientes registrados
                                            para mostrar.

                                        @endif

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