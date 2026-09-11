<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Recibo;
use App\Services\Redondeo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LecturaController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $lecturas = Lectura::with([
                'contador.cliente',
                'contador.tarifa',
                'usuarioLector',
                'recibo.tarifa',
            ])
            ->when($busqueda, function ($query, $busqueda) {
                return $query->whereHas(
                    'contador',
                    function ($q) use ($busqueda) {
                        $q->where(
                            'numero_registro',
                            'like',
                            "%{$busqueda}%"
                        );
                    }
                );
            })
            ->orderByDesc('periodo')
            ->paginate(10)
            ->withQueryString();

        return view(
            'lecturas.index',
            compact('lecturas', 'busqueda')
        );
    }

    public function create(Request $request)
    {
        $contadores = Contador::where('activo', true)
            ->with('cliente')
            ->orderBy('numero_registro')
            ->get();

        $contadorSeleccionado = null;
        $lecturaAnterior = 0;

        if ($request->filled('contador_id')) {
            $contadorSeleccionado = Contador::with('cliente')
                ->find($request->input('contador_id'));

            if ($contadorSeleccionado) {
                $ultimaLectura = Lectura::where(
                        'contador_id',
                        $contadorSeleccionado->id
                    )
                    ->orderByDesc('periodo')
                    ->first();

                $lecturaAnterior =
                    $ultimaLectura->lectura_actual ?? 0;
            }
        }

        return view(
            'lecturas.create',
            compact(
                'contadores',
                'contadorSeleccionado',
                'lecturaAnterior'
            )
        );
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'contador_id' =>
                'required|exists:contadores,id',

            'periodo' =>
                'required|date_format:Y-m',

            'lectura_actual' =>
                'required|numeric|min:0',

            'observacion' =>
                'nullable|string|max:255',
        ]);

        $contador = Contador::with('tarifa')
            ->find($datos['contador_id']);

        /*
         * Todo contador debe tener una tarifa asignada.
         */
        if (! $contador->tarifa) {
            return back()
                ->withInput()
                ->withErrors([
                    'contador_id' =>
                        'Este contador no tiene una tarifa asignada. Asígnale una tarifa antes de registrar la lectura.',
                ]);
        }

        /*
         * Toda tarifa debe tener capacidad mensual.
         */
        if (
            $contador->tarifa->capacidad === null
            || (float) $contador->tarifa->capacidad <= 0
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contador_id' =>
                        'La tarifa asignada a este contador no tiene una capacidad mensual válida. Actualice la tarifa antes de registrar la lectura.',
                ]);
        }

        /*
         * Toda tarifa debe tener precio por exceso.
         */
        if (
            $contador->tarifa->precio_exceso_m3 === null
            || (float) $contador->tarifa->precio_exceso_m3 <= 0
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'contador_id' =>
                        'La tarifa asignada a este contador no tiene un precio por exceso válido. Actualice la tarifa antes de registrar la lectura.',
                ]);
        }

        /*
         * El período se guarda como el primer día del mes.
         */
        $periodoFecha = $datos['periodo'] . '-01';

        /*
         * La lectura anterior se obtiene directamente
         * desde la base de datos.
         */
        $ultimaLectura = Lectura::where(
                'contador_id',
                $datos['contador_id']
            )
            ->orderByDesc('periodo')
            ->first();

        $lecturaAnterior =
            $ultimaLectura->lectura_actual ?? 0;

        /*
         * La lectura actual no puede disminuir.
         */
        if (
            (float) $datos['lectura_actual']
            < (float) $lecturaAnterior
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' =>
                        'La lectura actual no puede ser menor a la lectura anterior (' .
                        $lecturaAnterior .
                        ').',
                ]);
        }

        /*
         * No se permite más de una lectura por contador
         * para el mismo mes.
         */
        $existeDuplicado = Lectura::where(
                'contador_id',
                $datos['contador_id']
            )
            ->where('periodo', $periodoFecha)
            ->exists();

        if ($existeDuplicado) {
            return back()
                ->withInput()
                ->withErrors([
                    'periodo' =>
                        'Ya existe una lectura registrada para este contador en este período.',
                ]);
        }

        /*
         * Consumo mensual:
         *
         * lectura actual - lectura anterior
         */
        $consumo =
            (float) $datos['lectura_actual']
            - (float) $lecturaAnterior;

        try {
            [$lectura, $recibo] = DB::transaction(
                function () use (
                    $datos,
                    $contador,
                    $periodoFecha,
                    $lecturaAnterior,
                    $consumo
                ) {
                    /*
                     * Registrar lectura.
                     */
                    $lectura = Lectura::create([
                        'contador_id' =>
                            $datos['contador_id'],

                        'usuario_lector_id' =>
                            auth()->id(),

                        'periodo' =>
                            $periodoFecha,

                        'fecha_lectura' =>
                            now()->toDateString(),

                        'lectura_anterior' =>
                            $lecturaAnterior,

                        'lectura_actual' =>
                            $datos['lectura_actual'],

                        'consumo_m3' =>
                            $consumo,

                        'observacion' =>
                            $datos['observacion'] ?? null,
                    ]);

                    /*
                     * Calcular monto normal y exceso.
                     */
                    [
                        $monto,
                        $observacionRecibo
                    ] = $this->calcularMonto(
                        $contador->tarifa,
                        $consumo
                    );

                    /*
                     * Crear recibo automáticamente.
                     */
                    $recibo = Recibo::create([
                        'lectura_id' =>
                            $lectura->id,

                        'tarifa_id' =>
                            $contador->tarifa->id,

                        'numero_recibo' =>
                            sprintf(
                                'REC-%06d',
                                $lectura->id
                            ),

                        'fecha_emision' =>
                            now()->toDateString(),

                        'monto' =>
                            $monto,

                        'estado' =>
                            'PENDIENTE',

                        'observacion' =>
                            $observacionRecibo,
                    ]);

                    return [$lectura, $recibo];
                }
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' =>
                        'No se pudo guardar la lectura. Verifique que el período no esté repetido y que los datos ingresados sean válidos.',
                ]);
        }

        return redirect()
            ->route('lecturas.index')
            ->with(
                'exito',
                'Lectura y recibo N.° ' .
                $recibo->numero_recibo .
                ' registrados correctamente.'
            );
    }

    /**
     * Calcula el monto mensual.
     *
     * Hasta la capacidad:
     * consumo × precio normal.
     *
     * Al superar la capacidad:
     * capacidad × precio normal
     * +
     * exceso × precio por exceso.
     */
    private function calcularMonto(
        $tarifa,
        float $consumo
    ): array {
        $capacidad =
            (float) $tarifa->capacidad;

        $precioBase =
            (float) $tarifa->precio_por_m3;

        $precioExceso =
            (float) $tarifa->precio_exceso_m3;

        /*
         * No existe exceso.
         */
        if ($consumo <= $capacidad) {
            $monto = Redondeo::monto(
                $consumo * $precioBase
            );

            $observacion = sprintf(
                'Consumo: %.3f m³. Capacidad: %.3f m³. Sin exceso.',
                $consumo,
                $capacidad
            );

            return [$monto, $observacion];
        }

        /*
         * Existe exceso.
         */
        $consumoExceso =
            $consumo - $capacidad;

        $montoNormal =
            $capacidad * $precioBase;

        $montoExceso =
            $consumoExceso * $precioExceso;

        $monto = Redondeo::monto(
            $montoNormal + $montoExceso
        );

        $observacion = sprintf(
            'Consumo: %.3f m³. Capacidad: %.3f m³. Exceso: %.3f m³. Monto exceso: Q%.2f.',
            $consumo,
            $capacidad,
            $consumoExceso,
            $montoExceso
        );

        return [$monto, $observacion];
    }
}