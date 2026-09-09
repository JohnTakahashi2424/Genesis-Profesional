<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pasante extends Model
{
    protected $table = 'pasantes';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'estudiante_id',
        'supervisor_id',
        'area',
        'tipo_pasantia',
        'estado',
        'fase_actual',
        'fase1_curriculum',
        'fase2_aceptado',
        'fase3_practicas',
        'fase4_informe_final',
        'horas_aprobadas',
    ];

    protected $casts = [
        'horas_aprobadas' => 'decimal:2',
    ];

    /**
     * Relación con la cuenta de usuario principal (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con los datos académicos del estudiante (Estudiante).
     */
    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id', 'id');
    }

    /**
     * Relación con el supervisor asignado (User).
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id', 'id');
    }
}
