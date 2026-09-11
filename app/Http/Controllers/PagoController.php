<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Recibo;
use App\Services\Auditoria;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PagoController extends Controller
{
    /**
     * Lista los recibos que todavía están pendientes de pago.
     *
     * Disponible únicamente para:
     * - Administrador
     * - Secretaria
     */
    public function index(Request $request)
    {
        $busqueda = trim((string) $request->input('q', ''));

        /*
         * Cargamos toda la información necesaria para mostrar
         * correctamente el recibo, cliente, contador, servicio
         * y tarifa sin realizar consultas adicionales por fila.
         */
        $recibos = Recibo::with([
                'lectura.contador.cliente',
                'lectura.contador.servicio',
                'tarifa',
            ])
            ->where('estado', 'PENDIENTE')
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {

                    /*
                     * Buscar por número de recibo.
                     */
                    $q->where(
                        'numero_recibo',
                        'like',
                        '%' . $busqueda . '%'
                    );

                    /*
                     * Buscar por cliente.
                     */
                    $q->orWhereHas(
                        'lectura.contador.cliente',
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

                    /*
                     * Buscar por número de contador.
                     */
                    $q->orWhereHas(
                        'lectura.contador',
                        function ($contadorQuery) use ($busqueda) {
                            $contadorQuery->where(
                                'numero_registro',
                                'like',
                                '%' . $busqueda . '%'
                            );
                        }
                    );
                });
            })
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'pagos.index',
            compact(
                'recibos',
                'busqueda'
            )
        );
    }


    /**
     * Muestra el formulario para registrar el pago
     * de un recibo determinado.
     */
    public function create(Recibo $recibo)
    {
        /*
         * Traemos toda la información que necesita
         * la pantalla de cobro.
         */
        $recibo->load([
            'lectura.contador.cliente',
            'lectura.contador.servicio',
            'tarifa',
            'pago',
        ]);

        /*
         * No permitimos volver a cobrar un recibo pagado.
         */
        if ($recibo->estado === 'PAGADO' || $recibo->pago) {
            return redirect()
                ->route('pagos.index')
                ->with(
                    'error',
                    'Este recibo ya fue pagado y no puede registrarse nuevamente.'
                );
        }

        /*
         * Un recibo anulado tampoco puede pagarse.
         */
        if ($recibo->estado === 'ANULADO') {
            return redirect()
                ->route('pagos.index')
                ->with(
                    'error',
                    'El recibo está anulado y no puede recibir pagos.'
                );
        }

        /*
         * Solo trabajamos con recibos pendientes.
         */
        if ($recibo->estado !== 'PENDIENTE') {
            return redirect()
                ->route('pagos.index')
                ->with(
                    'error',
                    'El estado actual del recibo no permite registrar un pago.'
                );
        }

        /*
         * Solo se muestran métodos de pago activos.
         */
        $metodosPago = MetodoPago::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view(
            'pagos.create',
            compact(
                'recibo',
                'metodosPago'
            )
        );
    }


    /**
     * Registra un pago real.
     *
     * Flujo:
     *
     * 1. Validar datos.
     * 2. Bloquear el recibo durante la operación.
     * 3. Verificar que siga pendiente.
     * 4. Verificar que todavía no tenga pago.
     * 5. Calcular nuevamente monto + mora en el servidor.
     * 6. Registrar el pago.
     * 7. Cambiar el recibo a PAGADO.
     * 8. Confirmar todo dentro de una transacción.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'recibo_id' => ['required', 'integer', 'exists:recibos,id'],
            'metodo_pago_id' => [
                'required', 'integer',
                Rule::exists('metodos_pago', 'id')->where('activo', 1),
            ],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ], [
            'metodo_pago_id.exists' => 'El método de pago seleccionado no está disponible.',
        ]);

        try {
            $pago = DB::transaction(function () use ($datos) {
                Auditoria::establecerUsuario();
                $recibo = Recibo::query()->lockForUpdate()->findOrFail($datos['recibo_id']);
                if ($recibo->estado === 'PAGADO' || $recibo->pago()->lockForUpdate()->exists()) {
                    throw new \DomainException('Este recibo ya fue pagado.');
                }
                if ($recibo->estado !== 'PENDIENTE') {
                    throw new \DomainException('El estado actual del recibo no permite registrar el pago.');
                }

                $metodo = MetodoPago::query()->lockForUpdate()->find($datos['metodo_pago_id']);
                if (! $metodo || ! $metodo->activo) {
                    throw new \DomainException('El método de pago seleccionado ya no está disponible.');
                }

                // Una misma fecha determina el recargo y queda registrada en el pago.
                $fechaPago = now();
                $totalPagar = $recibo->montoConMora($fechaPago);
                if (! is_finite($totalPagar) || $totalPagar < 0 || $totalPagar > 9999999999.99) {
                    throw new \DomainException('El total calculado para el recibo no es válido.');
                }
                if ($totalPagar == 0.0) {
                    throw new \DomainException('No se puede registrar un pago de Q0.00. El recibo permanece pendiente; su liquidación requiere definir la política de recibos sin importe.');
                }

                // monto y fecha enviados por el navegador nunca determinan el cobro.
                $pago = Pago::create([
                    'recibo_id' => $recibo->id,
                    'usuario_registro_id' => auth()->id(),
                    'metodo_pago_id' => $metodo->id,
                    'monto' => $totalPagar,
                    'fecha_pago' => $fechaPago,
                    'referencia' => $datos['referencia'] ?? null,
                    'observacion' => $datos['observacion'] ?? null,
                ]);
                $recibo->update(['estado' => 'PAGADO']);

                return $pago;
            }, 3);
        } catch (QueryException $e) {
            // QueryException deriva de RuntimeException: nunca mostrar su texto SQL.
            report($e);
            return redirect()->route('pagos.index')->with(
                'error', 'No se pudo registrar el pago. Verifique que el recibo no haya sido pagado previamente.'
            );
        } catch (\DomainException $e) {
            return redirect()->route('pagos.index')->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('pagos.index')->with(
                'error', 'Ocurrió un problema al registrar el pago. No se guardó información parcial.'
            );
        }

        return redirect()->route('recibos.imprimir', $pago->recibo_id)
            ->with('exito', 'Pago registrado correctamente.');
    }
}
