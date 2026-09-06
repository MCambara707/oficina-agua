<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Recibo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardEstadoCuentaController extends Controller
{
    /**
     * Estados permitidos para filtrar el dashboard.
     */
    private const ESTADOS_VALIDOS = [
        'al-dia',
        'pendiente',
        'con-mora',
    ];

    /**
     * AQ-35 — Muestra el estado de cuenta de los clientes.
     *
     * Estados:
     * - Al día: no tiene recibos pendientes.
     * - Pendiente: tiene recibos pendientes, pero ninguno está atrasado.
     * - Con mora: tiene al menos un recibo pendiente atrasado.
     */
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->query('busqueda', ''));
        $estado = (string) $request->query('estado', '');

        // Evita aceptar valores de filtro no contemplados.
        if (! in_array($estado, self::ESTADOS_VALIDOS, true)) {
            $estado = '';
        }

        /*
         * Primero obtenemos los clientes.
         * La búsqueda por nombre se hace desde la base de datos.
         */
        $clientes = Cliente::query()
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where('nombre', 'like', "%{$busqueda}%");
            })
            ->orderBy('nombre')
            ->get();

        $clienteIds = $clientes->pluck('id');

        /*
         * Obtenemos los recibos de todos los clientes encontrados
         * en una sola consulta y cargamos la tarifa porque AQ-34
         * la necesita para calcular la mora.
         */
        $recibosPorCliente = collect();

        if ($clienteIds->isNotEmpty()) {
            $recibosPorCliente = Recibo::with([
                'lectura.contador',
                'tarifa',
            ])
                ->whereHas('lectura.contador', function ($query) use ($clienteIds) {
                    $query->whereIn('cliente_id', $clienteIds);
                })
                ->get()
                ->groupBy(function (Recibo $recibo) {
                    return $recibo->lectura?->contador?->cliente_id;
                });
        }

        /*
         * Construimos una fila de estado de cuenta para cada cliente.
         */
        $filas = $clientes->map(function (Cliente $cliente) use ($recibosPorCliente) {
            $recibos = $recibosPorCliente->get($cliente->id, collect());

            return $this->construirFila($cliente, $recibos);
        });

        /*
         * El estado se calcula usando la lógica de AQ-34,
         * por eso este filtro se aplica después de construir las filas.
         */
        if ($estado !== '') {
            $filas = $filas
                ->where('estado_clave', $estado)
                ->values();
        }

        return view('dashboard.estado-cuenta', compact(
            'filas',
            'busqueda',
            'estado'
        ));
    }

    /**
     * Construye la información consolidada de un cliente.
     */
    private function construirFila(Cliente $cliente, Collection $recibos): array
    {
        $pendientes = $recibos->filter(function (Recibo $recibo) {
            return $recibo->estado === 'PENDIENTE';
        });

        /*
         * Reutilizamos directamente AQ-34.
         * No se vuelve a implementar aquí la regla de vencimiento.
         */
        $tieneMora = $pendientes->contains(function (Recibo $recibo) {
            return $recibo->estaAtrasado();
        });

        if ($tieneMora) {
            $estadoClave = 'con-mora';
            $estadoEtiqueta = 'Con mora';
        } elseif ($pendientes->isNotEmpty()) {
            $estadoClave = 'pendiente';
            $estadoEtiqueta = 'Pendiente';
        } else {
            $estadoClave = 'al-dia';
            $estadoEtiqueta = 'Al día';
        }

        $montoPendiente = round(
            $pendientes->sum(fn (Recibo $recibo) => (float) $recibo->monto),
            2
        );

        /*
         * montoMora() y montoConMora() pertenecen a AQ-34.
         */
        $mora = round(
            $pendientes->sum(fn (Recibo $recibo) => $recibo->montoMora()),
            2
        );

        $total = round(
            $pendientes->sum(fn (Recibo $recibo) => $recibo->montoConMora()),
            2
        );

        return [
            'cliente' => $cliente,
            'estado_clave' => $estadoClave,
            'estado_etiqueta' => $estadoEtiqueta,
            'recibos_pendientes' => $pendientes->count(),
            'monto_pendiente' => $montoPendiente,
            'mora' => $mora,
            'total' => $total,
        ];
    }
}