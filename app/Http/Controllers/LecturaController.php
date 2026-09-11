<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Lectura;
use App\Models\Recibo;
use App\Services\Auditoria;
use App\Services\GeneradorNumeroRecibo;
use App\Services\Redondeo;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LecturaController extends Controller
{
    /**
     * Lista las lecturas registradas.
     *
     * Disponible para:
     * - Administrador
     * - Secretaria
     * - Lector
     */
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->input('q', ''));

        /*
         * Cargamos previamente todas las relaciones utilizadas
         * en la vista para evitar consultas adicionales por fila.
         */
        $lecturas = Lectura::with([
                'contador.cliente',
                'contador.tarifa',
                'contador.servicio',
                'usuarioLector',
                'recibo.tarifa',
            ])
            ->when($busqueda !== '', function ($query) use ($busqueda) {

                /*
                 * Por ahora la búsqueda principal continúa siendo
                 * por número de contador.
                 */
                $query->whereHas(
                    'contador',
                    function ($q) use ($busqueda) {
                        $q->where(
                            'numero_registro',
                            'like',
                            '%' . $busqueda . '%'
                        );
                    }
                );
            })
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view(
            'lecturas.index',
            compact(
                'lecturas',
                'busqueda'
            )
        );
    }

    /**
     * Muestra el formulario para registrar una lectura.
     */
    public function create(Request $request)
    {
        /*
         * Solamente se pueden registrar lecturas sobre
         * contadores activos.
         *
         * Se cargan Cliente, Tarifa y Servicio porque forman
         * parte del contexto operativo del contador.
         */
        $contadores = Contador::where('activo', true)
            ->with([
                'cliente',
                'tarifa',
                'servicio',
            ])
            ->orderBy('numero_registro')
            ->get();

        $contadorSeleccionado = null;
        $lecturaAnterior = null;

        /*
         * Si desde la pantalla se seleccionó previamente
         * un contador, obtenemos sus datos y su última lectura.
         */
        if ($request->filled('contador_id')) {

            $contadorSeleccionado = Contador::where('activo', true)
                ->with([
                    'cliente',
                    'tarifa',
                    'servicio',
                ])
                ->find($request->input('contador_id'));

            if ($contadorSeleccionado) {

                $ultimaLectura = Lectura::where(
                        'contador_id',
                        $contadorSeleccionado->id
                    )
                    ->orderByDesc('periodo')
                    ->orderByDesc('id')
                    ->first();

                $lecturaAnterior = $ultimaLectura?->lectura_actual
                    ?? $contadorSeleccionado->lectura_inicial;
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

    /**
     * Registra una lectura y genera automáticamente
     * el recibo correspondiente.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'contador_id' => ['required', 'integer', 'exists:contadores,id'],
            'periodo' => ['required', 'date_format:Y-m'],
            'lectura_actual' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.999',
                'decimal:0,3',
            ],
            'observacion' => ['nullable', 'string', 'max:255'],
        ], [
            'lectura_actual.min' =>
                'La lectura actual no puede ser negativa.',

            'lectura_actual.decimal' =>
                'La lectura admite como máximo tres decimales.',

            'periodo.date_format' =>
                'El período debe tener formato año-mes.',
        ]);

        try {
            $recibo = DB::transaction(function () use ($datos) {

                /*
                 * Después de beginTransaction,
                 * también si Laravel tuvo que reconectar.
                 */
                Auditoria::establecerUsuario();

                /*
                 * El contador siempre existe, incluso antes
                 * de su primera lectura.
                 *
                 * Su bloqueo serializa lecturas de cualquier
                 * período y cambios de lectura inicial.
                 */
                $contador = Contador::query()
                    ->lockForUpdate()
                    ->findOrFail($datos['contador_id']);

                if (! $contador->activo) {
                    throw ValidationException::withMessages([
                        'contador_id' =>
                            'El contador seleccionado no está activo.',
                    ]);
                }

                $periodoFecha = $datos['periodo'] . '-01';

                $ultimaLectura = $contador->lecturas()
                    ->orderByDesc('periodo')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                /*
                 * No se permiten períodos repetidos
                 * ni anteriores al último registrado.
                 */
                if (
                    $ultimaLectura &&
                    $periodoFecha <= $ultimaLectura->periodo->toDateString()
                ) {
                    throw ValidationException::withMessages([
                        'periodo' =>
                            'El período debe ser posterior a la última lectura registrada ('
                            . $ultimaLectura->periodo->format('m/Y')
                            . '). No se permiten períodos repetidos ni anteriores.',
                    ]);
                }

                /*
                 * Para la primera lectura debe existir
                 * una lectura inicial conocida.
                 */
                if (
                    ! $ultimaLectura &&
                    $contador->lectura_inicial === null
                ) {
                    throw ValidationException::withMessages([
                        'contador_id' =>
                            'Falta la lectura inicial de este contador. '
                            . 'Administrador o Secretaria deben registrarla '
                            . 'antes de la primera lectura.',
                    ]);
                }

                /*
                 * Ambos valores proceden de BD.
                 * Ninguna lectura anterior enviada por navegador
                 * se utiliza para el cálculo.
                 */
                $lecturaAnterior = (float) (
                    $ultimaLectura?->lectura_actual
                    ?? $contador->lectura_inicial
                );

                $lecturaActual = (float) $datos['lectura_actual'];

                if ($lecturaActual < $lecturaAnterior) {
                    throw ValidationException::withMessages([
                        'lectura_actual' =>
                            'La lectura actual no puede ser menor a la anterior ('
                            . $this->formatearM3($lecturaAnterior)
                            . ' m³).',
                    ]);
                }

                $consumo = round(
                    $lecturaActual - $lecturaAnterior,
                    3
                );

                $fechaEmision = now()->toDateString();

                /*
                 * La tarifa asignada identifica el tipo contratado.
                 *
                 * Su versión vigente a la fecha de emisión
                 * queda vinculada al recibo de forma histórica.
                 */
                $contador->setRelation(
                    'tarifa',
                    $contador->tarifa()
                        ->lockForUpdate()
                        ->first()
                );

                $tarifa = $contador->tarifaVigente(
                    $fechaEmision,
                    true
                );

                if (
                    ! $tarifa ||
                    $tarifa->capacidad === null ||
                    (float) $tarifa->capacidad <= 0 ||
                    $tarifa->precio_por_m3 === null ||
                    (float) $tarifa->precio_por_m3 < 0 ||
                    $tarifa->precio_exceso_m3 === null ||
                    (float) $tarifa->precio_exceso_m3 <= 0
                ) {
                    throw ValidationException::withMessages([
                        'contador_id' =>
                            'No existe una tarifa vigente válida para el tipo '
                            . 'de este contador en la fecha de emisión. '
                            . 'Revise vigencia, capacidad y precios.',
                    ]);
                }

                /*
                 * Calcula el importe según el consumo
                 * y la tarifa vigente.
                 */
                [
                    $monto,
                    $observacionRecibo
                ] = $this->calcularMonto(
                    $tarifa,
                    $consumo
                );

                if (
                    ! is_finite($monto) ||
                    $monto < 0 ||
                    $monto > 9999999999.99
                ) {
                    throw ValidationException::withMessages([
                        'lectura_actual' =>
                            'El importe calculado excede el monto permitido '
                            . 'para un recibo.',
                    ]);
                }

                /*
                 * =====================================================
                 * CREACIÓN DE LA LECTURA
                 * =====================================================
                 */
                $lectura = Lectura::create([
                    'contador_id' => $contador->id,
                    'usuario_lector_id' => auth()->id(),
                    'periodo' => $periodoFecha,
                    'fecha_lectura' => $fechaEmision,
                    'lectura_anterior' => $lecturaAnterior,
                    'lectura_actual' => $lecturaActual,
                    'consumo_m3' => $consumo,
                    'observacion' =>
                        $datos['observacion'] ?? null,
                ]);

                /*
                 * =====================================================
                 * AQ-71 - NUMERACIÓN DEFINITIVA DEL RECIBO
                 * =====================================================
                 *
                 * Ejemplo:
                 *
                 * REC-2026-4821-000001
                 *
                 * REC      = tipo de documento
                 * 2026     = año de emisión
                 * 4821     = últimos 4 dígitos del contador
                 * 000001   = correlativo general único
                 *
                 * El generador utiliza lockForUpdate(), por lo que
                 * debe ejecutarse dentro de esta misma transacción.
                 */
                $numeroRecibo = app(
                    GeneradorNumeroRecibo::class
                )->generar(
                    $contador,
                    $fechaEmision
                );

                /*
                 * =====================================================
                 * CREACIÓN DEL RECIBO
                 * =====================================================
                 *
                 * Si esta creación falla, Laravel revierte:
                 *
                 * - la lectura;
                 * - el incremento del correlativo;
                 * - cualquier cambio realizado en esta transacción.
                 */
                return Recibo::create([
                    'lectura_id' => $lectura->id,
                    'tarifa_id' => $tarifa->id,
                    'numero_recibo' => $numeroRecibo,
                    'fecha_emision' => $fechaEmision,
                    'monto' => $monto,
                    'estado' => 'PENDIENTE',
                    'observacion' => $observacionRecibo,
                ]);
            }, 3);

        } catch (ValidationException $e) {

            throw $e;

        } catch (QueryException $e) {

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' =>
                        'No se pudo registrar la lectura y generar el recibo. '
                        . 'Verifique que el período no esté repetido '
                        . 'e inténtelo nuevamente.',
                ]);

        } catch (\Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' =>
                        'Ocurrió un problema al registrar la lectura. '
                        . 'No se guardó información parcial.',
                ]);
        }

        return redirect()
            ->route('lecturas.index')
            ->with([
                'exito' =>
                    'Lectura registrada correctamente. '
                    . 'Se generó el recibo N.° '
                    . $recibo->numero_recibo
                    . '.',

                'recibo_generado_id' =>
                    $recibo->id,
            ]);
    }

    /**
     * Calcula el monto correspondiente al consumo mensual.
     *
     * Hasta la capacidad:
     *
     * consumo × precio normal
     *
     * Si existe exceso:
     *
     * capacidad × precio normal
     * +
     * exceso × precio por exceso
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
         * =========================================================
         * SIN EXCESO
         * =========================================================
         */
        if ($consumo <= $capacidad) {

            $monto = Redondeo::monto(
                $consumo * $precioBase
            );

            $observacion = sprintf(
                'Consumo: %.3f m³. '
                . 'Capacidad: %.3f m³. '
                . 'Sin exceso.',
                $consumo,
                $capacidad
            );

            return [
                $monto,
                $observacion,
            ];
        }

        /*
         * =========================================================
         * CON EXCESO
         * =========================================================
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
            'Consumo: %.3f m³. '
            . 'Capacidad: %.3f m³. '
            . 'Exceso: %.3f m³. '
            . 'Monto exceso: Q%.2f.',
            $consumo,
            $capacidad,
            $consumoExceso,
            $montoExceso
        );

        return [
            $monto,
            $observacion,
        ];
    }

    /**
     * Elimina ceros decimales innecesarios en valores de m³.
     *
     * Ejemplos:
     *
     * 60.000  → 60
     * 60.500  → 60.5
     * 60.125  → 60.125
     */
    private function formatearM3(float $valor): string
    {
        return rtrim(
            rtrim(
                number_format(
                    $valor,
                    3,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
    }
}