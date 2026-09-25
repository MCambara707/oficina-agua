<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarAvisoPublicoRequest extends FormRequest
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
            'titulo' => [
                'required',
                'string',
                'max:150',
            ],

            'contenido' => [
                'required',
                'string',
                'max:5000',
            ],

            'fecha_inicio' => [
                'nullable',
                'date',
            ],

            'fecha_fin' => [
                'nullable',
                'date',
                'after_or_equal:fecha_inicio',
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
            'titulo.required' =>
                'Ingrese el título del aviso.',

            'titulo.max' =>
                'El título no puede superar los 150 caracteres.',

            'contenido.required' =>
                'Ingrese el contenido del aviso.',

            'contenido.max' =>
                'El contenido no puede superar los 5000 caracteres.',

            'fecha_inicio.date' =>
                'La fecha de inicio no es válida.',

            'fecha_fin.date' =>
                'La fecha de finalización no es válida.',

            'fecha_fin.after_or_equal' =>
                'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',

            'orden.integer' =>
                'El orden debe ser un número entero.',

            'orden.min' =>
                'El orden no puede ser negativo.',

            'orden.max' =>
                'El orden no puede ser mayor a 9999.',

            'activo.boolean' =>
                'El estado del aviso no es válido.',
        ];
    }
}