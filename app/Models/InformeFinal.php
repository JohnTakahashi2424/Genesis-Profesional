<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InformeFinal extends Model
{
    protected $table = 'informes_finales';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'pasante_id',
        'nombre_pasante',
        'institucion',
        'presentacion',
        'generalidades',
        'procedimiento_pasantia',
        'descripcion_empresa',
        'descripcion_pasantia',
        'anexos',
        'presupuesto_columnas',
        'presupuesto_total',
        'estado',
    ];

    protected $casts = [
        'anexos' => 'array',
        'presupuesto_columnas' => 'array',
        'presupuesto_total' => 'decimal:2',
    ];

    protected $appends = [
        'total_reportes_adjuntos',
    ];

    // ─── Accessors ───────────────────────────────────────────────

    /**
     * Obtener la cantidad de reportes mensuales adjuntados.
     */
    public function getTotalReportesAdjuntosAttribute(): int
    {
        if ($this->relationLoaded('reportes')) {
            return $this->reportes->count();
        }

        return $this->reportes()->count();
    }

    // ─── Relaciones ──────────────────────────────────────────────

    /**
     * Relación con el usuario que creó el informe.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación con el pasante asociado al informe.
     */
    public function pasante(): BelongsTo
    {
        return $this->belongsTo(Pasante::class, 'pasante_id', 'id');
    }

    /**
     * Relación muchos a muchos con los reportes mensuales adjuntados.
     */
    public function reportes(): BelongsToMany
    {
        return $this->belongsToMany(
            Reporte::class,
            'informe_final_reportes',
            'informe_final_id',
            'reporte_id'
        )->withTimestamps();
    }

    /**
     * Relación con las filas dinámicas del presupuesto.
     */
    public function presupuestoItems(): HasMany
    {
        return $this->hasMany(PresupuestoItem::class, 'informe_final_id', 'id')->orderBy('orden', 'asc');
    }

    // ─── Métodos de utilidad ─────────────────────────────────────

    /**
     * Recalcular el total general del presupuesto sumando todas las filas.
     */
    public function recalcularPresupuestoTotal(): void
    {
        $this->presupuesto_total = $this->presupuestoItems()->sum('total');
        $this->save();
    }
}

