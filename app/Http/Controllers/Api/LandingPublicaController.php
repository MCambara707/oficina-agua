<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TarifaPublicaResource;
use App\Models\AvisoPublico;
use App\Models\ConfiguracionLanding;
use App\Models\PreguntaFrecuente;
use App\Models\Tarifa;
use Illuminate\Http\JsonResponse;

class LandingPublicaController extends Controller
{
    /**
     * Tarifas activas y vigentes para la fecha actual.
     */
    public function tarifas(): JsonResponse
    {
        $hoy = now()->toDateString();

        $tarifas = Tarifa::query()
            ->where('activo', true)
            ->whereDate(
                'vigente_desde',
                '<=',
                $hoy
            )
            ->where(function ($query) use ($hoy) {
                $query
                    ->whereNull('vigente_hasta')
                    ->orWhereDate(
                        'vigente_hasta',
                        '>=',
                        $hoy
                    );
            })
            ->orderBy('capacidad')
            ->orderBy('tipo')
            ->get();

        $datos = TarifaPublicaResource::collection(
            $tarifas
        )->resolve();

        return response()->json($datos);
    }

    /**
     * Avisos activos y dentro de su período de publicación.
     */
    public function avisos(): JsonResponse
    {
        $avisos = AvisoPublico::query()
            ->publicados()
            ->orderBy('orden')
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get()
            ->map(function (AvisoPublico $aviso) {
                return [
                    'titulo' => $aviso->titulo,
                    'contenido' => $aviso->contenido,
                    'fecha_inicio' =>
                        $aviso->fecha_inicio?->toDateString(),
                    'fecha_fin' =>
                        $aviso->fecha_fin?->toDateString(),
                ];
            })
            ->values()
            ->all();

        return response()->json($avisos);
    }

    /**
     * Preguntas frecuentes activas.
     */
    public function preguntasFrecuentes(): JsonResponse
    {
        $preguntas = PreguntaFrecuente::query()
            ->activas()
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->map(function (PreguntaFrecuente $pregunta) {
                return [
                    'pregunta' => $pregunta->pregunta,
                    'respuesta' => $pregunta->respuesta,
                ];
            })
            ->values()
            ->all();

        return response()->json($preguntas);
    }

    /**
     * Información institucional pública.
     */
    public function quienesSomos(): JsonResponse
    {
        $configuracion = $this->configuracionActiva();

        if (! $configuracion) {
            return response()->json([]);
        }

        return response()->json([
            'historia' => $configuracion->historia,
            'mision' => $configuracion->mision,
            'vision' => $configuracion->vision,
            'diferenciadores' =>
                $configuracion->diferenciadores,
        ]);
    }

    /**
     * Información pública de contacto.
     */
    public function contacto(): JsonResponse
    {
        $configuracion = $this->configuracionActiva();

        if (! $configuracion) {
            return response()->json([]);
        }

        return response()->json([
            'telefono' => $configuracion->telefono,
            'whatsapp' => $configuracion->whatsapp,
            'correo' => $configuracion->correo,
            'direccion' => $configuracion->direccion,
            'horario' => $configuracion->horario,
        ]);
    }

    /**
     * Obtiene la configuración pública activa.
     */
    private function configuracionActiva(): ?ConfiguracionLanding
    {
        return ConfiguracionLanding::query()
            ->where('activo', true)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }
}