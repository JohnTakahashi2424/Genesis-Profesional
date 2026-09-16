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
        'fecha_actividad',
        'objetivo',
        'actividad_realizada',
        'logros_obtenidos',
    ];

    protected $casts = [
        'fecha_actividad' => 'date',
    ];

    /**
     * Relación con el reporte al que pertenece esta actividad.
     */
    public function reporte(): BelongsTo
    {
        return $this->belongsTo(Reporte::class, 'reporte_id', 'id');
    }
}
