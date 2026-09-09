<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estudiante extends Model
{
    protected $table = 'estudiantes';

    public $timestamps = true;

    protected $fillable = [
        'codigo_estudiante',
        'nombres',
        'apellidos',
        'genero',
        'estado_civil',
        'dui',
        'direccion',
        'fecha_nacimiento',
        'departamento_nacimiento',
        'municipio_nacimiento',
        'pais',
        'correo_principal',
        'correo_secundario',
        'telefono',
        'celular',
        'es_estudiante_activo',
        'carrera',
    ];

    protected $casts = [
        'es_estudiante_activo' => 'boolean',
        'fecha_nacimiento' => 'date',
    ];

    /**
     * Relación con la cuenta de usuario del sistema.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'correo_institucional', 'correo_secundario');
    }
}
