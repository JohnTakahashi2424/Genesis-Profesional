<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RestablecerContrasenaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el cambio de contraseña.
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
            ],
            'contrasena' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/[A-Z]/',                     // Al menos una mayúscula
                'regex:/[a-z]/',                     // Al menos una minúscula
                'regex:/[0-9@$!%*?&._#\-]/',          // Un número o símbolo
            ],
            // Soporta tanto 'confirmar_contrasena' como 'contrasena_confirmation'
            'confirmar_contrasena' => [
                'required_without:contrasena_confirmation',
                'nullable',
                'same:contrasena',
            ],
            'contrasena_confirmation' => [
                'required_without:confirmar_contrasena',
                'nullable',
                'same:contrasena',
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
            'correo.email' => 'El formato del correo es inválido.',
            'codigo.required' => 'El código de verificación es obligatorio.',
            'codigo.size' => 'El código debe tener 6 dígitos.',
            'contrasena.required' => 'La nueva contraseña es obligatoria.',
            'contrasena.min' => 'La contraseña debe tener un mínimo de 8 caracteres.',
            'contrasena.max' => 'La contraseña no puede exceder los 100 caracteres.',
            'contrasena.regex' => 'La contraseña debe incluir al menos una mayúscula, una minúscula y un número o símbolo.',
            'confirmar_contrasena.same' => 'Las contraseñas no coinciden.',
            'contrasena_confirmation.same' => 'Las contraseñas no coinciden.',
            'confirmar_contrasena.required_without' => 'Debe confirmar la nueva contraseña.',
            'contrasena_confirmation.required_without' => 'Debe confirmar la nueva contraseña.',
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
                'mensaje' => $validator->errors()->first() ?: 'Los datos proporcionados no cumplen con las reglas de seguridad.',
                'errores' => $validator->errors()
            ], 422)
        );
    }
}
