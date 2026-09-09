<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pasante extends Model
{
    protected $table = 'pasantes';

    // La base de datos tiene created_at y updated_at pero tal vez no estables. Mantenemos true para Eloquent.
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

