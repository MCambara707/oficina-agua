<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActualizarConfiguracionLandingRequest extends FormRequest
{
    /**
     * Determina si el usuario puede realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara los datos antes de ejecutar las validaciones.
     */
    protected function prepareForValidation(): void
    {
        /*
         * El campo "activo" únicamente se procesa cuando
         * realmente viene incluido en el formulario.
         *
         * Actualmente lo utiliza Información principal,
         * pero Quiénes somos y Contacto no deben modificarlo.
         */
        if ($this->has('activo')) {
            $this->merge([
                'activo' => $this->boolean('activo'),
            ]);
        }
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        return [
            'titulo_principal' => [
                'nullable',
                'string',
                'max:150',
            ],

            'descripcion_principal' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'historia' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'mision' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'vision' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'diferenciadores' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:30',
            ],

            'whatsapp' => [
                'nullable',
                'string',
                'max:30',
            ],

            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'horario' => [
                'nullable',
                'string',
                'max:255',
            ],

            'activo' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Mensajes personalizados de validación.
     */
    public function messages(): array
    {
        return [
            'titulo_principal.max' =>
                'El título principal no puede superar los 150 caracteres.',

            'descripcion_principal.max' =>
                'La descripción principal no puede superar los 3000 caracteres.',

            'historia.max' =>
                'La historia no puede superar los 5000 caracteres.',

            'mision.max' =>
                'La misión no puede superar los 5000 caracteres.',

            'vision.max' =>
                'La visión no puede superar los 5000 caracteres.',

            'diferenciadores.max' =>
                'Los diferenciadores no pueden superar los 5000 caracteres.',

            'telefono.max' =>
                'El teléfono no puede superar los 30 caracteres.',

            'whatsapp.max' =>
                'El número de WhatsApp no puede superar los 30 caracteres.',

            'correo.email' =>
                'Ingrese un correo electrónico válido.',

            'correo.max' =>
                'El correo no puede superar los 150 caracteres.',

            'direccion.max' =>
                'La dirección no puede superar los 1000 caracteres.',

            'horario.max' =>
                'El horario no puede superar los 255 caracteres.',

            'activo.boolean' =>
                'El estado de publicación no es válido.',
        ];
    }
}