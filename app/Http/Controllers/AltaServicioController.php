<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Contador;
use App\Models\Servicio;
use App\Models\Tarifa;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AltaServicioController extends Controller
{
    /**
     * Muestra el formulario de alta integral.
     *
     * El flujo permite:
     *
     * - utilizar un cliente existente;
     * - crear un cliente nuevo;
     * - asignar tarifa;
     * - asignar servicio;
     * - registrar el contador;
     * - registrar ubicación y fotografía.
     *
     * No se crea una tabla "altas".
     * Se utilizan las entidades existentes.
     */
    public function create()
    {
        /*
         * Clientes disponibles para nuevas altas.
         */
        $clientes = Cliente::where('activo', true)
            ->orderBy('nombre')
            ->get();


        /*
         * Solo tarifas activas pueden asignarse
         * a una nueva conexión.
         */
        $tarifas = Tarifa::where('activo', true)
            ->orderBy('nombre')
            ->orderBy('tipo')
            ->get();


        /*
         * Servicio funciona únicamente como clasificación
         * informativa del contador.
         */
        $servicios = Servicio::where('activo', true)
            ->orderBy('nombre')
            ->get();


        return view(
            'alta-servicio.create',
            compact(
                'clientes',
                'tarifas',
                'servicios'
            )
        );
    }


    /**
     * Registra una nueva alta de servicio.
     *
     * Puede trabajar con:
     *
     * 1. Cliente existente.
     * 2. Cliente nuevo.
     *
     * La creación del cliente y del contador se realiza
     * dentro de una misma transacción.
     */
    public function store(Request $request)
    {
        /*
         * =========================================================
         * VALIDACIÓN GENERAL
         * =========================================================
         */
        $datos = $request->validate(
            [
                /*
                 * Define qué modalidad de alta se utilizará.
                 */
                'tipo_cliente' => [
                    'required',
                    Rule::in([
                        'existente',
                        'nuevo',
                    ]),
                ],


                /*
                 * =================================================
                 * CLIENTE EXISTENTE
                 * =================================================
                 */
                'cliente_id' => [
                    'nullable',
                    'required_if:tipo_cliente,existente',
                    'integer',

                    Rule::exists(
                        'clientes',
                        'id'
                    )->where(
                        fn ($query) =>
                            $query->where('activo', 1)
                    ),
                ],


                /*
                 * =================================================
                 * CLIENTE NUEVO
                 * =================================================
                 */
                'nombre' => [
                    'nullable',
                    'required_if:tipo_cliente,nuevo',
                    'string',
                    'max:150',
                ],

                'dpi' => [
                    'nullable',
                    'required_if:tipo_cliente,nuevo',
                    'string',
                    'max:20',
                    Rule::unique(
                        'clientes',
                        'dpi'
                    ),
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


                /*
                 * =================================================
                 * DATOS DEL CONTADOR
                 * =================================================
                 */
                'numero_registro' => [
                    'required',
                    'string',
                    'max:50',

                    Rule::unique(
                        'contadores',
                        'numero_registro'
                    ),
                ],

                'tarifa_id' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'tarifas',
                        'id'
                    )->where(
                        fn ($query) =>
                            $query->where('activo', 1)
                    ),
                ],

                'servicio_id' => [
                    'nullable',
                    'integer',

                    Rule::exists(
                        'servicios',
                        'id'
                    )->where(
                        fn ($query) =>
                            $query->where('activo', 1)
                    ),
                ],

                'direccion_servicio' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'lectura_inicial' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999.999'],

                'sector' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'punto_referencia' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'foto' => [
                    'nullable',
                    'image',
                    'mimes:jpeg,jpg,png,webp',
                    'max:2048',
                ],
            ],
            [
                /*
                 * Tipo de cliente.
                 */
                'tipo_cliente.required' =>
                    'Debe indicar si utilizará un cliente existente o registrará uno nuevo.',

                'tipo_cliente.in' =>
                    'La modalidad de cliente seleccionada no es válida.',


                /*
                 * Cliente existente.
                 */
                'cliente_id.required_if' =>
                    'Debe seleccionar un cliente existente.',

                'cliente_id.exists' =>
                    'El cliente seleccionado no existe o se encuentra inactivo.',


                /*
                 * Cliente nuevo.
                 */
                'nombre.required_if' =>
                    'El nombre del cliente es obligatorio.',

                'nombre.max' =>
                    'El nombre del cliente no puede exceder los 150 caracteres.',

                'dpi.required_if' =>
                    'El DPI del cliente es obligatorio.',

                'dpi.unique' =>
                    'Ya existe un cliente registrado con este DPI. '
                    . 'Utilice la opción de cliente existente.',

                'dpi.max' =>
                    'El DPI no puede exceder los 20 caracteres.',

                'telefono.max' =>
                    'El teléfono no puede exceder los 25 caracteres.',

                'direccion_principal.max' =>
                    'La dirección principal no puede exceder los 255 caracteres.',


                /*
                 * Contador.
                 */
                'numero_registro.required' =>
                    'El número de registro del contador es obligatorio.',

                'numero_registro.unique' =>
                    'Ya existe un contador registrado con este número.',

                'numero_registro.max' =>
                    'El número de registro no puede exceder los 50 caracteres.',

                'lectura_inicial.min' => 'La lectura inicial no puede ser negativa.',
                'lectura_inicial.numeric' => 'La lectura inicial debe ser un número.',
                'lectura_inicial.decimal' => 'La lectura inicial admite como máximo tres decimales.',
                'lectura_inicial.max' => 'La lectura inicial no puede superar 999999999.999 m³.',


                /*
                 * Tarifa.
                 */
                'tarifa_id.required' =>
                    'Debe seleccionar una tarifa.',

                'tarifa_id.exists' =>
                    'La tarifa seleccionada no existe o se encuentra inactiva.',


                /*
                 * Servicio.
                 */
                'servicio_id.exists' =>
                    'El servicio seleccionado no existe o se encuentra inactivo.',


                /*
                 * Ubicación.
                 */
                'direccion_servicio.required' =>
                    'La dirección donde se presta el servicio es obligatoria.',

                'direccion_servicio.max' =>
                    'La dirección del servicio no puede exceder los 255 caracteres.',

                'sector.max' =>
                    'El sector no puede exceder los 100 caracteres.',

                'punto_referencia.max' =>
                    'El punto de referencia no puede exceder los 255 caracteres.',


                /*
                 * Fotografía.
                 */
                'foto.image' =>
                    'El archivo seleccionado debe ser una imagen.',

                'foto.mimes' =>
                    'La fotografía debe estar en formato JPEG, JPG, PNG o WEBP.',

                'foto.max' =>
                    'La fotografía no puede superar los 2 MB.',
            ]
        );


        /*
         * =========================================================
         * NORMALIZAR DATOS
         * =========================================================
         */

        $datos['numero_registro'] =
            trim($datos['numero_registro']);

        $datos['direccion_servicio'] =
            trim($datos['direccion_servicio']);


        $datos['sector'] =
            isset($datos['sector'])
            && trim($datos['sector']) !== ''
                ? trim($datos['sector'])
                : null;


        $datos['punto_referencia'] =
            isset($datos['punto_referencia'])
            && trim($datos['punto_referencia']) !== ''
                ? trim($datos['punto_referencia'])
                : null;


        /*
         * Datos de cliente nuevo.
         */
        if ($datos['tipo_cliente'] === 'nuevo') {

            $datos['nombre'] =
                trim($datos['nombre']);

            $datos['dpi'] =
                trim($datos['dpi']);

            $datos['telefono'] =
                isset($datos['telefono'])
                && trim($datos['telefono']) !== ''
                    ? trim($datos['telefono'])
                    : null;

            $datos['direccion_principal'] =
                isset($datos['direccion_principal'])
                && trim($datos['direccion_principal']) !== ''
                    ? trim($datos['direccion_principal'])
                    : null;
        }


        /*
         * =========================================================
         * FOTOGRAFÍA
         * =========================================================
         *
         * Storage no forma parte de la transacción SQL.
         *
         * Por eso conservamos la ruta y, si la operación de BD
         * falla, eliminamos posteriormente el archivo.
         */
        $fotoRuta = null;

        try {

            if ($request->hasFile('foto')) {
                $fotoRuta = $request->file('foto')->store('contadores', 'public') ?: null;

                if (! $fotoRuta) {
                    throw new \RuntimeException('No se pudo almacenar la fotografía del contador.');
                }
            }

            /*
             * =====================================================
             * TRANSACCIÓN DE ALTA
             * =====================================================
             */
            [$cliente, $contador] = DB::transaction(
                function () use (
                    $datos,
                    $fotoRuta
                ) {

                    /*
                     * =================================================
                     * CLIENTE
                     * =================================================
                     */

                    if ($datos['tipo_cliente'] === 'existente') {

                        /*
                         * Volvemos a consultar dentro de la transacción.
                         *
                         * Aunque la validación ya comprobó su existencia,
                         * confirmamos que siga disponible.
                         */
                        $cliente = Cliente::where(
                                'activo',
                                true
                            )
                            ->lockForUpdate()
                            ->findOrFail(
                                $datos['cliente_id']
                            );

                    } else {

                        /*
                         * Registrar nuevo cliente.
                         */
                        $cliente = Cliente::create([
                            'nombre' =>
                                $datos['nombre'],

                            'dpi' =>
                                $datos['dpi'],

                            'telefono' =>
                                $datos['telefono'] ?? null,

                            'direccion_principal' =>
                                $datos['direccion_principal'] ?? null,

                            /*
                             * Toda nueva alta comienza activa.
                             */
                            'activo' =>
                                true,
                        ]);
                    }


                    /*
                     * =================================================
                     * TARIFA
                     * =================================================
                     *
                     * Confirmamos nuevamente que continúa activa.
                     */
                    $tarifa = Tarifa::where(
                            'activo',
                            true
                        )
                        ->lockForUpdate()
                        ->findOrFail(
                            $datos['tarifa_id']
                        );


                    /*
                     * =================================================
                     * SERVICIO
                     * =================================================
                     *
                     * Servicio continúa siendo opcional,
                     * según el modelo actual del proyecto.
                     */
                    $servicioId = null;

                    if (! empty($datos['servicio_id'])) {

                        $servicio = Servicio::where(
                                'activo',
                                true
                            )
                            ->lockForUpdate()
                            ->findOrFail(
                                $datos['servicio_id']
                            );

                        $servicioId =
                            $servicio->id;
                    }


                    /*
                     * =================================================
                     * CONTADOR
                     * =================================================
                     */
                    $contador = Contador::create([
                        'cliente_id' =>
                            $cliente->id,

                        'tarifa_id' =>
                            $tarifa->id,

                        'servicio_id' =>
                            $servicioId,

                        'numero_registro' =>
                            $datos['numero_registro'],

                        'lectura_inicial' =>
                            $datos['lectura_inicial'] ?? null,

                        'direccion_servicio' =>
                            $datos['direccion_servicio'],

                        'sector' =>
                            $datos['sector'] ?? null,

                        'punto_referencia' =>
                            $datos['punto_referencia'] ?? null,

                        'foto_ruta' =>
                            $fotoRuta,

                        /*
                         * Una nueva conexión queda activa.
                         */
                        'activo' =>
                            true,
                    ]);


                    return [
                        $cliente,
                        $contador,
                    ];
                },
                3
            );

        } catch (QueryException $e) {

            /*
             * =========================================================
             * ERROR DE BASE DE DATOS
             * =========================================================
             */
            $this->eliminarFotoSiExiste(
                $fotoRuta
            );


            report($e);


            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo completar el alta del servicio. '
                    . 'No se guardó información parcial. '
                    . 'Verifique los datos e inténtelo nuevamente.'
                );

        } catch (\Throwable $e) {

            /*
             * =========================================================
             * ERROR INESPERADO
             * =========================================================
             */
            $this->eliminarFotoSiExiste(
                $fotoRuta
            );


            report($e);


            return back()
                ->withInput()
                ->with(
                    'error',
                    'Ocurrió un problema al registrar el alta. '
                    . 'La operación fue cancelada completamente.'
                );
        }

        return redirect()
            ->route('contadores.index', ['q' => $contador->numero_registro])
            ->with(
                'exito',
                'Alta registrada correctamente. Cliente: ' . $cliente->nombre
                . '. Contador: ' . $contador->numero_registro . '.'
            );
    }


    /**
     * Elimina una fotografía almacenada cuando la operación
     * de base de datos no pudo completarse.
     */
    private function eliminarFotoSiExiste(
        ?string $fotoRuta
    ): void {
        if (! $fotoRuta) {
            return;
        }

        try {
            Storage::disk('public')->delete($fotoRuta);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
