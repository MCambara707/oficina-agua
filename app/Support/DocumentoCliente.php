<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DocumentoCliente
{
    public static function prepararSolicitud(Request $request): void
    {
        $request->merge(['tipo_documento' => $request->input('tipo_documento', 'dpi')]);

        if (is_string($request->input('dpi'))) {
            $request->merge(['dpi' => trim($request->input('dpi'))]);
        }

        if (is_string($request->input('nit'))) {
            $nit = strtoupper(trim($request->input('nit')));
            $request->merge(['nit' => preg_replace('/(?<=\d)-(?=[0-9K]$)/', '', $nit)]);
        }
    }

    public static function reglas(?int $ignorarCliente = null, bool $unico = true, bool $requerido = true): array
    {
        $reglas = [
            'tipo_documento' => [Rule::excludeIf(! $requerido), 'required', Rule::in(['dpi', 'nit'])],
            'dpi' => [Rule::excludeIf(! $requerido), 'exclude_unless:tipo_documento,dpi', 'bail', 'required', 'string', 'regex:/^[0-9]{13}$/D'],
            'nit' => [Rule::excludeIf(! $requerido), 'exclude_unless:tipo_documento,nit', 'bail', 'required', 'string', 'regex:/^[1-9][0-9]{0,11}[0-9K]$/D'],
        ];

        if ($unico) {
            foreach (['dpi', 'nit'] as $campo) {
                $reglas[$campo][] = Rule::unique('clientes', $campo)->ignore($ignorarCliente);
            }
        }

        return $reglas;
    }

    public static function mensajes(): array
    {
        return [
            'tipo_documento.required' => 'Seleccione DPI o NIT.',
            'tipo_documento.in' => 'El tipo de documento debe ser DPI o NIT.',
            'dpi.required' => 'El DPI del cliente es obligatorio.',
            'dpi.string' => 'El DPI debe contener exactamente 13 números.',
            'dpi.regex' => 'El DPI debe contener exactamente 13 números.',
            'dpi.unique' => 'Ya existe un cliente registrado con este DPI.',
            'nit.required' => 'El NIT del cliente es obligatorio.',
            'nit.string' => 'El NIT debe ser un texto.',
            'nit.regex' => 'El NIT debe tener entre 2 y 13 caracteres, iniciar del 1 al 9 y terminar en un número o K. El guion antes del último carácter es opcional.',
            'nit.unique' => 'Ya existe un cliente registrado con este NIT.',
        ];
    }

    public static function paraGuardar(array $datos): array
    {
        return [
            'dpi' => $datos['tipo_documento'] === 'dpi' ? $datos['dpi'] : null,
            'nit' => $datos['tipo_documento'] === 'nit' ? $datos['nit'] : null,
        ];
    }
}
