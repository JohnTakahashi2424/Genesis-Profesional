<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActividadReporte extends Model
{
    protected $table = 'actividades_reporte';

    public $timestamps = true;

    protected $fillable = [
        'reporte_id',
        'numero',
        'actividad',
        'fecha',
        'resultado_aprendizaje',
    ];

    protected $casts = [
        'numero' => 'integer',
        'fecha' => 'date',
    ];

    /**
     * Relación con el reporte al que pertenece esta actividad.
     */
    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class, 'reporte_id', 'id');
    }
}
