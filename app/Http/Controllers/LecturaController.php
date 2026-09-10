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

        $lecturas = Lectura::with(['contador.cliente', 'contador.tarifa', 'usuarioLector', 'recibo'])
            ->when($busqueda, function ($query, $busqueda) {
                return $query->whereHas('contador', function ($q) use ($busqueda) {
                    $q->where('numero_registro', 'like', "%{$busqueda}%");
                });
            })
            ->orderByDesc('periodo')
            ->paginate(10)
            ->withQueryString();

        return view('lecturas.index', compact('lecturas', 'busqueda'));
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
            $contadorSeleccionado = Contador::with('cliente')->find($request->input('contador_id'));

            if ($contadorSeleccionado) {
                $ultimaLectura = Lectura::where('contador_id', $contadorSeleccionado->id)
                    ->orderByDesc('periodo')
                    ->first();

                $lecturaAnterior = $ultimaLectura->lectura_actual ?? 0;
            }
        }

        return view('lecturas.create', compact('contadores', 'contadorSeleccionado', 'lecturaAnterior'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'contador_id' => 'required|exists:contadores,id',
            'periodo' => 'required|date_format:Y-m',
            'lectura_actual' => 'required|numeric|min:0',
            'observacion' => 'nullable|string|max:255',
        ]);

        $contador = Contador::with('tarifa')->find($datos['contador_id']);

        // AQ-66: la tarifa ahora es fija por contador (ver AQ-42). Sin
        // tarifa asignada no se puede calcular el monto del recibo, así
        // que no dejamos ni empezar la operación.
        if (! $contador->tarifa) {
            return back()
                ->withInput()
                ->withErrors([
                    'contador_id' => 'Este contador no tiene una tarifa asignada. Asignale una tarifa antes de registrar la lectura.',
                ]);
        }

        // El período se guarda como el primer día del mes (columna DATE en la tabla).
        $periodoFecha = $datos['periodo'].'-01';

        // La lectura anterior nunca se toma del formulario: se recalcula aquí
        // en el servidor para que nadie pueda manipularla desde el HTML.
        $ultimaLectura = Lectura::where('contador_id', $datos['contador_id'])
            ->orderByDesc('periodo')
            ->first();

        $lecturaAnterior = $ultimaLectura->lectura_actual ?? 0;

        if ((float) $datos['lectura_actual'] < (float) $lecturaAnterior) {
            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' => 'La lectura actual no puede ser menor a la lectura anterior ('.$lecturaAnterior.').',
                ]);
        }

        // (contador_id, periodo) ya existe: mismo contador, mismo mes.
        $existeDuplicado = Lectura::where('contador_id', $datos['contador_id'])
            ->where('periodo', $periodoFecha)
            ->exists();

        if ($existeDuplicado) {
            return back()
                ->withInput()
                ->withErrors([
                    'periodo' => 'Ya existe una lectura registrada para este contador en este período.',
                ]);
        }

        $consumo = (float) $datos['lectura_actual'] - (float) $lecturaAnterior;

        try {
            [$lectura, $recibo] = DB::transaction(function () use ($datos, $contador, $periodoFecha, $lecturaAnterior, $consumo) {
                $lectura = Lectura::create([
                    'contador_id' => $datos['contador_id'],
                    'usuario_lector_id' => auth()->id(),
                    'periodo' => $periodoFecha,
                    'fecha_lectura' => now()->toDateString(),
                    'lectura_anterior' => $lecturaAnterior,
                    'lectura_actual' => $datos['lectura_actual'],
                    'consumo_m3' => $consumo,
                    'observacion' => $datos['observacion'] ?? null,
                ]);

                [$monto, $observacionRecibo] = $this->calcularMonto($contador->tarifa, $consumo);

                $recibo = Recibo::create([
                    'lectura_id' => $lectura->id,
                    'tarifa_id' => $contador->tarifa->id,
                    'numero_recibo' => sprintf('REC-%06d', $lectura->id),
                    'fecha_emision' => now()->toDateString(),
                    'monto' => $monto,
                    'estado' => 'PENDIENTE',
                    'observacion' => $observacionRecibo,
                ]);

                return [$lectura, $recibo];
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Red de seguridad: si dos lectores registran al mismo instante, o
            // si algo se escapó de las validaciones de arriba, el UNIQUE o
            // alguno de los CHECK de la base lo va a rechazar aquí. Al estar
            // dentro de DB::transaction(), si esto falla no queda ni la
            // lectura ni el recibo a medio guardar.
            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' => 'No se pudo guardar la lectura: verifica que el período no esté repetido y que la lectura actual no sea menor a la anterior.',
                ]);
        }

        return redirect()
            ->route('lecturas.index')
            ->with('exito', 'Lectura y recibo N.° '.$recibo->numero_recibo.' registrados correctamente.');
    }

    /**
     * AQ-66 — Calcula el monto del recibo a partir del consumo y la
     * tarifa fija del contador.
     *
     * Si la tarifa no tiene `capacidad` definida, todo el consumo se
     * cobra a `precio_por_m3`. Si la tiene y el consumo se pasa, el
     * excedente se cobra a `precio_exceso_m3`; si ese campo no está
     * configurado en la tarifa, se usa `precio_por_m3` como respaldo
     * (para no bloquear el registro por un dato de tarifa incompleto)
     * y se deja constancia en la observación del recibo.
     *
     * @return array{0: float, 1: ?string} [monto, observacion]
     */
    private function calcularMonto($tarifa, float $consumo): array
    {
        $capacidad = $tarifa->capacidad !== null ? (float) $tarifa->capacidad : null;
        $precioBase = (float) $tarifa->precio_por_m3;

        if ($capacidad === null || $consumo <= $capacidad) {
            $monto = Redondeo::monto($consumo * $precioBase);

            return [$monto, null];
        }

        $consumoDentro = $capacidad;
        $consumoExceso = $consumo - $capacidad;

        $observacion = null;
        $precioExceso = $tarifa->precio_exceso_m3 !== null
            ? (float) $tarifa->precio_exceso_m3
            : $precioBase;

        if ($tarifa->precio_exceso_m3 === null) {
            $observacion = 'Exceso facturado al precio base: la tarifa no tiene precio_exceso_m3 configurado.';
        }

        $monto = Redondeo::monto(
            ($consumoDentro * $precioBase) + ($consumoExceso * $precioExceso)
        );

        return [$monto, $observacion];
    }
}