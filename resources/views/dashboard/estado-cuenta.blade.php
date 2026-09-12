@extends('adminlte::page')

@section('title', 'Estado de cuenta')

@section('content_header')

    <div class="estado-cuenta-header">
        <h1 class="mb-0">
            Estado de cuenta
        </h1>

        <small class="estado-cuenta-texto-secundario">
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
            <i class="fas fa-check-circle me-1"></i>

            {{ session('exito') }}

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
                aria-label="Cerrar"
            ></button>
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
            ></button>
        </div>

    @endif


    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <div class="card mb-4 estado-cuenta-card estado-filtros-card">

        <div class="card-header">

            <h3 class="card-title mb-0">

                <i class="fas fa-filter me-1"></i>

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
                                class="form-select"
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
                                <i class="fas fa-search me-1"></i>

                                Buscar
                            </button>

                        </div>

                    </div>

                </div>


                @if ($busqueda !== '' || $estado !== '')

                    <div class="mt-1">

                        
                            href="{{ route('dashboard.estado-cuenta') }}"
                            class="btn btn-outline-secondary btn-sm"
                        >
                            <i class="fas fa-times me-1"></i>

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

    <div class="row g-3 mb-4 estado-cuenta-resumen">

        {{-- CLIENTES --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div
                class="small-box
                       estado-resumen-card
                       estado-resumen-clientes"
            >

                <div class="inner">

                    <h3 class="estado-resumen-valor">
                        {{ $resumenGeneral['clientes'] }}
                    </h3>

                    <p class="estado-resumen-etiqueta">
                        Clientes mostrados
                    </p>

                </div>

                <div class="icon estado-resumen-icono">
                    <i class="fas fa-users"></i>
                </div>

            </div>

        </div>


        {{-- AL DÍA --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div
                class="small-box
                       estado-resumen-card
                       estado-resumen-al-dia"
            >

                <div class="inner">

                    <h3
                        class="estado-resumen-valor
                               estado-resumen-valor-exito"
                    >
                        {{ $resumenGeneral['al_dia'] }}
                    </h3>

                    <p class="estado-resumen-etiqueta">
                        Al día
                    </p>

                </div>

                <div class="icon estado-resumen-icono">
                    <i class="fas fa-check-circle"></i>
                </div>

            </div>

        </div>


        {{-- PENDIENTES --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div
                class="small-box
                       estado-resumen-card
                       estado-resumen-pendientes"
            >

                <div class="inner">

                    <h3
                        class="estado-resumen-valor
                               estado-resumen-valor-advertencia"
                    >
                        {{ $resumenGeneral['pendientes'] }}
                    </h3>

                    <p class="estado-resumen-etiqueta">
                        Pendientes
                    </p>

                </div>

                <div class="icon estado-resumen-icono">
                    <i class="fas fa-clock"></i>
                </div>

            </div>

        </div>


        {{-- CON MORA --}}
        <div class="col-12 col-sm-6 col-lg-3">

            <div
                class="small-box
                       estado-resumen-card
                       estado-resumen-mora"
            >

                <div class="inner">

                    <h3
                        class="estado-resumen-valor
                               estado-resumen-valor-peligro"
                    >
                        {{ $resumenGeneral['con_mora'] }}
                    </h3>

                    <p class="estado-resumen-etiqueta">
                        Con mora
                    </p>

                </div>

                <div class="icon estado-resumen-icono">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         SALDO GENERAL
    ========================================================== --}}

    <div class="card mb-4 estado-cuenta-card estado-saldo-card">

        <div
            class="card-body
                   d-flex flex-column flex-md-row
                   flex-wrap gap-2
                   justify-content-between
                   align-items-md-center"
        >

            <div>

                <small class="estado-cuenta-texto-secundario">
                    Saldo pendiente de los clientes mostrados
                </small>

                <div class="estado-saldo-total">
                    Q{{ number_format(
                        $resumenGeneral['saldo_total'],
                        2
                    ) }}
                </div>

            </div>


            <div
                class="mt-3 mt-md-0
                       estado-cuenta-texto-secundario"
            >

                <i class="fas fa-info-circle me-1"></i>

                Incluye mora vigente cuando corresponde.

            </div>

        </div>

    </div>


    {{-- =========================================================
         ESTADO DE CUENTA POR CLIENTE
    ========================================================== --}}

    <div class="card estado-cuenta-card estado-tabla-card">

        <div class="card-header">

            <h3 class="card-title mb-0">

                <i class="fas fa-file-invoice-dollar me-1"></i>

                Estado de cuenta por cliente

            </h3>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table
                    class="table table-bordered
                           table-striped table-hover
                           align-middle mb-0
                           estado-cuenta-table"
                >

                    <thead>

                        <tr>

                            <th>
                                Cliente
                            </th>

                            <th>
                                DPI
                            </th>

                            <th>
                                Contadores
                            </th>

                            <th>
                                Servicios
                            </th>

                            <th class="text-center">
                                Estado
                            </th>

                            <th class="text-center">
                                Pendientes
                            </th>

                            <th class="text-center">
                                Pagados
                            </th>

                            <th class="text-end">
                                Monto pendiente
                            </th>

                            <th class="text-end">
                                Mora
                            </th>

                            <th class="text-end">
                                Total
                            </th>

                            <th
                                class="text-center
                                       estado-columna-acciones"
                            >
                                Acciones
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse ($filas as $fila)

                            @php

                                $cliente = $fila['cliente'];

                                $contadores =
                                    $cliente->contadores;

                                $servicios =
                                    $fila['servicios'];

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

                                        <small
                                            class="estado-cuenta-texto-secundario"
                                        >

                                            <i
                                                class="fas fa-phone me-1"
                                            ></i>

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
                                                        class="badge text-bg-success ms-1"
                                                    >
                                                        Activo
                                                    </span>

                                                @else

                                                    <span
                                                        class="badge text-bg-secondary ms-1"
                                                    >
                                                        Inactivo
                                                    </span>

                                                @endif

                                            </div>

                                        @endforeach

                                    @else

                                        <span
                                            class="estado-cuenta-texto-secundario"
                                        >
                                            Sin contador
                                        </span>

                                    @endif

                                </td>


                                {{-- SERVICIOS --}}
                                <td>

                                    @if ($servicios->isNotEmpty())

                                        @foreach ($servicios as $servicio)

                                            <span
                                                class="badge text-bg-info
                                                       d-inline-block mb-1"
                                            >
                                                {{ $servicio }}
                                            </span>

                                        @endforeach

                                    @else

                                        <span
                                            class="estado-cuenta-texto-secundario"
                                        >
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

                                        <span class="badge text-bg-success">

                                            <i
                                                class="fas fa-check-circle me-1"
                                            ></i>

                                            Al día

                                        </span>


                                    @elseif (
                                        $fila['estado_clave']
                                        === 'pendiente'
                                    )

                                        <span class="badge text-bg-warning">

                                            <i
                                                class="fas fa-clock me-1"
                                            ></i>

                                            Pendiente

                                        </span>


                                    @else

                                        <span class="badge text-bg-danger">

                                            <i
                                                class="fas fa-exclamation-triangle me-1"
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
                                <td class="text-end">

                                    Q{{ number_format(
                                        $fila['monto_pendiente'],
                                        2
                                    ) }}

                                </td>


                                {{-- MORA --}}
                                <td class="text-end">

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
                                <td class="text-end">

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
                                               gap-2
                                               justify-content-center"
                                    >

                                        @if (
                                            $fila['recibos_pendientes']
                                            > 0
                                        )

                                            
                                                href="{{ route(
                                                    'pagos.index',
                                                    [
                                                        'q' =>
                                                            $cliente->dpi
                                                            ?: $cliente->nombre,
                                                    ]
                                                ) }}"
                                                class="btn btn-sm btn-success"
                                            >
                                                <i
                                                    class="fas fa-cash-register me-1"
                                                ></i>

                                                Ver deuda
                                            </a>

                                        @endif


                                        @if ($ultimoRecibo)

                                            
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
                                                    class="fas fa-print me-1"
                                                ></i>

                                                Último recibo
                                            </a>

                                        @endif


                                        @if (
                                            $fila['recibos_pendientes']
                                            === 0
                                            && ! $ultimoRecibo
                                        )

                                            <span
                                                class="estado-cuenta-texto-secundario"
                                            >
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
                                               fa-2x d-block mb-2
                                               estado-cuenta-texto-secundario"
                                    ></i>

                                    <span
                                        class="estado-cuenta-texto-secundario"
                                    >

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