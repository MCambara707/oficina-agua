<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TarifaController extends Controller
{
    private const TIPOS_BASE = [
        '1/2 paja',
        '1 paja',
        '2 pajas',
    ];

    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $tarifas = Tarifa::when($busqueda, function ($query, $busqueda) {
                return $query->where('nombre', 'like', "%{$busqueda}%");
            })
            ->orderBy('vigente_desde', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('tarifas.index', compact('tarifas', 'busqueda'));
    }

    public function create()
    {
        return view('tarifas.create');
    }

    public function store(Request $request)
    {
        $datos = $this->validarTarifa($request);

        $datos['tipo'] = $this->resolverTipo(
            $datos['tipo_selector'],
            $datos['cantidad_pajas'] ?? null
        );

        unset(
            $datos['tipo_selector'],
            $datos['cantidad_pajas']
        );

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        /*
         * Evita dos tarifas activas del mismo tipo
         * con exactamente la misma fecha de inicio.
         */
        if ($datos['activo']) {
            $existeMismaFecha = Tarifa::where('tipo', $datos['tipo'])
                ->where('activo', true)
                ->whereDate('vigente_desde', $datos['vigente_desde'])
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
             * Cuando comienza una nueva tarifa activa del mismo tipo,
             * la vigencia anterior termina un día antes.
             */
            if ($datos['activo']) {
                $fechaCierre = Carbon::parse($datos['vigente_desde'])
                    ->subDay()
                    ->toDateString();

                Tarifa::where('tipo', $datos['tipo'])
                    ->where('activo', true)
                    ->where('vigente_desde', '<', $datos['vigente_desde'])
                    ->where(function ($query) use ($datos) {
                        $query->whereNull('vigente_hasta')
                            ->orWhere(
                                'vigente_hasta',
                                '>=',
                                $datos['vigente_desde']
                            );
                    })
                    ->update([
                        'vigente_hasta' => $fechaCierre,
                    ]);
            }

            Tarifa::create($datos);
        });

        return redirect()
            ->route('tarifas.index')
            ->with('exito', 'Tarifa creada correctamente.');
    }

    public function edit(Tarifa $tarifa)
    {
        return view('tarifas.edit', compact('tarifa'));
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        $datos = $this->validarTarifa($request);

        $datos['tipo'] = $this->resolverTipo(
            $datos['tipo_selector'],
            $datos['cantidad_pajas'] ?? null
        );

        unset(
            $datos['tipo_selector'],
            $datos['cantidad_pajas']
        );

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        if ($datos['activo']) {
            $existeMismaFecha = Tarifa::where('tipo', $datos['tipo'])
                ->where('activo', true)
                ->whereDate('vigente_desde', $datos['vigente_desde'])
                ->where('id', '<>', $tarifa->id)
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
            ->with('exito', 'Tarifa actualizada correctamente.');
    }

    public function destroy(Tarifa $tarifa)
    {
        try {
            $tarifa->delete();

            return redirect()
                ->route('tarifas.index')
                ->with('exito', 'Tarifa eliminada correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('tarifas.index')
                ->with(
                    'error',
                    'No se puede eliminar: la tarifa está en uso. Desactívala en su lugar.'
                );
        }
    }

    private function validarTarifa(Request $request): array
    {
        return $request->validate(
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

                'precio_por_m3' => [
                    'required',
                    'numeric',
                    'min:0',
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
            ]
        );
    }

    private function resolverTipo(
        string $tipoSelector,
        $cantidadPajas
    ): string {
        if (in_array($tipoSelector, self::TIPOS_BASE, true)) {
            return $tipoSelector;
        }

        return ((int) $cantidadPajas) . ' pajas';
    }
}