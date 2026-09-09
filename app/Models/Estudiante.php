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
        'es_estudiante_activo'
    ];
}
