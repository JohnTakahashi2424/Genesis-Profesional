<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pasante extends Model
{
    protected $table = 'pasantes';

    public $timestamps = true;

    protected $fillable = [
        'usuario_id',
        'area',
        'tipo_pasantia',
        'estado',
        'fase_actual',
        'supervisor_id',
        'horas_aprobadas',
    ];

    protected $casts = [
        'horas_aprobadas' => 'decimal:2',
    ];

    /**
     * Un pasante pertenece a un registro de usuario.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    /**
     * Un pasante es supervisado por un miembro del personal administrativo.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(PersonalAdministrativo::class, 'supervisor_id', 'id');
    }
}

