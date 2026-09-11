@extends('adminlte::page')

@section('title', 'Registrar pago')

@section('content_header')
    <div>
        <h1 class="mb-0">Registrar pago</h1>
        <small class="text-muted">
            Confirme los datos del recibo antes de registrar el cobro.
        </small>
    </div>
@stop


@section('content')

    @php
        $contador = $recibo->lectura?->contador;
        $cliente = $contador?->cliente;
        $servicio = $contador?->servicio;

        $montoOriginal = (float) $recibo->monto;
        $mora = (float) $recibo->montoMora();
        $totalPagar = (float) $recibo->montoConMora();
        $estaAtrasado = $recibo->estaAtrasado();
    @endphp


    {{-- =========================================================
         ERRORES DE VALIDACIÓN
    ========================================================== --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>No fue posible registrar el pago.</strong>

            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    {{-- =========================================================
         INFORMACIÓN DEL RECIBO
    ========================================================== --}}
    <div class="card">

        <div class="card-header">
            <h3 class="card-title mb-0">
                Recibo N.° {{ $recibo->numero_recibo }}
            </h3>
        </div>


        <div class="card-body">

            {{-- Estado de mora --}}
            @if ($estaAtrasado)

                <div class="alert alert-warning">

                    <strong>
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Recibo con mora
                    </strong>

                    <div class="mt-1">
                        Tiene
                        <strong>
                            {{ $recibo->diasAtraso() }} día(s)
                        </strong>
                        de atraso y una mora de
                        <strong>
                            Q{{ number_format($mora, 2) }}
                        </strong>.
                    </div>

                </div>

            @else

                <div class="alert alert-success">

                    <i class="fas fa-check-circle me-1"></i>

                    El recibo se encuentra dentro del período de pago
                    y no tiene mora.

                </div>

            @endif


            {{-- =================================================
                 DATOS DEL CLIENTE
            ================================================== --}}
            <div class="row">

                <div class="col-md-6">

                    <h5 class="mb-3">
                        Datos del cliente
                    </h5>

                    <dl class="row mb-0">

                        <dt class="col-sm-4">
                            Cliente
                        </dt>

                        <dd class="col-sm-8">
                            {{ $cliente?->nombre ?? '—' }}
                        </dd>


                        <dt class="col-sm-4">
                            DPI
                        </dt>

                        <dd class="col-sm-8">
                            {{ $cliente?->dpi ?? 'No registrado' }}
                        </dd>


                        <dt class="col-sm-4">
                            Contador
                        </dt>

                        <dd class="col-sm-8">
                            {{ $contador?->numero_registro ?? '—' }}
                        </dd>


                        <dt class="col-sm-4">
                            Servicio
                        </dt>

                        <dd class="col-sm-8">

                            @if ($servicio)

                                <span class="badge badge-info">
                                    {{ $servicio->nombre }}
                                </span>

                            @else

                                <span class="text-muted">
                                    No asignado
                                </span>

                            @endif

                        </dd>


                        <dt class="col-sm-4">
                            Período
                        </dt>

                        <dd class="col-sm-8">
                            {{ $recibo->lectura?->periodo
                                ? $recibo->lectura->periodo->format('m/Y')
                                : '—'
                            }}
                        </dd>

                    </dl>

                </div>


                {{-- =============================================
                     DATOS DEL RECIBO
                ============================================== --}}
                <div class="col-md-6 mt-4 mt-md-0">

                    <h5 class="mb-3">
                        Datos del recibo
                    </h5>

                    <dl class="row mb-0">

                        <dt class="col-sm-5">
                            Fecha de emisión
                        </dt>

                        <dd class="col-sm-7">
                            {{ $recibo->fecha_emision
                                ? $recibo->fecha_emision->format('d/m/Y')
                                : '—'
                            }}
                        </dd>


                        <dt class="col-sm-5">
                            Fecha de vencimiento
                        </dt>

                        <dd class="col-sm-7">
                            {{ $recibo->fechaVencimiento()->format('d/m/Y') }}
                        </dd>


                        <dt class="col-sm-5">
                            Estado
                        </dt>

                        <dd class="col-sm-7">

                            @if ($estaAtrasado)

                                <span class="badge badge-danger">
                                    CON MORA
                                </span>

                            @else

                                <span class="badge badge-warning">
                                    PENDIENTE
                                </span>

                            @endif

                        </dd>

                    </dl>

                </div>

            </div>


            <hr>


            {{-- =================================================
                 RESUMEN DEL COBRO
            ================================================== --}}
            <div class="row justify-content-end">

                <div class="col-md-5 col-lg-4">

                    <div class="card bg-light">

                        <div class="card-body">

                            <div
                                class="d-flex justify-content-between
                                       mb-2"
                            >
                                <span>
                                    Monto original
                                </span>

                                <strong>
                                    Q{{ number_format(
                                        $montoOriginal,
                                        2
                                    ) }}
                                </strong>
                            </div>


                            <div
                                class="d-flex justify-content-between
                                       mb-2"
                            >
                                <span>
                                    Mora
                                </span>

                                <strong
                                    class="{{ $mora > 0
                                        ? 'text-danger'
                                        : ''
                                    }}"
                                >
                                    Q{{ number_format(
                                        $mora,
                                        2
                                    ) }}
                                </strong>
                            </div>


                            <hr class="my-2">


                            <div
                                class="d-flex justify-content-between
                                       align-items-center"
                            >
                                <strong>
                                    Total a pagar
                                </strong>

                                <strong
                                    class="text-primary"
                                    style="font-size: 1.35rem;"
                                >
                                    Q{{ number_format(
                                        $totalPagar,
                                        2
                                    ) }}
                                </strong>
                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 FORMULARIO DE PAGO
            ================================================== --}}
            @if ($totalPagar <= 0)
                <div class="alert alert-warning">
                    No se puede registrar un pago de Q0.00. El recibo permanece pendiente hasta definir la política de liquidación de recibos sin importe.
                </div>
            @endif
            @if ($metodosPago->isEmpty())
                <div class="alert alert-warning">
                    No hay métodos de pago activos. Solicite al Administrador habilitar un método antes de cobrar.
                </div>
            @endif
            <form
                action="{{ route('pagos.store') }}"
                method="POST"
                class="mt-3"
            >

                @csrf


                <input
                    type="hidden"
                    name="recibo_id"
                    value="{{ $recibo->id }}"
                >


                <div class="row">

                    {{-- MÉTODO DE PAGO --}}
                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="metodo_pago_id">
                                Método de pago
                                <span class="text-danger">*</span>
                            </label>

                            <select
                                name="metodo_pago_id"
                                id="metodo_pago_id"
                                class="form-control
                                       @error('metodo_pago_id')
                                           is-invalid
                                       @enderror"
                                required
                            >

                                <option value="">
                                    Seleccione un método
                                </option>

                                @foreach ($metodosPago as $metodo)

                                    <option
                                        value="{{ $metodo->id }}"
                                        @selected(
                                            old('metodo_pago_id')
                                            == $metodo->id
                                        )
                                    >
                                        {{ $metodo->nombre }}
                                    </option>

                                @endforeach

                            </select>


                            @error('metodo_pago_id')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>


                    {{-- REFERENCIA --}}
                    <div class="col-md-6">

                        <div class="form-group">

                            <label for="referencia">
                                Referencia
                                <small class="text-muted">
                                    (opcional)
                                </small>
                            </label>

                            <input
                                type="text"
                                name="referencia"
                                id="referencia"
                                maxlength="100"
                                value="{{ old('referencia') }}"
                                class="form-control
                                       @error('referencia')
                                           is-invalid
                                       @enderror"
                                placeholder="Ej. número de transferencia"
                            >


                            @error('referencia')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                        </div>

                    </div>

                </div>


                {{-- OBSERVACIÓN --}}
                <div class="form-group">

                    <label for="observacion">
                        Observación
                        <small class="text-muted">
                            (opcional)
                        </small>
                    </label>

                    <textarea
                        name="observacion"
                        id="observacion"
                        rows="3"
                        maxlength="255"
                        class="form-control
                               @error('observacion')
                                   is-invalid
                               @enderror"
                        placeholder="Ingrese una observación si es necesario"
                    >{{ old('observacion') }}</textarea>


                    @error('observacion')

                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- =================================================
                     ACCIONES
                ================================================== --}}
                <div
                    class="d-flex flex-column flex-sm-row flex-wrap gap-2
                           justify-content-between mt-4"
                >

                    <a
                        href="{{ route('pagos.index') }}"
                        class="btn btn-secondary"
                    >
                        <i class="fas fa-arrow-left me-1"></i>
                        Cancelar
                    </a>


                    <button
                        type="submit"
                        class="btn btn-success"
                        @disabled($totalPagar <= 0 || $metodosPago->isEmpty())
                        onclick="
                            return confirm(
                                '¿Confirma registrar el pago de Q{{ number_format(
                                    $totalPagar,
                                    2
                                ) }} para el recibo {{ $recibo->numero_recibo }}?'
                            );
                        "
                    >
                        <i class="fas fa-cash-register me-1"></i>
                        Confirmar pago
                    </button>

                </div>

            </form>

        </div>

    </div>

@stop
