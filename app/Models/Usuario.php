<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usuario extends Model
{
    protected $table = 'usuarios';
    
    // Como la base de datos sql no define timestamps estándar sino cread_at, desactivamos o mapeamos
    public $timestamps = false;

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo_institucional',
        'password',
        'estado',
        'rol',
        'fecha_registro',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Un usuario tiene un perfil de pasante.
     */
    public function pasante(): HasOne
    {
        return $this->hasOne(Pasante::class, 'usuario_id', 'id');
    }
}
