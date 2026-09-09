<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para las credenciales de inicio de sesión.
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
            'contrasena' => [
                'required',
                'string',
            ],
        ];
    }

    /**
     * Mensajes de error personalizados para las reglas de validación.
     */
    public function messages(): array
    {
        return [
            'correo.required' => 'Debe completar todos los campos para continuar',
            'correo.string' => 'El correo institucional debe ser una cadena de texto.',
            'correo.email' => 'El correo institucional debe tener un formato válido (ejemplo: usss@000ugb.edu.sv).',
            'correo.max' => 'El correo institucional no puede exceder los 100 caracteres.',

            'contrasena.required' => 'Debe completar todos los campos para continuar',
            'contrasena.string' => 'La contraseña debe ser una cadena de texto.',
        ];
    }

    /**
     * Manejar fallos de validación respondiendo con JSON uniforme según el diseño de interfaz.
     */
    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();
        $failed = $validator->failed();

        // Si falta algún campo obligatorio (según UI: "Debe completar todos los campos para continuar")
        $mensaje = 'Debe completar todos los campos para continuar';

        // Si ambos campos fueron provistos pero el correo tiene formato sintáctico inválido
        if (!isset($failed['correo']['Required']) && !isset($failed['contrasena']['Required']) && isset($failed['correo']['Email'])) {
            $mensaje = 'El formato del correo institucional es inválido.';
        }

        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'mensaje' => $mensaje,
                'errores' => $errors
            ], 422)
        );
    }
}
