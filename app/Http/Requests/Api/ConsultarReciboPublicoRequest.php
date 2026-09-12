<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ConsultarReciboPublicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dpi' => trim((string) $this->input('dpi')),
            'numero_contador' => trim(
                (string) $this->input('numero_contador')
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'dpi' => [
                'required',
                'string',
                'regex:/^\d{13}$/',
            ],

            'numero_contador' => [
                'required',
                'string',
                'max:50',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'dpi.required' =>
                'El DPI es obligatorio.',

            'dpi.regex' =>
                'El DPI debe contener exactamente 13 dígitos.',

            'numero_contador.required' =>
                'El número de contador es obligatorio.',

            'numero_contador.max' =>
                'El número de contador no puede superar los 50 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'dpi' => 'DPI',
            'numero_contador' => 'número de contador',
        ];
    }
}