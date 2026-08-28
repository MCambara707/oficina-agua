<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    // Listado de clientes
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $clientes = Cliente::when($busqueda, function ($query, $busqueda) {
                return $query->where('nombre', 'like', "%{$busqueda}%")
                             ->orWhere('telefono', 'like', "%{$busqueda}%");
            })
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('clientes.index', compact('clientes', 'busqueda'));
    }

    // Formulario para crear un cliente nuevo
    public function create()
    {
        return view('clientes.create');
    }

    // Guardar el cliente nuevo
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'telefono' => 'nullable|string|max:25',
            'direccion_principal' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        Cliente::create($datos);

        return redirect()
            ->route('clientes.index')
            ->with('exito', 'Cliente creado correctamente.');
    }

    // Formulario para editar un cliente existente
    public function edit(Cliente $cliente)
    {
        return view('clientes.edit', compact('cliente'));
    }

    // Guardar los cambios del cliente
    public function update(Request $request, Cliente $cliente)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:150',
            'telefono' => 'nullable|string|max:25',
            'direccion_principal' => 'nullable|string|max:255',
            'activo' => 'nullable|boolean',
        ]);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        $cliente->update($datos);

        return redirect()
            ->route('clientes.index')
            ->with('exito', 'Cliente actualizado correctamente.');
    }

    // Eliminar un cliente
    public function destroy(Cliente $cliente)
    {
        try {
            $cliente->delete();
            return redirect()
                ->route('clientes.index')
                ->with('exito', 'Cliente eliminado correctamente.');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()
                ->route('clientes.index')
                ->with('error', 'No se puede eliminar: el cliente tiene contadores asociados. Desactívalo en su lugar.');
        }
    }
}