<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Lectura;
use App\Services\Redondeo;
use Illuminate\Http\Request;

class LecturaController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $lecturas = Lectura::with(['contador.cliente', 'contador.tarifa', 'usuarioLector'])
            ->when($busqueda, function ($query, $busqueda) {
                return $query->whereHas('contador', function ($q) use ($busqueda) {
                    $q->where('numero_registro', 'like', "%{$busqueda}%");
                });
            })
            ->orderByDesc('periodo')
            ->paginate(10)
            ->withQueryString();

        // AQ-28: adjunta la tarifa vigente (según el tipo del contador y la
        // fecha de la lectura) y un monto estimado, para que se vea el
        // resultado de la selección de tarifa directamente en el listado.
        // No incluye exceso sobre la capacidad ni mora todavía — ver la
        // nota en Contador::tarifaVigente().
        $lecturas->through(function ($lectura) {
            $tarifaVigente = $lectura->contador->tarifaVigente($lectura->fecha_lectura);

            $lectura->tarifa_vigente = $tarifaVigente;
            $lectura->monto_estimado = $tarifaVigente
                ? Redondeo::monto($lectura->consumo_m3 * $tarifaVigente->precio_por_m3)
                : null;

            return $lectura;
        });

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

        try {
            Lectura::create([
                'contador_id' => $datos['contador_id'],
                'usuario_lector_id' => auth()->id(),
                'periodo' => $periodoFecha,
                'fecha_lectura' => now()->toDateString(),
                'lectura_anterior' => $lecturaAnterior,
                'lectura_actual' => $datos['lectura_actual'],
                'consumo_m3' => $datos['lectura_actual'] - $lecturaAnterior,
                'observacion' => $datos['observacion'] ?? null,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Red de seguridad: si dos lectores registran al mismo instante, o
            // si algo se escapó de las validaciones de arriba, el UNIQUE o
            // alguno de los CHECK de la base lo va a rechazar aquí.
            return back()
                ->withInput()
                ->withErrors([
                    'lectura_actual' => 'No se pudo guardar la lectura: verifica que el período no esté repetido y que la lectura actual no sea menor a la anterior.',
                ]);
        }

        return redirect()
            ->route('lecturas.index')
            ->with('exito', 'Lectura registrada correctamente.');
    }
}