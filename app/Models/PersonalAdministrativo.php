<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonalAdministrativo extends Model
{
    protected $table = 'personal_administrativo';
    
    public $timestamps = false;

    protected $fillable = [
        'nombres',
        'apellidos',
        'correo_institucional',
        'password',
        'cargo',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Un supervisor puede supervisar a múltiples pasantes.
     */
    public function pasantes(): HasMany
    {
        return $this->hasMany(Pasante::class, 'supervisor_id', 'id');
    }
}
