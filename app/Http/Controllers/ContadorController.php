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
use Illuminate\Validation\ValidationException;

class ContadorController extends Controller
{
    /**
     * Lista los contadores registrados.
     *
     * Permite buscar por:
     * - número de contador;
     * - dirección del servicio;
     * - punto de referencia;
     * - sector;
     * - nombre del cliente;
     * - DPI del cliente.
     */
    public function index(Request $request)
    {
        $busqueda = trim(
            (string) $request->input('q', '')
        );

        $contadores = Contador::with([
                'cliente',
                'tarifa',
                'servicio',
            ])
            ->when(
                $busqueda !== '',
                function ($query) use ($busqueda) {

                    $query->where(
                        function ($q) use ($busqueda) {

                            $q->where(
                                'numero_registro',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            $q->orWhere(
                                'direccion_servicio',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            $q->orWhere(
                                'punto_referencia',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            $q->orWhere(
                                'sector',
                                'like',
                                '%' . $busqueda . '%'
                            );

                            $q->orWhereHas(
                                'cliente',
                                function ($clienteQuery) use ($busqueda) {

                                    $clienteQuery
                                        ->where(
                                            'nombre',
                                            'like',
                                            '%' . $busqueda . '%'
                                        )
                                        ->orWhere(
                                            'dpi',
                                            'like',
                                            '%' . $busqueda . '%'
                                        );
                                }
                            );
                        }
                    );
                }
            )
            ->orderBy('numero_registro')
            ->paginate(10)
            ->withQueryString();

        return view(
            'contadores.index',
            compact(
                'contadores',
                'busqueda'
            )
        );
    }


    /**
     * Muestra el formulario para registrar un contador.
     */
    public function create()
    {
        /*
         * Para nuevas altas únicamente mostramos clientes activos.
         */
        $clientes = Cliente::where('activo', true)
            ->orderBy('nombre')
            ->get();


        /*
         * Solo tarifas activas para nuevas asignaciones.
         */
        $tarifas = Tarifa::where('activo', true)
            ->orderBy('nombre')
            ->orderBy('tipo')
            ->get();


        /*
         * Servicio es una clasificación informativa del contador.
         */
        $servicios = Servicio::where('activo', true)
            ->orderBy('nombre')
            ->get();


        return view(
            'contadores.create',
            compact(
                'clientes',
                'tarifas',
                'servicios'
            )
        );
    }


    /**
     * Guarda un contador nuevo.
     */
    public function store(Request $request)
    {
        $datos = $request->validate(
            [
                'cliente_id' => [
                    'required',
                    'integer',

                    Rule::exists(
                        'clientes',
                        'id'
                    )->where(
                        fn ($query) =>
                            $query->where('activo', 1)
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

                'numero_registro' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique(
                        'contadores',
                        'numero_registro'
                    ),
                ],

                'direccion_servicio' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'lectura_inicial' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999.999'],

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
            ],
            [
                'cliente_id.required' =>
                    'Debe seleccionar un cliente.',

                'cliente_id.exists' =>
                    'El cliente seleccionado no existe o está inactivo.',

                'tarifa_id.required' =>
                    'Debe seleccionar una tarifa.',

                'tarifa_id.exists' =>
                    'La tarifa seleccionada no existe o está inactiva.',

                'servicio_id.exists' =>
                    'El servicio seleccionado no existe o está inactivo.',

                'numero_registro.required' =>
                    'El número de registro del contador es obligatorio.',

                'numero_registro.unique' =>
                    'Ya existe un contador con este número de registro.',

                'numero_registro.max' =>
                    'El número de registro no puede exceder los 50 caracteres.',

                'direccion_servicio.required' =>
                    'La dirección del servicio es obligatoria.',

                'lectura_inicial.min' => 'La lectura inicial no puede ser negativa.',
                'lectura_inicial.numeric' => 'La lectura inicial debe ser un número.',
                'lectura_inicial.decimal' => 'La lectura inicial admite como máximo tres decimales.',
                'lectura_inicial.max' => 'La lectura inicial no puede superar 999999999.999 m³.',

                'direccion_servicio.max' =>
                    'La dirección del servicio no puede exceder los 255 caracteres.',

                'punto_referencia.max' =>
                    'El punto de referencia no puede exceder los 255 caracteres.',

                'sector.max' =>
                    'El sector no puede exceder los 100 caracteres.',

                'foto.image' =>
                    'El archivo seleccionado debe ser una imagen.',

                'foto.mimes' =>
                    'La foto debe estar en formato JPEG, JPG, PNG o WEBP.',

                'foto.max' =>
                    'La foto no puede superar los 2 MB.',
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

        $datos['punto_referencia'] =
            isset($datos['punto_referencia'])
                && trim($datos['punto_referencia']) !== ''
                    ? trim($datos['punto_referencia'])
                    : null;

        $datos['sector'] =
            isset($datos['sector'])
                && trim($datos['sector']) !== ''
                    ? trim($datos['sector'])
                    : null;

        $datos['activo'] =
            $request->boolean('activo');


        /*
         * =========================================================
         * FOTO
         * =========================================================
         */
        unset($datos['foto']);


        try {

            if ($request->hasFile('foto')) {
                $datos['foto_ruta'] = $request->file('foto')->store('contadores', 'public');

                if (! $datos['foto_ruta']) {
                    throw new \RuntimeException('No se pudo almacenar la fotografía del contador.');
                }
            }

            DB::transaction(function () use ($datos) {
                $this->validarAsignaciones($datos);
                Contador::create($datos);
            }, 3);

        } catch (ValidationException $e) {
            $this->eliminarFotoSiExiste(($datos['foto_ruta'] ?? null) ?: null);
            throw $e;

        } catch (\Throwable $e) {

            /*
             * Si la BD falla después de guardar la imagen,
             * eliminamos la foto para evitar archivos huérfanos.
             */
            $this->eliminarFotoSiExiste(($datos['foto_ruta'] ?? null) ?: null);

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar el contador. '
                    . 'Verifique los datos e inténtelo nuevamente.'
                );
        }


        return redirect()
            ->route('contadores.index')
            ->with(
                'exito',
                'Contador creado correctamente.'
            );
    }


    /**
     * Muestra el formulario para editar un contador.
     */
    public function edit(Contador $contador)
    {
        $contador->loadExists('lecturas');

        /*
         * Incluimos:
         *
         * - clientes activos;
         * - cliente actualmente asignado aunque esté inactivo;
         *
         * para no romper registros históricos.
         */
        $clientes = Cliente::where(
                function ($query) use ($contador) {

                    $query
                        ->where('activo', true)
                        ->orWhere(
                            'id',
                            $contador->cliente_id
                        );
                }
            )
            ->orderBy('nombre')
            ->get();


        /*
         * Permitimos visualizar la tarifa actual aunque
         * posteriormente haya sido desactivada.
         */
        $tarifas = Tarifa::where(
                function ($query) use ($contador) {

                    $query
                        ->where('activo', true)
                        ->orWhere(
                            'id',
                            $contador->tarifa_id
                        );
                }
            )
            ->orderBy('nombre')
            ->orderBy('tipo')
            ->get();


        /*
         * Igual comportamiento para Servicio.
         */
        $servicios = Servicio::where(
                function ($query) use ($contador) {

                    $query
                        ->where('activo', true);

                    if ($contador->servicio_id) {
                        $query->orWhere(
                            'id',
                            $contador->servicio_id
                        );
                    }
                }
            )
            ->orderBy('nombre')
            ->get();


        return view(
            'contadores.edit',
            compact(
                'contador',
                'clientes',
                'tarifas',
                'servicios'
            )
        );
    }


    /**
     * Actualiza un contador existente.
     */
    public function update(
        Request $request,
        Contador $contador
    ) {
        $datos = $request->validate(
            [
                'cliente_id' => [
                    'required',
                    'integer',
                    'exists:clientes,id',
                ],

                'tarifa_id' => [
                    'required',
                    'integer',
                    'exists:tarifas,id',
                ],

                'servicio_id' => [
                    'nullable',
                    'integer',
                    'exists:servicios,id',
                ],

                'numero_registro' => [
                    'required',
                    'string',
                    'max:50',

                    Rule::unique(
                        'contadores',
                        'numero_registro'
                    )->ignore($contador->id),
                ],

                'direccion_servicio' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'lectura_inicial' => ['nullable', 'numeric', 'decimal:0,3', 'min:0', 'max:999999999.999'],

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
            ],
            [
                'cliente_id.required' =>
                    'Debe seleccionar un cliente.',

                'cliente_id.exists' =>
                    'El cliente seleccionado no existe.',

                'tarifa_id.required' =>
                    'Debe seleccionar una tarifa.',

                'tarifa_id.exists' =>
                    'La tarifa seleccionada no existe.',

                'servicio_id.exists' =>
                    'El servicio seleccionado no existe.',

                'numero_registro.required' =>
                    'El número de registro del contador es obligatorio.',

                'numero_registro.unique' =>
                    'Ya existe otro contador con este número de registro.',

                'direccion_servicio.required' =>
                    'La dirección del servicio es obligatoria.',

                'lectura_inicial.min' => 'La lectura inicial no puede ser negativa.',
                'lectura_inicial.numeric' => 'La lectura inicial debe ser un número.',
                'lectura_inicial.decimal' => 'La lectura inicial admite como máximo tres decimales.',
                'lectura_inicial.max' => 'La lectura inicial no puede superar 999999999.999 m³.',

                'foto.image' =>
                    'El archivo seleccionado debe ser una imagen.',

                'foto.mimes' =>
                    'La foto debe estar en formato JPEG, JPG, PNG o WEBP.',

                'foto.max' =>
                    'La foto no puede superar los 2 MB.',
            ]
        );


        /*
         * =========================================================
         * NORMALIZAR
         * =========================================================
         */
        $datos['numero_registro'] =
            trim($datos['numero_registro']);

        $datos['direccion_servicio'] =
            trim($datos['direccion_servicio']);

        $datos['punto_referencia'] =
            isset($datos['punto_referencia'])
                && trim($datos['punto_referencia']) !== ''
                    ? trim($datos['punto_referencia'])
                    : null;

        $datos['sector'] =
            isset($datos['sector'])
                && trim($datos['sector']) !== ''
                    ? trim($datos['sector'])
                    : null;

        $datos['activo'] =
            $request->boolean('activo');


        $fotoAnterior = null;
        $fotoNueva = null;


        /*
         * =========================================================
         * NUEVA FOTO
         * =========================================================
         */
        unset($datos['foto']);


        try {

            if ($request->hasFile('foto')) {
                $fotoNueva = $request->file('foto')->store('contadores', 'public') ?: null;

                if (! $fotoNueva) {
                    throw new \RuntimeException('No se pudo almacenar la fotografía del contador.');
                }

                $datos['foto_ruta'] = $fotoNueva;
            }

            $fotoAnterior = DB::transaction(function () use ($contador, $datos) {
                // La primera lectura bloquea esta misma fila antes de calcular.
                $actual = Contador::whereKey($contador->id)->lockForUpdate()->firstOrFail();
                $tieneHistorial = $actual->lecturas()->lockForUpdate()->first(['id']) !== null;
                $fotoAnterior = $actual->foto_ruta;

                $this->validarAsignaciones($datos, $actual);
                $actual->fill($datos);

                if ($tieneHistorial && $actual->isDirty('lectura_inicial')) {
                    throw ValidationException::withMessages([
                        'lectura_inicial' => 'No se puede modificar la lectura inicial de un contador con lecturas registradas.',
                    ]);
                }

                if ($tieneHistorial && $actual->isDirty('cliente_id')) {
                    throw ValidationException::withMessages([
                        'cliente_id' => 'No se puede cambiar el cliente de un contador con historial, porque trasladaría sus recibos y pagos.',
                    ]);
                }

                $actual->save();

                return $fotoAnterior;
            }, 3);

        } catch (ValidationException $e) {
            $this->eliminarFotoSiExiste($fotoNueva);
            throw $e;

        } catch (\Throwable $e) {

            /*
             * Si la actualización falla, eliminamos únicamente
             * la nueva imagen. La anterior permanece intacta.
             */
            $this->eliminarFotoSiExiste($fotoNueva);

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo actualizar el contador. '
                    . 'Verifique los datos e inténtelo nuevamente.'
                );
        }

        // La limpieza posterior al commit nunca debe borrar la imagen vigente.
        if ($fotoNueva && $fotoAnterior !== $fotoNueva) {
            $this->eliminarFotoSiExiste($fotoAnterior);
        }

        return redirect()
            ->route('contadores.index')
            ->with(
                'exito',
                'Contador actualizado correctamente.'
            );
    }


    /**
     * Elimina físicamente un contador únicamente cuando
     * no posee historial de lecturas.
     *
     * Cuando ya existe historial operativo, debe
     * desactivarse en lugar de eliminarse.
     */
    public function destroy(Contador $contador)
    {
        try {
            $resultado = DB::transaction(function () use ($contador) {
                // Usa el mismo bloqueo que la edición y el registro de lecturas.
                $actual = Contador::whereKey($contador->id)->lockForUpdate()->firstOrFail();

                if ($actual->lecturas()->lockForUpdate()->first(['id']) !== null) {
                    return ['eliminado' => false, 'foto' => null];
                }

                // La fotografía pudo cambiar después del enlace inicial de la ruta.
                $fotoRuta = $actual->foto_ruta;

                return ['eliminado' => $actual->delete(), 'foto' => $fotoRuta];
            }, 3);

        } catch (QueryException $e) {

            report($e);


            return redirect()
                ->route('contadores.index')
                ->with(
                    'error',
                    'No se puede eliminar el contador porque '
                    . 'existen registros relacionados. '
                    . 'Desactívelo en su lugar.'
                );
        }

        if (! $resultado['eliminado']) {
            return redirect()->route('contadores.index')->with(
                'error',
                'No se puede eliminar el contador porque tiene lecturas registradas. Desactívelo en su lugar.'
            );
        }

        // Solo después de confirmar la eliminación, limpia la fotografía vigente.
        $this->eliminarFotoSiExiste($resultado['foto']);

        return redirect()->route('contadores.index')
            ->with('exito', 'Contador eliminado correctamente.');
    }

    /**
     * Conserva referencias históricas inactivas; toda asignación nueva debe estar activa.
     * Se ejecuta dentro de la transacción para cubrir cambios posteriores al formulario.
     */
    private function validarAsignaciones(array $datos, ?Contador $contador = null): void
    {
        foreach ([
            'cliente_id' => [Cliente::class, 'El cliente'],
            'tarifa_id' => [Tarifa::class, 'La tarifa'],
            'servicio_id' => [Servicio::class, 'El servicio'],
        ] as $campo => [$modelo, $etiqueta]) {
            $id = $datos[$campo] ?? null;

            if ($id === null || ($contador && (int) $contador->{$campo} === (int) $id)) {
                continue;
            }

            $asignacion = $modelo::whereKey($id)->lockForUpdate()->first();

            if (! $asignacion || ! $asignacion->activo) {
                throw ValidationException::withMessages([
                    $campo => $etiqueta . ' seleccionado no existe o está inactivo. Elija una asignación activa.',
                ]);
            }
        }
    }

    private function eliminarFotoSiExiste(?string $ruta): void
    {
        if (! $ruta) {
            return;
        }

        try {
            Storage::disk('public')->delete($ruta);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
