<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClienteController extends Controller
{
    /**
     * Lista de clientes.
     *
     * Permite buscar por:
     * - nombre;
     * - DPI;
     * - teléfono.
     */
    public function index(Request $request)
    {
        $busqueda = trim(
            (string) $request->input('q', '')
        );

        $clientes = Cliente::query()
            ->when(
                $busqueda !== '',
                function ($query) use ($busqueda) {

                    $query->where(
                        function ($q) use ($busqueda) {

                            $q->where(
                                'nombre',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            $q->orWhere(
                                'dpi',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            $q->orWhere(
                                'telefono',
                                'like',
                                '%' . $busqueda . '%'
                            );
                        }
                    );
                }
            )
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view(
            'clientes.index',
            compact(
                'clientes',
                'busqueda'
            )
        );
    }


    /**
     * Muestra el formulario para crear un cliente.
     */
    public function create()
    {
        return view('clientes.create');
    }


    /**
     * Guarda un cliente nuevo.
     */
    public function store(Request $request)
    {
        $datos = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'dpi' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('clientes', 'dpi'),
                ],

                'telefono' => [
                    'nullable',
                    'string',
                    'max:25',
                ],

                'direccion_principal' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'activo' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'nombre.required' =>
                    'El nombre del cliente es obligatorio.',

                'nombre.max' =>
                    'El nombre no puede exceder los 150 caracteres.',

                'dpi.required' =>
                    'El DPI del cliente es obligatorio.',

                'dpi.unique' =>
                    'Ya existe un cliente registrado con este DPI.',

                'dpi.max' =>
                    'El DPI no puede exceder los 20 caracteres.',

                'telefono.max' =>
                    'El teléfono no puede exceder los 25 caracteres.',

                'direccion_principal.max' =>
                    'La dirección no puede exceder los 255 caracteres.',
            ]
        );

        /*
         * Checkbox:
         *
         * Si viene marcado → activo = 1
         * Si no viene → activo = 0
         */
        $datos['activo'] = $request->boolean('activo');


        /*
         * Limpiamos espacios innecesarios.
         */
        $datos['nombre'] = trim($datos['nombre']);
        $datos['dpi'] = trim($datos['dpi']);

        $datos['telefono'] =
            isset($datos['telefono'])
                ? trim($datos['telefono'])
                : null;

        $datos['direccion_principal'] =
            isset($datos['direccion_principal'])
                ? trim($datos['direccion_principal'])
                : null;


        Cliente::create($datos);


        return redirect()
            ->route('clientes.index')
            ->with(
                'exito',
                'Cliente creado correctamente.'
            );
    }


    /**
     * Muestra el formulario para editar un cliente.
     */
    public function edit(Cliente $cliente)
    {
        return view(
            'clientes.edit',
            compact('cliente')
        );
    }


    /**
     * Actualiza los datos de un cliente.
     */
    public function update(
        Request $request,
        Cliente $cliente
    ) {
        $datos = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
                ],

                'dpi' => [
                    'required',
                    'string',
                    'max:20',

                    Rule::unique(
                        'clientes',
                        'dpi'
                    )->ignore($cliente->id),
                ],

                'telefono' => [
                    'nullable',
                    'string',
                    'max:25',
                ],

                'direccion_principal' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'activo' => [
                    'nullable',
                    'boolean',
                ],
            ],
            [
                'nombre.required' =>
                    'El nombre del cliente es obligatorio.',

                'nombre.max' =>
                    'El nombre no puede exceder los 150 caracteres.',

                'dpi.required' =>
                    'El DPI del cliente es obligatorio.',

                'dpi.unique' =>
                    'Ya existe otro cliente registrado con este DPI.',

                'dpi.max' =>
                    'El DPI no puede exceder los 20 caracteres.',

                'telefono.max' =>
                    'El teléfono no puede exceder los 25 caracteres.',

                'direccion_principal.max' =>
                    'La dirección no puede exceder los 255 caracteres.',
            ]
        );


        $datos['activo'] = $request->boolean('activo');


        /*
         * Limpiamos espacios innecesarios.
         */
        $datos['nombre'] = trim($datos['nombre']);
        $datos['dpi'] = trim($datos['dpi']);

        $datos['telefono'] =
            isset($datos['telefono'])
                ? trim($datos['telefono'])
                : null;

        $datos['direccion_principal'] =
            isset($datos['direccion_principal'])
                ? trim($datos['direccion_principal'])
                : null;


        $cliente->update($datos);


        return redirect()
            ->route('clientes.index')
            ->with(
                'exito',
                'Cliente actualizado correctamente.'
            );
    }


    /**
     * Elimina físicamente un cliente únicamente si
     * no tiene contadores asociados.
     *
     * Para clientes que ya poseen historial operativo,
     * la práctica recomendada es desactivarlos.
     */
    public function destroy(Cliente $cliente)
    {
        /*
         * Validación a nivel de aplicación.
         */
        if ($cliente->contadores()->exists()) {

            return redirect()
                ->route('clientes.index')
                ->with(
                    'error',
                    'No se puede eliminar el cliente porque tiene '
                    . 'contadores asociados. Desactívelo en su lugar.'
                );
        }


        try {

            $cliente->delete();


            return redirect()
                ->route('clientes.index')
                ->with(
                    'exito',
                    'Cliente eliminado correctamente.'
                );

        } catch (QueryException $e) {

            /*
             * Protección adicional de base de datos.
             */
            report($e);


            return redirect()
                ->route('clientes.index')
                ->with(
                    'error',
                    'No se puede eliminar el cliente porque '
                    . 'existen registros relacionados.'
                );
        }
    }
}