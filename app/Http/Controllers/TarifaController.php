<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TarifaController extends Controller
{
    /**
     * Regla del sistema:
     * 1 paja equivale a 60 m³ de capacidad mensual.
     */
    private const CAPACIDAD_POR_PAJA = 60;

    private const TIPOS_BASE = [
        '1/2 paja',
        '1 paja',
        '2 pajas',
    ];

    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $tarifas = Tarifa::when(
            $busqueda,
            function ($query, $busqueda) {
                return $query->where(
                    'nombre',
                    'like',
                    "%{$busqueda}%"
                );
            }
        )
            ->orderBy('vigente_desde', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view(
            'tarifas.index',
            compact('tarifas', 'busqueda')
        );
    }

    public function create()
    {
        return view('tarifas.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validarTarifa($request);

        /*
         * Se resuelve el texto que se guardará como tipo.
         */
        $datos['tipo'] = $this->resolverTipo(
            $datos['tipo_selector'],
            $datos['cantidad_pajas'] ?? null
        );

        /*
         * La capacidad NO se recibe del formulario.
         *
         * Se calcula siempre en el servidor según
         * el tipo de paja seleccionado.
         */
        $datos['capacidad'] = $this->resolverCapacidad(
            $datos['tipo_selector'],
            $datos['cantidad_pajas'] ?? null
        );

        unset(
            $datos['tipo_selector'],
            $datos['cantidad_pajas']
        );

        $datos['activo'] = $request->has('activo')
            ? 1
            : 0;

        /*
         * Evita dos tarifas activas del mismo tipo
         * con exactamente la misma fecha de inicio.
         */
        if ($datos['activo']) {
            $existeMismaFecha = Tarifa::where(
                    'tipo',
                    $datos['tipo']
                )
                ->where('activo', true)
                ->whereDate(
                    'vigente_desde',
                    $datos['vigente_desde']
                )
                ->exists();

            if ($existeMismaFecha) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'vigente_desde' =>
                            'Ya existe una tarifa activa de este tipo con la misma fecha de inicio.',
                    ]);
            }
        }

        DB::transaction(function () use ($datos) {
            /*
             * AQ-29:
             *
             * Cuando empieza una nueva tarifa activa
             * del mismo tipo, la tarifa anterior
             * termina un día antes.
             */
            if ($datos['activo']) {
                $fechaCierre = Carbon::parse(
                    $datos['vigente_desde']
                )
                    ->subDay()
                    ->toDateString();

                Tarifa::where(
                        'tipo',
                        $datos['tipo']
                    )
                    ->where('activo', true)
                    ->where(
                        'vigente_desde',
                        '<',
                        $datos['vigente_desde']
                    )
                    ->where(
                        function ($query) use ($datos) {
                            $query
                                ->whereNull('vigente_hasta')
                                ->orWhere(
                                    'vigente_hasta',
                                    '>=',
                                    $datos['vigente_desde']
                                );
                        }
                    )
                    ->update([
                        'vigente_hasta' => $fechaCierre,
                    ]);
            }

            Tarifa::create($datos);
        });

        return redirect()
            ->route('tarifas.index')
            ->with(
                'exito',
                'Tarifa creada correctamente.'
            );
    }

    public function edit(Tarifa $tarifa)
    {
        return view(
            'tarifas.edit',
            compact('tarifa')
        );
    }

    public function update(
        Request $request,
        Tarifa $tarifa
    ) {
        $datos = $this->validarTarifa($request);

        /*
         * Se vuelve a determinar el tipo.
         */
        $datos['tipo'] = $this->resolverTipo(
            $datos['tipo_selector'],
            $datos['cantidad_pajas'] ?? null
        );

        /*
         * También se vuelve a calcular automáticamente
         * la capacidad.
         *
         * Si cambia de 1 paja a 2 pajas, por ejemplo:
         *
         * 60 m³ → 120 m³
         */
        $datos['capacidad'] = $this->resolverCapacidad(
            $datos['tipo_selector'],
            $datos['cantidad_pajas'] ?? null
        );

        unset(
            $datos['tipo_selector'],
            $datos['cantidad_pajas']
        );

        $datos['activo'] = $request->has('activo')
            ? 1
            : 0;

        if ($datos['activo']) {
            $existeMismaFecha = Tarifa::where(
                    'tipo',
                    $datos['tipo']
                )
                ->where('activo', true)
                ->whereDate(
                    'vigente_desde',
                    $datos['vigente_desde']
                )
                ->where(
                    'id',
                    '<>',
                    $tarifa->id
                )
                ->exists();

            if ($existeMismaFecha) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'vigente_desde' =>
                            'Ya existe otra tarifa activa de este tipo con la misma fecha de inicio.',
                    ]);
            }
        }

        $tarifa->update($datos);

        return redirect()
            ->route('tarifas.index')
            ->with(
                'exito',
                'Tarifa actualizada correctamente.'
            );
    }

    public function destroy(Tarifa $tarifa)
    {
        try {
            $tarifa->delete();

            return redirect()
                ->route('tarifas.index')
                ->with(
                    'exito',
                    'Tarifa eliminada correctamente.'
                );
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('tarifas.index')
                ->with(
                    'error',
                    'No se puede eliminar: la tarifa está en uso. Desactívala en su lugar.'
                );
        }
    }

    /**
     * Validaciones del mantenimiento de tarifas.
     */
    private function validarTarifa(
        Request $request
    ): array {
        $datos = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:100',
                ],

                'tipo_selector' => [
                    'required',
                    Rule::in([
                        '1/2 paja',
                        '1 paja',
                        '2 pajas',
                        'otra_cantidad',
                    ]),
                ],

                'cantidad_pajas' => [
                    'nullable',
                    'required_if:tipo_selector,otra_cantidad',
                    'integer',
                    'min:3',
                ],

                /*
                 * Ya NO validamos "capacidad"
                 * porque el usuario no la escribe.
                 *
                 * El sistema la calcula automáticamente.
                 */

                'precio_por_m3' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'precio_exceso_m3' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                /*
                 * Puede configurarse:
                 *
                 * - mora porcentual,
                 * - mora fija,
                 * - o ambas.
                 */
                'mora_porcentaje' => [
                    'nullable',
                    'numeric',
                    'gt:0',
                    'max:100',
                ],

                'mora_monto_fijo' => [
                    'nullable',
                    'numeric',
                    'gt:0',
                ],

                'vigente_desde' => [
                    'required',
                    'date',
                ],

                'vigente_hasta' => [
                    'nullable',
                    'date',
                    'after_or_equal:vigente_desde',
                ],

                'activo' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'cantidad_pajas.required_if' =>
                    'Debe indicar la cantidad de pajas.',

                'cantidad_pajas.integer' =>
                    'La cantidad de pajas debe ser un número entero.',

                'cantidad_pajas.min' =>
                    'Para 1/2 paja, 1 paja o 2 pajas utilice las opciones del listado.',

                'precio_por_m3.required' =>
                    'Debe indicar el precio normal por m³.',

                'precio_por_m3.numeric' =>
                    'El precio por m³ debe ser un valor numérico.',

                'precio_por_m3.gt' =>
                    'El precio por m³ debe ser mayor que cero.',

                'precio_exceso_m3.required' =>
                    'Debe indicar el precio por m³ de exceso.',

                'precio_exceso_m3.numeric' =>
                    'El precio por exceso debe ser un valor numérico.',

                'precio_exceso_m3.gt' =>
                    'El precio por exceso debe ser mayor que cero.',

                'mora_porcentaje.numeric' =>
                    'El porcentaje de mora debe ser un valor numérico.',

                'mora_porcentaje.gt' =>
                    'El porcentaje de mora debe ser mayor que cero.',

                'mora_porcentaje.max' =>
                    'El porcentaje de mora no puede ser mayor al 100%.',

                'mora_monto_fijo.numeric' =>
                    'El monto fijo de mora debe ser un valor numérico.',

                'mora_monto_fijo.gt' =>
                    'El monto fijo de mora debe ser mayor que cero.',

                'vigente_hasta.after_or_equal' =>
                    'La fecha de finalización no puede ser anterior a la fecha de inicio.',
            ]
        );

        /*
         * Todas las tarifas deben tener configurada
         * al menos una forma de mora.
         */
        $tieneMoraPorcentaje =
            isset($datos['mora_porcentaje'])
            && $datos['mora_porcentaje'] !== null;

        $tieneMoraFija =
            isset($datos['mora_monto_fijo'])
            && $datos['mora_monto_fijo'] !== null;

        if (
            ! $tieneMoraPorcentaje
            && ! $tieneMoraFija
        ) {
            throw ValidationException::withMessages([
                'mora_porcentaje' =>
                    'Debe configurar al menos una mora: porcentaje, monto fijo o ambos.',
            ]);
        }

        return $datos;
    }

    /**
     * Convierte la selección del formulario
     * al texto que se almacena en la BD.
     */
    private function resolverTipo(
        string $tipoSelector,
        $cantidadPajas
    ): string {
        if (
            in_array(
                $tipoSelector,
                self::TIPOS_BASE,
                true
            )
        ) {
            return $tipoSelector;
        }

        return ((int) $cantidadPajas)
            . ' pajas';
    }

    /**
     * Calcula automáticamente la capacidad mensual.
     *
     * 1/2 paja = 30 m³
     * 1 paja   = 60 m³
     * 2 pajas  = 120 m³
     * N pajas  = N × 60 m³
     */
    private function resolverCapacidad(
        string $tipoSelector,
        $cantidadPajas
    ): float {
        return match ($tipoSelector) {
            '1/2 paja' =>
                self::CAPACIDAD_POR_PAJA / 2,

            '1 paja' =>
                self::CAPACIDAD_POR_PAJA,

            '2 pajas' =>
                self::CAPACIDAD_POR_PAJA * 2,

            'otra_cantidad' =>
                ((int) $cantidadPajas)
                * self::CAPACIDAD_POR_PAJA,

            default =>
                throw ValidationException::withMessages([
                    'tipo_selector' =>
                        'El tipo de tarifa seleccionado no es válido.',
                ]),
        };
    }
}