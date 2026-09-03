<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Recibo;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    /**
     * Lista los recibos pendientes de pago.
     * (AQ-32) Punto de entrada al módulo de pagos.
     */
public function index()
{
    $recibos = Recibo::with(['lectura.contador.cliente'])
        ->where('estado', 'PENDIENTE')
        ->orderBy('fecha_emision')
        ->paginate(15);

    return view('pagos.index', compact('recibos'));
}

    /**
     * Muestra el formulario de "Registrar pago" para un recibo puntual.
     * (AQ-32) La vista existe, pero el envío no guarda nada todavía.
     */
    public function create(Recibo $recibo)
    {
        $metodosPago = MetodoPago::where('activo', true)->get();

        return view('pagos.create', compact('recibo', 'metodosPago'));
    }

    /**
     * AQ-32 — Como el proyecto es un prototipo para pitch a la junta,
     * el módulo de pagos no procesa pagos reales este sprint.
     * En vez de guardar, redirige con un mensaje informativo.
     *
     * AQ-33 — Cuando se active el pago real, la restricción
     * UNIQUE(recibo_id) en la tabla `pagos` ya garantiza que un
     * mismo recibo no pueda pagarse dos veces (ver App\Models\Pago).
     * No hace falta lógica adicional de idempotencia por ahora.
     */
    public function store(Request $request)
    {
        return redirect()
            ->route('pagos.index')
            ->with('info', 'Esta funcionalidad estará disponible si el proyecto es seleccionado para continuidad.');
    }
}