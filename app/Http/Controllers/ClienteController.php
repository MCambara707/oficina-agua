<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Support\DocumentoCliente;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ClienteController extends Controller
{
    /**
     * Lista de clientes.
     *
     * Permite buscar por:
     * - nombre;
     * - DPI;
     * - NIT;
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
                                'nit',
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
     * Consulta orientativa del titular para los formularios de clientes.
     * Incluye clientes inactivos porque su documento también debe ser único.
     */
    public function consultarDocumento(Request $request)
    {
        DocumentoCliente::prepararSolicitud($request);

        $datos = $request->validate(
            DocumentoCliente::reglas(unico: false),
            DocumentoCliente::mensajes()
        );

        $campo = $datos['tipo_documento'];
        $cliente = Cliente::where($campo, $datos[$campo])->first(['nombre']);

        return response()->json(
            $cliente
                ? ['encontrado' => true, 'nombre' => $cliente->nombre]
                : ['encontrado' => false]
        )->header('Cache-Control', 'private, no-store');
    }


    /**
     * Guarda un cliente nuevo.
     */
    public function store(Request $request)
    {
        DocumentoCliente::prepararSolicitud($request);

        $datos = $request->validate(
            [
                ...DocumentoCliente::reglas(),
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
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
                ...DocumentoCliente::mensajes(),
                'nombre.required' =>
                    'El nombre del cliente es obligatorio.',

                'nombre.max' =>
                    'El nombre no puede exceder los 150 caracteres.',

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
        $datos = array_merge($datos, DocumentoCliente::paraGuardar($datos));
        unset($datos['tipo_documento']);

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
        DocumentoCliente::prepararSolicitud($request);

        $datos = $request->validate(
            [
                ...DocumentoCliente::reglas(ignorarCliente: $cliente->id),
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
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
                ...DocumentoCliente::mensajes(),
                'nombre.required' =>
                    'El nombre del cliente es obligatorio.',

                'nombre.max' =>
                    'El nombre no puede exceder los 150 caracteres.',

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
        $datos = array_merge($datos, DocumentoCliente::paraGuardar($datos));
        unset($datos['tipo_documento']);

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
