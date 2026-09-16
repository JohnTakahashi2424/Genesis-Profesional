<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reporte extends Model
{
    protected $table = 'reportes';

    public $timestamps = true;

    protected $fillable = [
        'pasante_id',
        'nombre_reporte',
        'fecha_inicio',
        'fecha_fin',
        'horas_registradas',
        'estado',
        'evidencias',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'horas_registradas' => 'integer',
        'evidencias' => 'array',
    ];

    /**
     * Relación con el pasante que creó el reporte.
     */
    public function pasante(): BelongsTo
    {
        return $this->belongsTo(Pasante::class, 'pasante_id', 'id');
    }

    /**
     * Relación con las actividades asociadas a este reporte.
     */
    public function actividades(): HasMany
    {
        return $this->hasMany(ActividadReporte::class, 'reporte_id', 'id');
    }
}
