<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $servicios = Servicio::when($busqueda, function ($query, $busqueda) {
                return $query->where('nombre', 'like', "%{$busqueda}%");
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('servicios.index', compact('servicios', 'busqueda'));
    }

    public function create()
    {
        return view('servicios.create');
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100|unique:servicios,nombre',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        Servicio::create($datos);

        return redirect()
            ->route('servicios.index')
            ->with('exito', 'Servicio creado correctamente.');
    }

    public function edit(Servicio $servicio)
    {
        return view('servicios.edit', compact('servicio'));
    }

    public function update(Request $request, Servicio $servicio)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100|unique:servicios,nombre,' . $servicio->id,
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        $servicio->update($datos);

        return redirect()
            ->route('servicios.index')
            ->with('exito', 'Servicio actualizado correctamente.');
    }

    public function destroy(Servicio $servicio)
    {
        try {
            $servicio->delete();
            return redirect()
                ->route('servicios.index')
                ->with('exito', 'Servicio eliminado correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('servicios.index')
                ->with('error', 'No se puede eliminar: hay contadores usando este servicio. Desactívalo en su lugar.');
        }
    }
}