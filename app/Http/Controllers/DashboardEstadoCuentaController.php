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
     * Muestra el estado de cuenta consolidado de los clientes.
     *
     * Estados:
     *
     * - Al día:
     *   no tiene recibos pendientes.
     *
     * - Pendiente:
     *   tiene recibos pendientes, pero ninguno vencido.
     *
     * - Con mora:
     *   tiene al menos un recibo pendiente vencido.
     */
    public function index(Request $request)
    {
        $busqueda = trim(
            (string) $request->query('busqueda', '')
        );

        $estado = trim(
            (string) $request->query('estado', '')
        );


        /*
         * =========================================================
         * VALIDAR FILTRO DE ESTADO
         * =========================================================
         */
        if (! in_array(
            $estado,
            self::ESTADOS_VALIDOS,
            true
        )) {
            $estado = '';
        }


        /*
         * =========================================================
         * CLIENTES
         * =========================================================
         *
         * La búsqueda permite encontrar clientes por:
         *
         * - nombre;
         * - DPI;
         * - número de contador.
         *
         * Además cargamos sus contadores y servicios para poder
         * mostrar información operacional en el dashboard.
         */
        $clientes = Cliente::query()
            ->with([
                'contadores.servicio',
            ])
            ->when(
                $busqueda !== '',
                function ($query) use ($busqueda) {

                    $query->where(
                        function ($q) use ($busqueda) {

                            /*
                             * Buscar por nombre.
                             */
                            $q->where(
                                'nombre',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            /*
                             * Buscar por DPI.
                             */
                            $q->orWhere(
                                'dpi',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            /*
                             * Buscar por número de contador.
                             */
                            $q->orWhereHas(
                                'contadores',
                                function ($contadorQuery) use ($busqueda) {

                                    $contadorQuery->where(
                                        'numero_registro',
                                        'like',
                                        '%' . $busqueda . '%'
                                    );
                                }
                            );
                        }
                    );
                }
            )
            ->orderBy('nombre')
            ->get();


        $clienteIds = $clientes->pluck('id');


        /*
         * =========================================================
         * RECIBOS AGRUPADOS POR CLIENTE
         * =========================================================
         *
         * Se obtiene toda la información necesaria en una sola
         * consulta para evitar múltiples consultas por cliente.
         */
        $recibosPorCliente = collect();

        if ($clienteIds->isNotEmpty()) {

            $recibosPorCliente = Recibo::with([
                    'lectura.contador.cliente',
                    'lectura.contador.servicio',
                    'tarifa',
                    'pago',
                ])
                ->whereHas(
                    'lectura.contador',
                    function ($query) use ($clienteIds) {

                        $query->whereIn(
                            'cliente_id',
                            $clienteIds
                        );
                    }
                )
                ->orderByDesc('fecha_emision')
                ->orderByDesc('id')
                ->get()
                ->groupBy(
                    function (Recibo $recibo) {

                        return $recibo
                            ->lectura
                            ?->contador
                            ?->cliente_id;
                    }
                );
        }


        /*
         * =========================================================
         * FILAS DEL DASHBOARD
         * =========================================================
         */
        $filas = $clientes->map(
            function (Cliente $cliente) use ($recibosPorCliente) {

                $recibos = $recibosPorCliente->get(
                    $cliente->id,
                    collect()
                );

                return $this->construirFila(
                    $cliente,
                    $recibos
                );
            }
        );


        /*
         * =========================================================
         * FILTRAR POR ESTADO
         * =========================================================
         *
         * El estado depende del cálculo de mora,
         * por eso se aplica después de construir cada fila.
         */
        if ($estado !== '') {

            $filas = $filas
                ->where(
                    'estado_clave',
                    $estado
                )
                ->values();
        }


        /*
         * =========================================================
         * RESUMEN GENERAL
         * =========================================================
         *
         * Información útil para mostrar indicadores superiores
         * en el dashboard.
         */
        $resumenGeneral = [
            'clientes' =>
                $filas->count(),

            'al_dia' =>
                $filas
                    ->where(
                        'estado_clave',
                        'al-dia'
                    )
                    ->count(),

            'pendientes' =>
                $filas
                    ->where(
                        'estado_clave',
                        'pendiente'
                    )
                    ->count(),

            'con_mora' =>
                $filas
                    ->where(
                        'estado_clave',
                        'con-mora'
                    )
                    ->count(),

            'saldo_total' =>
                round(
                    (float) $filas->sum('total'),
                    2
                ),
        ];


        return view(
            'dashboard.estado-cuenta',
            compact(
                'filas',
                'busqueda',
                'estado',
                'resumenGeneral'
            )
        );
    }


    /**
     * Construye la información consolidada
     * correspondiente a un cliente.
     */
    private function construirFila(
        Cliente $cliente,
        Collection $recibos
    ): array {

        /*
         * =========================================================
         * SEPARACIÓN DE RECIBOS
         * =========================================================
         */

        $pendientes = $recibos->filter(
            function (Recibo $recibo) {

                return $recibo->estado === 'PENDIENTE';
            }
        );


        $pagados = $recibos->filter(
            function (Recibo $recibo) {

                return $recibo->estado === 'PAGADO';
            }
        );


        $anulados = $recibos->filter(
            function (Recibo $recibo) {

                return $recibo->estado === 'ANULADO';
            }
        );


        /*
         * =========================================================
         * ESTADO DEL CLIENTE
         * =========================================================
         *
         * Reutilizamos la lógica oficial del modelo Recibo.
         */
        $tieneMora = $pendientes->contains(
            function (Recibo $recibo) {

                return $recibo->estaAtrasado();
            }
        );


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


        /*
         * =========================================================
         * MONTO ORIGINAL PENDIENTE
         * =========================================================
         */
        $montoPendiente = round(
            (float) $pendientes->sum(
                fn (Recibo $recibo) =>
                    (float) $recibo->monto
            ),
            2
        );


        /*
         * =========================================================
         * MORA TOTAL
         * =========================================================
         */
        $mora = round(
            (float) $pendientes->sum(
                fn (Recibo $recibo) =>
                    $recibo->montoMora()
            ),
            2
        );


        /*
         * =========================================================
         * TOTAL PENDIENTE
         * =========================================================
         *
         * Incluye:
         *
         * monto original
         * +
         * mora vigente.
         */
        $total = round(
            (float) $pendientes->sum(
                fn (Recibo $recibo) =>
                    $recibo->montoConMora()
            ),
            2
        );


        /*
         * =========================================================
         * CONTADORES DEL CLIENTE
         * =========================================================
         *
         * Un cliente puede tener más de un contador.
         */
        $contadores = $cliente->contadores ?? collect();

        $contadoresActivos = $contadores->filter(
            fn ($contador) =>
                (bool) $contador->activo
        );


        /*
         * =========================================================
         * SERVICIOS DEL CLIENTE
         * =========================================================
         *
         * Servicio se mantiene únicamente como clasificación
         * informativa del contador.
         */
        $servicios = $contadores
            ->map(
                fn ($contador) =>
                    $contador->servicio?->nombre
            )
            ->filter()
            ->unique()
            ->values();


        /*
         * =========================================================
         * ÚLTIMO RECIBO
         * =========================================================
         */
        $ultimoRecibo = $recibos
            ->sortByDesc('fecha_emision')
            ->first();


        /*
         * =========================================================
         * RESULTADO
         * =========================================================
         */
        return [
            'cliente' =>
                $cliente,

            'estado_clave' =>
                $estadoClave,

            'estado_etiqueta' =>
                $estadoEtiqueta,

            /*
             * Contadores.
             */
            'contadores_total' =>
                $contadores->count(),

            'contadores_activos' =>
                $contadoresActivos->count(),

            /*
             * Servicios informativos.
             */
            'servicios' =>
                $servicios,

            /*
             * Recibos.
             */
            'total_recibos' =>
                $recibos->count(),

            'recibos_pendientes' =>
                $pendientes->count(),

            'recibos_pagados' =>
                $pagados->count(),

            'recibos_anulados' =>
                $anulados->count(),

            /*
             * Valores financieros.
             */
            'monto_pendiente' =>
                $montoPendiente,

            'mora' =>
                $mora,

            'total' =>
                $total,

            /*
             * Referencia al último recibo,
             * útil para futuras acciones en la vista.
             */
            'ultimo_recibo' =>
                $ultimoRecibo,
        ];
    }
}