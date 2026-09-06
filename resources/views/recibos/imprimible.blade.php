<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        {{ $esComprobante ? 'Comprobante de pago' : 'Recibo de agua' }}
        {{ $recibo->numero_recibo }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            background: #eef2f6;
            color: #1f2937;
            font-family: Arial, Helvetica, sans-serif;
        }

        .acciones {
            width: 100%;
            max-width: 850px;
            margin: 0 auto 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            border: 0;
            border-radius: 7px;
            padding: 11px 18px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-imprimir {
            background: #0b74b8;
            color: #fff;
        }

        .btn-volver {
            background: #4b5563;
            color: #fff;
        }

        .documento {
            width: 100%;
            max-width: 850px;
            margin: auto;
            padding: 38px 44px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .08);
        }

        .encabezado {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 25px;
            padding-bottom: 22px;
            border-bottom: 2px solid #0b74b8;
        }

        .logo {
            width: 270px;
            max-height: 100px;
            object-fit: contain;
            object-position: left center;
        }

        .documento-info {
            text-align: right;
        }

        .documento-info h1 {
            margin: 0 0 8px;
            font-size: 25px;
            color: #0b4f7c;
        }

        .numero-recibo {
            font-size: 15px;
            color: #4b5563;
        }

        .estado {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .estado-pagado {
            background: #dcfce7;
            color: #166534;
        }

        .estado-mora {
            background: #fee2e2;
            color: #991b1b;
        }

        .estado-pendiente {
            background: #fef3c7;
            color: #92400e;
        }

        .seccion {
            margin-top: 28px;
        }

        .seccion h2 {
            margin: 0 0 14px;
            padding-bottom: 7px;
            border-bottom: 1px solid #d1d5db;
            color: #0b4f7c;
            font-size: 17px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px 30px;
        }

        .dato {
            font-size: 14px;
            line-height: 1.5;
        }

        .dato strong {
            display: block;
            color: #374151;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .tabla th,
        .tabla td {
            border: 1px solid #d1d5db;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }

        .tabla th {
            background: #f3f4f6;
            color: #374151;
        }

        .tabla td.text-end,
        .tabla th.text-end {
            text-align: right;
        }

        .resumen {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .resumen-item {
            border: 1px solid #dbe3ea;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .resumen-item span {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .resumen-item strong {
            font-size: 19px;
            color: #111827;
        }

        .totales {
            width: 340px;
            margin: 25px 0 0 auto;
        }

        .fila-total {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        .fila-total.final {
            border-top: 2px solid #0b74b8;
            border-bottom: 0;
            margin-top: 4px;
            padding-top: 12px;
            font-size: 20px;
            font-weight: 700;
            color: #0b4f7c;
        }

        .comprobante {
            margin-top: 28px;
            padding: 18px;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            border-radius: 8px;
        }

        .comprobante h2 {
            margin: 0 0 16px;
            color: #166534;
            font-size: 18px;
        }

        .pie {
            margin-top: 35px;
            padding-top: 15px;
            border-top: 1px solid #d1d5db;
            text-align: center;
            color: #6b7280;
            font-size: 11px;
        }

        @media (max-width: 700px) {
            body {
                padding: 10px;
            }

            .documento {
                padding: 25px 20px;
            }

            .encabezado {
                flex-direction: column;
                align-items: flex-start;
            }

            .documento-info {
                text-align: left;
            }

            .grid,
            .resumen {
                grid-template-columns: 1fr;
            }

            .totales {
                width: 100%;
            }
        }

        @media print {
            @page {
                size: A4;
                margin: 8mm;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            body {
                font-size: 12px;
            }

            .acciones {
                display: none !important;
            }

            .documento {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
                border-radius: 0;
                box-shadow: none;
            }

            .encabezado {
                gap: 18px;
                padding-bottom: 12px;
            }

            .logo {
                width: 220px;
                max-height: 70px;
            }

            .documento-info h1 {
                font-size: 21px;
                margin-bottom: 4px;
            }

            .numero-recibo {
                font-size: 13px;
            }

            .estado {
                margin-top: 5px;
                padding: 4px 8px;
                font-size: 10px;
            }

            .seccion {
                margin-top: 15px;
            }

            .seccion h2 {
                margin-bottom: 7px;
                padding-bottom: 4px;
                font-size: 14px;
            }

            .grid {
                gap: 6px 20px;
            }

            .dato {
                font-size: 11px;
                line-height: 1.3;
            }

            .dato strong {
                font-size: 9px;
            }

            .tabla {
                margin-top: 7px;
            }

            .tabla th,
            .tabla td {
                padding: 6px;
                font-size: 10px;
            }

            .resumen {
                margin-top: 10px;
                gap: 7px;
            }

            .resumen-item {
                padding: 7px;
                border-radius: 5px;
            }

            .resumen-item span {
                margin-bottom: 2px;
                font-size: 9px;
            }

            .resumen-item strong {
                font-size: 14px;
            }

            .totales {
                width: 300px;
                margin-top: 10px;
            }

            .fila-total {
                padding: 4px 0;
                font-size: 11px;
            }

            .fila-total.final {
                margin-top: 2px;
                padding-top: 7px;
                font-size: 16px;
            }

            .comprobante {
                margin-top: 12px;
                padding: 10px;
                border-radius: 5px;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .comprobante h2 {
                margin-bottom: 8px;
                font-size: 14px;
            }

            .pie {
                margin-top: 12px;
                padding-top: 6px;
                font-size: 9px;
            }

            .encabezado,
            .seccion,
            .tabla,
            .resumen,
            .totales,
            .comprobante,
            .pie {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>

<body>

<div class="acciones">
    <a href="{{ url()->previous() }}" class="btn btn-volver">
        Volver
    </a>

    <button type="button" class="btn btn-imprimir" onclick="window.print()">
        Imprimir
    </button>
</div>

<main class="documento">

    <header class="encabezado">
        <div>
            <img
                src="{{ asset('img/branding/Logo_AquatechGt.png') }}"
                alt="AquaTech GT"
                class="logo"
            >
        </div>

        <div class="documento-info">
            <h1>
                {{ $esComprobante ? 'Comprobante de pago' : 'Recibo de agua potable' }}
            </h1>

            <div class="numero-recibo">
                No. {{ $recibo->numero_recibo }}
            </div>

            @if ($recibo->estado === 'PAGADO')
                <span class="estado estado-pagado">PAGADO</span>
            @elseif ($estaAtrasado)
                <span class="estado estado-mora">CON MORA</span>
            @else
                <span class="estado estado-pendiente">PENDIENTE</span>
            @endif
        </div>
    </header>

    <section class="seccion">
        <h2>Datos del cliente</h2>

        <div class="grid">
            <div class="dato">
                <strong>Cliente</strong>
                {{ $cliente->nombre }}
            </div>

            <div class="dato">
                <strong>DPI</strong>
                {{ $cliente->dpi ?? 'No registrado' }}
            </div>

            <div class="dato">
                <strong>Teléfono</strong>
                {{ $cliente->telefono ?? 'No registrado' }}
            </div>

            <div class="dato">
                <strong>Contador</strong>
                {{ $contador->numero_registro }}
            </div>

            <div class="dato">
                <strong>Dirección del servicio</strong>
                {{ $contador->direccion_servicio
                    ?? $contador->punto_referencia
                    ?? $contador->sector
                    ?? 'No registrada' }}
            </div>

            <div class="dato">
                <strong>Fecha de emisión</strong>
                {{ $recibo->fecha_emision->format('d/m/Y') }}
            </div>

            <div class="dato">
                <strong>Fecha de vencimiento</strong>
                {{ $fechaVencimiento->format('d/m/Y') }}
            </div>

            <div class="dato">
                <strong>Período</strong>
                {{ \Carbon\Carbon::parse($lectura->periodo)->format('m/Y') }}
            </div>
        </div>
    </section>

    <section class="seccion">
        <h2>Detalle del consumo</h2>

        <table class="tabla">
            <thead>
                <tr>
                    <th>Lectura anterior</th>
                    <th>Lectura actual</th>
                    <th>Consumo</th>
                    <th>Tarifa aplicada</th>
                    <th class="text-end">Monto</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>{{ number_format((float) $lectura->lectura_anterior, 3) }}</td>
                    <td>{{ number_format((float) $lectura->lectura_actual, 3) }}</td>
                    <td>{{ number_format((float) $lectura->consumo_m3, 3) }} m³</td>

                    <td>
                        {{ $tarifa->nombre }}
                        <br>
                        <small>{{ $tarifa->tipo }}</small>
                    </td>

                    <td class="text-end">
                        Q{{ number_format((float) $recibo->monto, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </section>

    <section class="seccion">
        <h2>Estado de cuenta del cliente</h2>

        <div class="resumen">
            <div class="resumen-item">
                <span>Pendientes</span>
                <strong>{{ $resumenCliente['pendientes'] }}</strong>
            </div>

            <div class="resumen-item">
                <span>Con mora</span>
                <strong>{{ $resumenCliente['con_mora'] }}</strong>
            </div>

            <div class="resumen-item">
                <span>Pagados</span>
                <strong>{{ $resumenCliente['pagados'] }}</strong>
            </div>

            <div class="resumen-item">
                <span>Saldo pendiente</span>
                <strong>
                    Q{{ number_format($resumenCliente['saldo_pendiente'], 2) }}
                </strong>
            </div>
        </div>
    </section>

    <div class="totales">

        <div class="fila-total">
            <span>Monto del recibo</span>
            <strong>
                Q{{ number_format((float) $recibo->monto, 2) }}
            </strong>
        </div>

        <div class="fila-total">
            <span>Mora</span>
            <strong>
                Q{{ number_format($moraActual, 2) }}
            </strong>
        </div>

        @if ($estaAtrasado)
            <div class="fila-total">
                <span>Días de atraso</span>
                <strong>{{ $diasAtraso }}</strong>
            </div>
        @endif

        <div class="fila-total final">
            <span>Total</span>
            <span>
                Q{{ number_format($totalActual, 2) }}
            </span>
        </div>

    </div>

    @if ($esComprobante && $ultimoPago)
        <section class="comprobante">

            <h2>Comprobante de pago</h2>

            <div class="grid">

                <div class="dato">
                    <strong>Fecha de pago</strong>

                    {{ $ultimoPago->fecha_pago
                        ? $ultimoPago->fecha_pago->format('d/m/Y H:i')
                        : 'No registrada' }}
                </div>

                <div class="dato">
                    <strong>Monto pagado</strong>

                    Q{{ number_format((float) $ultimoPago->monto, 2) }}
                </div>

                <div class="dato">
                    <strong>Método de pago</strong>

                    {{ $ultimoPago->metodoPago?->nombre ?? 'No registrado' }}
                </div>

                <div class="dato">
                    <strong>Referencia</strong>

                    {{ $ultimoPago->referencia ?? 'Sin referencia' }}
                </div>

                <div class="dato">
                    <strong>Registrado por</strong>

                    {{ $ultimoPago->usuarioRegistro?->nombre ?? 'Sistema' }}
                </div>

            </div>

        </section>
    @endif

    <footer class="pie">
        AquaTech GT — Control y eficiencia hídrica
        <br>
        Documento generado por el Sistema de Gestión de la Oficina del Agua.
    </footer>

</main>

</body>
</html>