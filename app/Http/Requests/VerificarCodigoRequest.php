<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerificarCodigoRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para verificación de código de 6 dígitos.
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
            'codigo' => [
                'required',
                'string',
                'size:6',
                'regex:/^[0-9]{6}$/',
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
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'codigo.required' => 'Debe ingresar el código de verificación.',
            'codigo.size' => 'El código de verificación debe contener exactamente 6 dígitos.',
            'codigo.regex' => 'El código solo debe contener números.',
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
                'mensaje' => $validator->errors()->first() ?: 'El código ingresado no es válido.',
                'errores' => $validator->errors()
            ], 422)
        );
    }
}
