<?php

namespace App\Http\Controllers;

use App\Models\Contador;
use App\Models\Cliente;
use Illuminate\Http\Request;

class ContadorController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $contadores = Contador::with('cliente')
            ->when($busqueda, function ($query, $busqueda) {
                return $query->where('numero_registro', 'like', "%{$busqueda}%")
                             ->orWhere('direccion_servicio', 'like', "%{$busqueda}%");
            })
            ->orderBy('numero_registro')
            ->paginate(10)
            ->withQueryString();

        return view('contadores.index', compact('contadores', 'busqueda'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();
        return view('contadores.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'numero_registro' => 'required|string|max:50|unique:contadores,numero_registro',
            'direccion_servicio' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:150',
            'sector' => 'nullable|string|max:100',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        Contador::create($datos);

        return redirect()
            ->route('contadores.index')
            ->with('exito', 'Contador creado correctamente.');
    }

    public function edit(Contador $contador)
    {
        $clientes = Cliente::orderBy('nombre')->get();
        return view('contadores.edit', compact('contador', 'clientes'));
    }

    public function update(Request $request, Contador $contador)
    {
        $datos = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'numero_registro' => 'required|string|max:50|unique:contadores,numero_registro,' . $contador->id,
            'direccion_servicio' => 'required|string|max:255',
            'referencia' => 'nullable|string|max:150',
            'sector' => 'nullable|string|max:100',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        $contador->update($datos);

        return redirect()
            ->route('contadores.index')
            ->with('exito', 'Contador actualizado correctamente.');
    }

    public function destroy(Contador $contador)
    {
        try {
            $contador->delete();
            return redirect()
                ->route('contadores.index')
                ->with('exito', 'Contador eliminado correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('contadores.index')
                ->with('error', 'No se puede eliminar: el contador tiene lecturas o recibos asociados. Desactívalo en su lugar.');
        }
    }
}
