<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Tarifa;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContadorController extends Controller
{
    public function index(Request $request)
    {
        $busqueda = $request->input('q');

        $contadores = Contador::with(['cliente', 'tarifa'])
            ->when($busqueda, function ($query, $busqueda) {
                return $query->where(function ($q) use ($busqueda) {
                    $q->where('numero_registro', 'like', "%{$busqueda}%")
                        ->orWhere('direccion_servicio', 'like', "%{$busqueda}%")
                        ->orWhere('punto_referencia', 'like', "%{$busqueda}%")
                        ->orWhere('sector', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('numero_registro')
            ->paginate(10)
            ->withQueryString();

        return view('contadores.index', compact('contadores', 'busqueda'));
    }

    public function create()
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $tarifas = Tarifa::where('activo', true)
            ->orderBy('nombre')
            ->orderBy('tipo')
            ->get();

        return view('contadores.create', compact('clientes', 'tarifas'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'cliente_id' => [
                'required',
                'exists:clientes,id',
            ],

            'tarifa_id' => [
                'required',
                'exists:tarifas,id',
            ],

            'numero_registro' => [
                'required',
                'string',
                'max:50',
                'unique:contadores,numero_registro',
            ],

            'direccion_servicio' => [
                'required',
                'string',
                'max:255',
            ],

            'punto_referencia' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sector' => [
                'nullable',
                'string',
                'max:100',
            ],

            'foto' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        if ($request->hasFile('foto')) {
            $datos['foto_ruta'] = $request
                ->file('foto')
                ->store('contadores', 'public');
        }

        unset($datos['foto']);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        Contador::create($datos);

        return redirect()
            ->route('contadores.index')
            ->with('exito', 'Contador creado correctamente.');
    }

    public function edit(Contador $contador)
    {
        $clientes = Cliente::orderBy('nombre')->get();

        $tarifas = Tarifa::where(function ($query) use ($contador) {
            $query->where('activo', true)
                ->orWhere('id', $contador->tarifa_id);
        })
            ->orderBy('nombre')
            ->orderBy('tipo')
            ->get();

        return view(
            'contadores.edit',
            compact('contador', 'clientes', 'tarifas')
        );
    }

    public function update(Request $request, Contador $contador)
    {
        $datos = $request->validate([
            'cliente_id' => [
                'required',
                'exists:clientes,id',
            ],

            'tarifa_id' => [
                'required',
                'exists:tarifas,id',
            ],

            'numero_registro' => [
                'required',
                'string',
                'max:50',
                'unique:contadores,numero_registro,' . $contador->id,
            ],

            'direccion_servicio' => [
                'required',
                'string',
                'max:255',
            ],

            'punto_referencia' => [
                'nullable',
                'string',
                'max:255',
            ],

            'sector' => [
                'nullable',
                'string',
                'max:100',
            ],

            'foto' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        if ($request->hasFile('foto')) {
            $fotoAnterior = $contador->foto_ruta;

            $datos['foto_ruta'] = $request
                ->file('foto')
                ->store('contadores', 'public');

            if (
                $fotoAnterior &&
                Storage::disk('public')->exists($fotoAnterior)
            ) {
                Storage::disk('public')->delete($fotoAnterior);
            }
        }

        unset($datos['foto']);

        $datos['activo'] = $request->has('activo') ? 1 : 0;

        $contador->update($datos);

        return redirect()
            ->route('contadores.index')
            ->with('exito', 'Contador actualizado correctamente.');
    }

    public function destroy(Contador $contador)
    {
        try {
            $fotoRuta = $contador->foto_ruta;

            $contador->delete();

            if (
                $fotoRuta &&
                Storage::disk('public')->exists($fotoRuta)
            ) {
                Storage::disk('public')->delete($fotoRuta);
            }

            return redirect()
                ->route('contadores.index')
                ->with('exito', 'Contador eliminado correctamente.');
        } catch (QueryException $e) {
            return redirect()
                ->route('contadores.index')
                ->with(
                    'error',
                    'No se puede eliminar: el contador tiene lecturas o recibos asociados. Desactívalo en su lugar.'
                );
        }
    }
}