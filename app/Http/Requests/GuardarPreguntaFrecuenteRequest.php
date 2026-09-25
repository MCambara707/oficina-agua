<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarPreguntaFrecuenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'orden' => $this->filled('orden')
                ? $this->input('orden')
                : 0,

            'activo' => $this->boolean('activo'),
        ]);
    }

    public function rules(): array
    {
        return [
            'pregunta' => [
                'required',
                'string',
                'max:255',
            ],

            'respuesta' => [
                'required',
                'string',
                'max:5000',
            ],

            'orden' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'activo' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'pregunta.required' =>
                'Ingrese la pregunta frecuente.',

            'pregunta.max' =>
                'La pregunta no puede superar los 255 caracteres.',

            'respuesta.required' =>
                'Ingrese la respuesta.',

            'respuesta.max' =>
                'La respuesta no puede superar los 5000 caracteres.',

            'orden.integer' =>
                'El orden debe ser un número entero.',

            'orden.min' =>
                'El orden no puede ser negativo.',
        ];
    }
}