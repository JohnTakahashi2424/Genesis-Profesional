<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reporte extends Model
{
    protected $table = 'reportes';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'pasante_id',
        'area_pasantia',
        'nombre_pasante',
        'mes',
        'fecha_inicio',
        'fecha_fin',
        'objetivo',
        'estado',
        'evidencias',
    ];

    protected $casts = [
        'fecha_inicio' => 'date:Y-m-d',
        'fecha_fin' => 'date:Y-m-d',
        'evidencias' => 'array',
    ];

    protected $appends = [
        'periodo',
        'actividades_desarrolladas',
    ];

    /**
     * Formatear el periodo del reporte legible (Ej: "01 Febr – 27 Febr 2026").
     */
    public function getPeriodoAttribute(): ?string
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        $mesesCortos = [
            1 => 'Ener', 2 => 'Febr', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'
        ];

        $diaInicio = $this->fecha_inicio->format('d');
        $mesInicio = $mesesCortos[(int)$this->fecha_inicio->format('n')] ?? $this->fecha_inicio->format('M');

        $diaFin = $this->fecha_fin->format('d');
        $mesFin = $mesesCortos[(int)$this->fecha_fin->format('n')] ?? $this->fecha_fin->format('M');
        $anioFin = $this->fecha_fin->format('Y');

        return "{$diaInicio} {$mesInicio} – {$diaFin} {$mesFin} {$anioFin}";
    }

    /**
     * Obtener el número de actividades desarrolladas.
     */
    public function getActividadesDesarrolladasAttribute(): int
    {
        if ($this->relationLoaded('actividades')) {
            return $this->actividades->count();
        }

        return $this->actividades()->count();
    }

    /**
     * Relación directa con el usuario de la cuenta (User).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

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
        return $this->hasMany(ActividadReporte::class, 'reporte_id', 'id')->orderBy('numero', 'asc');
    }

    /**
     * Relación muchos a muchos con los informes finales donde este reporte mensual está adjuntado.
     */
    public function informesFinales(): BelongsToMany
    {
        return $this->belongsToMany(
            InformeFinal::class,
            'informe_final_reportes',
            'reporte_id',
            'informe_final_id'
        )->withTimestamps();
    }
}
