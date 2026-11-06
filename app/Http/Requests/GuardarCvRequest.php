<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarCvRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para guardar el CV.
     */
    public function rules(): array
    {
        return [
            'usuario_id'  => 'nullable|integer',
            'pdf_base64'  => 'nullable|string',
            'titulo_cv'   => 'nullable|string|max:255',
            'cv_id'       => 'nullable|integer',
            'perfil'      => 'nullable|array',
            'objetivos'   => 'nullable|array',
            'logros'      => 'nullable|array',
            'diseno'      => 'nullable|array',
        ];
    }
}

