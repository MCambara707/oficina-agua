<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Recibo;
use App\Models\Tarifa;
use App\Services\Auditoria;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TarifaController extends Controller
{
    private const CAPACIDAD_POR_PAJA = 60;

    private const TIPOS_BASE = ['1/2 paja', '1 paja', '2 pajas'];

    private const CAMPOS_HISTORICOS = [
        'nombre', 'tipo', 'capacidad', 'precio_por_m3', 'precio_exceso_m3',
        'mora_porcentaje', 'mora_monto_fijo', 'vigente_desde',
    ];

    public function index(Request $request)
    {
        $busqueda = $request->input('q');
        $tarifas = Tarifa::when($busqueda, fn ($query, $valor) => $query->where('nombre', 'like', "%{$valor}%"))
            ->orderByDesc('vigente_desde')->paginate(10)->withQueryString();

        return view('tarifas.index', compact('tarifas', 'busqueda'));
    }

    public function create()
    {
        return view('tarifas.create');
    }

    public function store(Request $request)
    {
        $datos = $this->datosTarifa($request);

        DB::transaction(function () use ($datos) {
            Auditoria::establecerUsuario();
            if ($datos['activo']) {
                // Bloquea las tarifas que podrán cerrarse mientras se genera un recibo.
                $anteriores = Tarifa::where('tipo', $datos['tipo'])
                    ->where('activo', true)->orderBy('id')->lockForUpdate()->get();
                $fechaCierre = Carbon::parse($datos['vigente_desde'])->subDay()->toDateString();

                foreach ($anteriores as $anterior) {
                    $inicio = $anterior->vigente_desde->toDateString();
                    $fin = $anterior->vigente_hasta?->toDateString();

                    if ($inicio === $datos['vigente_desde']) {
                        throw ValidationException::withMessages([
                            'vigente_desde' => 'Ya existe una tarifa activa de este tipo con la misma fecha de inicio.',
                        ]);
                    }

                    if ($inicio > $datos['vigente_desde']) {
                        if (empty($datos['vigente_hasta']) || $datos['vigente_hasta'] >= $inicio) {
                            throw ValidationException::withMessages([
                                'vigente_hasta' => 'La vigencia se superpone con una tarifa posterior. Indique una fecha final anterior a su inicio.',
                            ]);
                        }
                        continue;
                    }

                    if ($fin === null || $fin >= $datos['vigente_desde']) {
                        if (Recibo::where('tarifa_id', $anterior->id)
                            ->whereDate('fecha_emision', '>', $fechaCierre)->exists()) {
                            throw ValidationException::withMessages([
                                'vigente_desde' => 'Esta fecha dejaría fuera de vigencia recibos ya emitidos. Use una fecha posterior al último recibo de la tarifa anterior.',
                            ]);
                        }
                        $anterior->update(['vigente_hasta' => $fechaCierre]);
                    }
                }
            }

            Tarifa::create($datos);
        }, 3);

        return redirect()->route('tarifas.index')->with('exito', 'Tarifa creada correctamente.');
    }

    public function edit(Tarifa $tarifa)
    {
        $tieneRecibos = Recibo::where('tarifa_id', $tarifa->id)->exists();
        $tieneContadores = Contador::where('tarifa_id', $tarifa->id)->exists();

        return view('tarifas.edit', compact('tarifa', 'tieneRecibos', 'tieneContadores'));
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        $datos = $this->datosTarifa($request);

        DB::transaction(function () use ($datos, $tarifa) {
            Auditoria::establecerUsuario();
            // La emisión de recibos toma el mismo bloqueo antes de usar esta tarifa.
            $actual = Tarifa::whereKey($tarifa->id)->lockForUpdate()->firstOrFail();
            $tieneRecibos = Recibo::where('tarifa_id', $actual->id)->exists();
            if ($tieneRecibos && $datos['tipo'] === $actual->tipo) {
                // Una capacidad histórica importada puede diferir de la fórmula actual.
                // No se recibe del formulario y debe conservarse al cerrar o desactivar.
                $datos['capacidad'] = $actual->capacidad;
            }
            $candidata = clone $actual;
            $candidata->fill($datos);

            if ($candidata->isDirty('tipo') && Contador::where('tarifa_id', $actual->id)->exists()) {
                throw ValidationException::withMessages([
                    'tipo_selector' => 'No puede cambiar el tipo de una tarifa asignada a contadores. Cree una nueva tarifa para el tipo correspondiente.',
                ]);
            }

            if ($tieneRecibos && $candidata->isDirty(self::CAMPOS_HISTORICOS)) {
                throw ValidationException::withMessages([
                    'tarifa' => 'Esta tarifa ya fue utilizada en recibos. Su nombre, tipo, capacidad, precios, mora y fecha inicial son históricos: cree una nueva tarifa para cambiarlos.',
                ]);
            }

            if ($tieneRecibos && !empty($datos['vigente_hasta']) &&
                Recibo::where('tarifa_id', $actual->id)
                    ->whereDate('fecha_emision', '>', $datos['vigente_hasta'])->exists()) {
                throw ValidationException::withMessages([
                    'vigente_hasta' => 'La fecha final no puede dejar fuera de vigencia recibos ya emitidos con esta tarifa.',
                ]);
            }

            if ($datos['activo'] && $candidata->isDirty(['tipo', 'activo', 'vigente_desde', 'vigente_hasta'])) {
                $superpuesta = Tarifa::where('tipo', $datos['tipo'])->where('activo', true)
                    ->where('id', '<>', $actual->id)
                    ->where(function ($query) use ($datos) {
                        $query->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $datos['vigente_desde']);
                    })
                    ->when(!empty($datos['vigente_hasta']), fn ($query) => $query->where('vigente_desde', '<=', $datos['vigente_hasta']))
                    ->lockForUpdate()->first();

                if ($superpuesta) {
                    throw ValidationException::withMessages([
                        'vigente_desde' => 'La vigencia se superpone con otra tarifa activa del mismo tipo. Revise las fechas o desactive la tarifa correspondiente.',
                    ]);
                }
            }

            $actual->update($datos);
        }, 3);

        return redirect()->route('tarifas.index')->with('exito', 'Tarifa actualizada correctamente.');
    }

    public function destroy(Tarifa $tarifa)
    {
        try {
            $eliminada = DB::transaction(function () use ($tarifa) {
                Auditoria::establecerUsuario();
                $actual = Tarifa::whereKey($tarifa->id)->lockForUpdate()->firstOrFail();
                if (Contador::where('tarifa_id', $actual->id)->exists() ||
                    Recibo::where('tarifa_id', $actual->id)->exists()) {
                    return false;
                }

                return $actual->delete();
            }, 3);

            return redirect()->route('tarifas.index')->with(
                $eliminada ? 'exito' : 'error',
                $eliminada ? 'Tarifa eliminada correctamente.' : 'No se puede eliminar: la tarifa está en uso. Desactívala en su lugar.'
            );
        } catch (QueryException $e) {
            report($e);
            return redirect()->route('tarifas.index')
                ->with('error', 'No se pudo eliminar la tarifa. Si está en uso, desactívala en su lugar.');
        }
    }

    private function datosTarifa(Request $request): array
    {
        $datos = $this->validarTarifa($request);
        $datos['tipo'] = $this->resolverTipo($datos['tipo_selector'], $datos['cantidad_pajas'] ?? null);
        $datos['capacidad'] = $this->resolverCapacidad($datos['tipo_selector'], $datos['cantidad_pajas'] ?? null);
        $datos['activo'] = $request->boolean('activo');
        $datos['vigente_desde'] = Carbon::parse($datos['vigente_desde'])->toDateString();
        $datos['vigente_hasta'] = empty($datos['vigente_hasta']) ? null : Carbon::parse($datos['vigente_hasta'])->toDateString();
        $datos['mora_porcentaje'] = $datos['mora_porcentaje'] ?? null;
        $datos['mora_monto_fijo'] = $datos['mora_monto_fijo'] ?? null;
        unset($datos['tipo_selector'], $datos['cantidad_pajas']);

        return $datos;
    }
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
