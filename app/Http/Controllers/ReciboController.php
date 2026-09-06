<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Recibo;

class ReciboController extends Controller
{
    public function imprimir(Recibo $recibo)
    {
        $recibo->load([
            'lectura.contador.cliente',
            'tarifa',
        ]);

        $lectura = $recibo->lectura;
        $contador = $lectura->contador;
        $cliente = $contador->cliente;
        $tarifa = $recibo->tarifa;

        // Reutilizamos la lógica oficial de AQ-34.
        $moraActual = $recibo->montoMora();
        $totalActual = $recibo->montoConMora();
        $fechaVencimiento = $recibo->fechaVencimiento();
        $diasAtraso = $recibo->diasAtraso();
        $estaAtrasado = $recibo->estaAtrasado();

        // Todos los recibos pertenecientes al mismo cliente.
        $recibosCliente = Recibo::with([
                'lectura.contador.cliente',
                'tarifa',
            ])
            ->whereHas('lectura.contador', function ($query) use ($cliente) {
                $query->where('cliente_id', $cliente->id);
            })
            ->orderByDesc('fecha_emision')
            ->get();

        $pendientes = 0;
        $conMora = 0;
        $pagados = 0;
        $anulados = 0;
        $saldoPendiente = 0.00;

        foreach ($recibosCliente as $item) {
            if ($item->estado === 'PAGADO') {
                $pagados++;
                continue;
            }

            if ($item->estado === 'ANULADO') {
                $anulados++;
                continue;
            }

            if ($item->estaAtrasado()) {
                $conMora++;
            } else {
                $pendientes++;
            }

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

        // La BD solo permite un pago por recibo gracias a UNIQUE(recibo_id).
        $ultimoPago = Pago::with([
                'metodoPago',
                'usuarioRegistro',
            ])
            ->where('recibo_id', $recibo->id)
            ->first();

        $esComprobante = $recibo->estado === 'PAGADO'
            && $ultimoPago !== null;

        return view('recibos.imprimible', compact(
            'recibo',
            'lectura',
            'contador',
            'cliente',
            'tarifa',
            'moraActual',
            'totalActual',
            'fechaVencimiento',
            'diasAtraso',
            'estaAtrasado',
            'resumenCliente',
            'ultimoPago',
            'esComprobante'
        ));
    }
}