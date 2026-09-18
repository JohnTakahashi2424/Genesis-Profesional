<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurriculumVitae extends Model
{
    use HasFactory;

    protected $table = 'curriculum_vitae';

    protected $fillable = [
        'usuario_id',
        'titulo_cv',
        'nombre_archivo',
        'ruta_archivo',
        'url_publica',
        'nombre_completo',
        'profesion',
        'foto_url',
        'direccion',
        'email',
        'telefono',
        'sobre_mi',
        'educacion',
        'objetivo',
        'valores',
        'conocimientos',
        'idiomas',
        'certificados',
        'habilidades',
        'logros',
        'proyectos_sociales',
        'color_plantilla',
        'fuente',
        'estado',
    ];

    protected $casts = [
        'educacion' => 'array',
        'valores' => 'array',
        'conocimientos' => 'array',
        'idiomas' => 'array',
        'certificados' => 'array',
        'habilidades' => 'array',
        'logros' => 'array',
        'proyectos_sociales' => 'array',
    ];


    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }
}
