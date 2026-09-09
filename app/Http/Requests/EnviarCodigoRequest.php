<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EnviarCodigoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para solicitud de código de recuperación.
     */
    public function rules(): array
    {
        return [
            'correo' => [
                'required',
                'string',
                'email',
                'max:100',
            ],
        ];
    }

    /**
     * Mensajes de error personalizados.
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'El correo institucional es obligatorio.',
            'correo.string' => 'El correo debe ser una cadena de texto.',
            'correo.email' => 'El correo institucional debe tener un formato válido (ejemplo: usss@000ugb.edu.sv).',
            'correo.max' => 'El correo institucional no puede exceder los 100 caracteres.',
        ];
    }

    /**
     * Respuesta uniforme ante fallo de validación (HTTP 422).
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'mensaje' => $validator->errors()->first('correo') ?: 'Debe ingresar un correo institucional válido.',
                'errores' => $validator->errors()
            ], 422)
        );
    }
}
