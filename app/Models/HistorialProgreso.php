<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialProgreso extends Model
{
    use HasFactory;

    protected $table = 'historial_progreso';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'categoria',
        'titulo',
        'descripcion',
        'fecha_exacta',
        'accion_texto',
        'accion_url',
        'progreso',
    ];

    protected $casts = [
        'fecha_exacta' => 'datetime',
        'progreso' => 'integer',
    ];

    protected $appends = [
        'fecha_agrupada',
        'fecha_hora_formateada',
        'color_indicador',
    ];

    /**
     * Relación con el usuario propietario de este registro de progreso.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Scope para filtrar únicamente los registros del usuario en sesión.
     */
    public function scopeParaUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por categoría o indicador.
     */
    public function scopePorCategoria(Builder $query, ?string $categoria): Builder
    {
        if (!empty($categoria) && $categoria !== 'todos') {
            return $query->where('categoria', $categoria);
        }

        return $query;
    }

    /**
     * Retorna la fecha agrupada en español (Ej: '01 de marzo de 2026').
     */
    public function getFechaAgrupadaAttribute(): string
    {
        if (!$this->fecha_exacta) {
            return '';
        }

        return Carbon::parse($this->fecha_exacta)
            ->locale('es')
            ->isoFormat('DD [de] MMMM [de] YYYY');
    }

    /**
     * Retorna el día y hora exacto formateado (Ej: '01 de marzo de 2026 - 08:16:03 a.m.').
     */
    public function getFechaHoraFormateadaAttribute(): string
    {
        if (!$this->fecha_exacta) {
            return '';
        }

        $carbon = Carbon::parse($this->fecha_exacta)->locale('es');
        $fecha = $carbon->isoFormat('DD [de] MMMM [de] YYYY');
        $hora = $carbon->isoFormat('hh:mm:ss a');

        return "{$fecha} - {$hora}";
    }

    /**
     * Retorna el color y borde asignado según el indicador de la maqueta.
     */
    public function getColorIndicadorAttribute(): array
    {
        return match ($this->categoria) {
            'cv' => [
                'etiqueta' => 'Actividad de creación / actualización de CV',
                'border_class' => 'border-rose-400',
                'bg_badge' => 'bg-rose-50 text-rose-700',
            ],
            'reportes' => [
                'etiqueta' => 'Actividad de creación/envío y observaciones de reportes',
                'border_class' => 'border-indigo-600',
                'bg_badge' => 'bg-indigo-50 text-indigo-700',
            ],
            'anexos' => [
                'etiqueta' => 'Actividad de anexos a reportes',
                'border_class' => 'border-amber-500',
                'bg_badge' => 'bg-amber-50 text-amber-700',
            ],
            'reportes_rechazados' => [
                'etiqueta' => 'Actividad de reportes rechazados',
                'border_class' => 'border-red-500',
                'bg_badge' => 'bg-red-50 text-red-700',
            ],
            'reportes_aprobados' => [
                'etiqueta' => 'Actividad de reportes aprobados',
                'border_class' => 'border-emerald-500',
                'bg_badge' => 'bg-emerald-50 text-emerald-700',
            ],
            'cambio_estado' => [
                'etiqueta' => 'Actividad de cambio de estado',
                'border_class' => 'border-sky-500',
                'bg_badge' => 'bg-sky-50 text-sky-700',
            ],
            'perfil' => [
                'etiqueta' => 'Actividad de actualización de perfil',
                'border_class' => 'border-gray-400',
                'bg_badge' => 'bg-gray-50 text-gray-700',
            ],
            default => [
                'etiqueta' => 'Actividad general',
                'border_class' => 'border-blue-500',
                'bg_badge' => 'bg-blue-50 text-blue-700',
            ],
        };
    }
}

