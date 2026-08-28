<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use Illuminate\Http\Request;

class TarifaController extends Controller
{
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
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'precio_por_m3' => 'required|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        Tarifa::create($datos);

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
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'precio_por_m3' => 'required|numeric|min:0',
            'vigente_desde' => 'required|date',
            'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

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
                ->with('error', 'No se puede eliminar: la tarifa está en uso en algún recibo. Desactívala en su lugar.');
        }
    }
}