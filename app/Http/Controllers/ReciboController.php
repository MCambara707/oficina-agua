<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Recibo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReciboController extends Controller
{
    /** Consulta administrativa de recibos de cualquier período y estado. */
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'periodo' => ['nullable', 'date_format:Y-m'],
            'estado' => ['nullable', Rule::in(['PENDIENTE', 'PAGADO', 'ANULADO'])],
        ]);

        $busqueda = trim($filtros['q'] ?? '');
        $periodo = $filtros['periodo'] ?? '';
        $estado = $filtros['estado'] ?? '';

        $recibos = Recibo::with(['lectura.contador.cliente', 'tarifa', 'pago'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($query) use ($busqueda) {
                    $query->where('numero_recibo', 'like', "%{$busqueda}%")
                        ->orWhereHas('lectura.contador', function ($contador) use ($busqueda) {
                            $contador->where('numero_registro', 'like', "%{$busqueda}%");
                        })
                        ->orWhereHas('lectura.contador.cliente', function ($cliente) use ($busqueda) {
                            $cliente->where(function ($cliente) use ($busqueda) {
                                $cliente->where('nombre', 'like', "%{$busqueda}%")
                                    ->orWhere('dpi', 'like', "%{$busqueda}%");
                            });
                        });
                });
            })
            ->when($periodo !== '', function ($query) use ($periodo) {
                $query->whereHas('lectura', function ($lectura) use ($periodo) {
                    $lectura->whereDate('periodo', $periodo . '-01');
                });
            })
            ->when($estado !== '', fn ($query) => $query->where('estado', $estado))
            ->orderByDesc('fecha_emision')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('recibos.index', compact('recibos', 'busqueda', 'periodo', 'estado'));
    }

    /**
     * Muestra el recibo imprimible.
     *
     * Puede ser utilizado por:
     * - Administrador
     * - Secretaria
     * - Lector
     *
     * Si el recibo ya fue pagado y existe un registro
     * de pago, la misma vista funciona como comprobante.
     */
    public function imprimir(Recibo $recibo)
    {
        /*
         * Cargamos toda la información necesaria del recibo.
         *
         * Se incluye Servicio porque actualmente funciona
         * como clasificación informativa del contador.
         */
        $recibo->load([
            'lectura.contador.cliente',
            'lectura.contador.servicio',
            'tarifa',
        ]);

        /*
         * Relaciones principales.
         */
        $lectura = $recibo->lectura;
        $contador = $lectura->contador;
        $cliente = $contador->cliente;
        $servicio = $contador->servicio;
        $tarifa = $recibo->tarifa;

        /*
         * Información actual del recibo.
         *
         * Mientras el recibo está PENDIENTE,
         * Recibo se encarga de calcular automáticamente:
         *
         * - vencimiento;
         * - días de atraso;
         * - mora;
         * - total pendiente.
         */
        $moraActual = $recibo->montoMora();
        $totalActual = $recibo->montoConMora();
        $fechaVencimiento = $recibo->fechaVencimiento();
        $diasAtraso = $recibo->diasAtraso();
        $estaAtrasado = $recibo->estaAtrasado();

        /*
         * Obtener todos los recibos asociados actualmente
         * al mismo cliente.
         *
         * Esto permite mostrar un pequeño resumen de su
         * estado de cuenta dentro del recibo.
         */
        $recibosCliente = Recibo::with([
                'lectura.contador.cliente',
                'tarifa',
            ])
            ->whereHas('lectura.contador', function ($query) use ($cliente) {
                $query->where('cliente_id', $cliente->id);
            })
            ->orderByDesc('fecha_emision')
            ->get();

        /*
         * Resumen del estado de cuenta.
         */
        $pendientes = 0;
        $conMora = 0;
        $pagados = 0;
        $anulados = 0;
        $saldoPendiente = 0.00;

        foreach ($recibosCliente as $item) {

            /*
             * Los recibos pagados ya no forman parte
             * del saldo pendiente.
             */
            if ($item->estado === 'PAGADO') {
                $pagados++;
                continue;
            }

            /*
             * Los recibos anulados tampoco generan saldo.
             */
            if ($item->estado === 'ANULADO') {
                $anulados++;
                continue;
            }

            /*
             * Separar recibos pendientes normales
             * de recibos vencidos con mora.
             */
            if ($item->estaAtrasado()) {
                $conMora++;
            } else {
                $pendientes++;
            }

            /*
             * El saldo pendiente incluye la mora
             * correspondiente a cada recibo vencido.
             */
            $saldoPendiente += $item->montoConMora();
        }

        $resumenCliente = [
            'total_recibos' => $recibosCliente->count(),
            'pendientes' => $pendientes,
            'con_mora' => $conMora,
            'pagados' => $pagados,
            'anulados' => $anulados,
            'saldo_pendiente' => round($saldoPendiente, 2),
        ];

        /*
         * La base de datos permite únicamente un pago
         * por recibo gracias a UNIQUE(recibo_id).
         *
         * Cuando implementemos el pago real, aquí podremos
         * recuperar toda la información del comprobante.
         */
        $ultimoPago = Pago::with([
                'metodoPago',
                'usuarioRegistro',
            ])
            ->where('recibo_id', $recibo->id)
            ->first();

        /*
         * Un documento se considera comprobante únicamente
         * cuando el recibo está PAGADO y existe realmente
         * un registro en la tabla pagos.
         */
        $esComprobante = $recibo->estado === 'PAGADO'
            && $ultimoPago !== null;

        /*
         * Información histórica del pago.
         *
         * IMPORTANTE:
         *
         * Una vez que un recibo cambia a PAGADO,
         * montoMora() devuelve 0 porque ya no está pendiente.
         *
         * Por eso, para un comprobante usamos el monto
         * realmente almacenado en pagos.
         *
         * Mora pagada =
         * total pagado - monto original del recibo.
         */
        $totalPagado = null;
        $moraPagada = 0.00;

        if ($esComprobante) {
            $totalPagado = (float) $ultimoPago->monto;

            $moraPagada = max(
                round(
                    $totalPagado - (float) $recibo->monto,
                    2
                ),
                0
            );
        }

        /*
         * Enviar toda la información a la vista imprimible.
         */
        return view('recibos.imprimible', compact(
            'recibo',
            'lectura',
            'contador',
            'cliente',
            'servicio',
            'tarifa',
            'moraActual',
            'totalActual',
            'fechaVencimiento',
            'diasAtraso',
            'estaAtrasado',
            'resumenCliente',
            'ultimoPago',
            'esComprobante',
            'totalPagado',
            'moraPagada'
        ));
    }
}
