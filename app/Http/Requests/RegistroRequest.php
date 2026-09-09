<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegistroRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'nombres' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u',
            ],
            'apellidos' => [
                'required',
                'string',
                'min:2',
                'max:50',
                'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u',
            ],
            'correo' => [
                'required',
                'string',
                'email',
                'max:100',
                'unique:users,correo_institucional',
            ],
            'contrasena' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'regex:/[A-Z]/',          // Requiere al menos una mayúscula
                'regex:/[a-z]/',          // Requiere al menos una minúscula
                'regex:/[0-9]/',          // Requiere al menos un número
                'regex:/[@$!%*?&._#\-]/', // Requiere al menos un carácter especial
            ],
        ];
    }

    /**
     * Mensajes de error personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'nombres.required' => 'El campo nombres es obligatorio.',
            'nombres.string' => 'El campo nombres debe ser una cadena de texto.',
            'nombres.min' => 'El campo nombres debe tener al menos 2 caracteres.',
            'nombres.max' => 'El campo nombres no puede exceder los 50 caracteres.',
            'nombres.regex' => 'El campo nombres solo puede contener letras y espacios. No se permiten números ni caracteres especiales.',

            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'apellidos.string' => 'El campo apellidos debe ser una cadena de texto.',
            'apellidos.min' => 'El campo apellidos debe tener al menos 2 caracteres.',
            'apellidos.max' => 'El campo apellidos no puede exceder los 50 caracteres.',
            'apellidos.regex' => 'El campo apellidos solo puede contener letras y espacios. No se permiten números ni caracteres especiales.',

            'correo.required' => 'El campo correo es obligatorio.',
            'correo.string' => 'El campo correo debe ser una cadena de texto.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'correo.max' => 'El campo correo no puede exceder los 100 caracteres.',
            // Mensaje de protección anti-phishing/enumeración de usuarios
            'correo.unique' => 'No es posible procesar el registro con este correo. Verifique la información o intente iniciar sesión.',

            'contrasena.required' => 'La contraseña es obligatoria.',
            'contrasena.string' => 'La contraseña debe ser una cadena de texto.',
            'contrasena.min' => 'La contraseña debe tener una longitud mínima de 8 caracteres.',
            'contrasena.max' => 'La contraseña no puede exceder los 100 caracteres.',
            'contrasena.regex' => 'La contraseña debe ser robusta: requiere al menos una letra mayúscula, una letra minúscula, un número y un carácter especial (@, $, !, %, *, ?, &, ., _, #, -).',
        ];
    }

    /**
     * Manejar una falla de validación y retornar una respuesta JSON 422 uniforme.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'mensaje' => 'Los datos proporcionados no cumplen con los requisitos de validación.',
                'errores' => $validator->errors()
            ], 422)
        );
    }
}
