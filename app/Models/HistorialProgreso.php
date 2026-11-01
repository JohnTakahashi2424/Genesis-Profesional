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
        $fecha = $this->fecha_exacta ?? $this->created_at;
        if (!$fecha) {
            return '';
        }

        return Carbon::parse($fecha)
            ->setTimezone(config('app.timezone', 'America/El_Salvador'))
            ->locale('es')
            ->isoFormat('DD [de] MMMM [de] YYYY');
    }

    /**
     * Retorna el día y hora exacto formateado (Ej: '01 de marzo de 2026 - 08:16:03 a.m.').
     */
    public function getFechaHoraFormateadaAttribute(): string
    {
        $fecha = $this->fecha_exacta ?? $this->created_at;
        if (!$fecha) {
            return '';
        }

        $carbon = Carbon::parse($fecha)
            ->setTimezone(config('app.timezone', 'America/El_Salvador'))
            ->locale('es');
        $f = $carbon->isoFormat('DD [de] MMMM [de] YYYY');
        $hora = $carbon->isoFormat('hh:mm:ss a');

        return "{$f} - {$hora}";
    }

    /**
     * Retorna el color y borde asignado según el indicador de la maqueta.
     */
    public function getColorIndicadorAttribute(): array
    {
        return match ($this->categoria) {
            'cv' => [
                'etiqueta' => 'Actividad de creación / actualización de CV',
                'border_class' => 'border-[#9E2A2B]',
                'text_class' => 'text-[#9E2A2B]',
            ],
            'reportes' => [
                'etiqueta' => 'Actividad de creación/envío y observaciones de reportes',
                'border_class' => 'border-[#1E3A8A]',
                'text_class' => 'text-[#1E3A8A]',
            ],
            'anexos' => [
                'etiqueta' => 'Actividad de anexos a reportes',
                'border_class' => 'border-[#C2410C]',
                'text_class' => 'text-[#C2410C]',
            ],
            'reportes_rechazados' => [
                'etiqueta' => 'Actividad de reportes rechazados',
                'border_class' => 'border-[#DC2626]',
                'text_class' => 'text-[#DC2626]',
            ],
            'reportes_aprobados' => [
                'etiqueta' => 'Actividad de reportes aprobados',
                'border_class' => 'border-[#16A34A]',
                'text_class' => 'text-[#16A34A]',
            ],
            'cambio_estado' => [
                'etiqueta' => 'Actividad de cambio de estado',
                'border_class' => 'border-[#0284C7]',
                'text_class' => 'text-[#0284C7]',
            ],
            'perfil' => [
                'etiqueta' => 'Actividad de actualización de perfil',
                'border_class' => 'border-[#9CA3AF]',
                'text_class' => 'text-[#6B7280]',
            ],
            default => [
                'etiqueta' => 'Actividad general',
                'border_class' => 'border-[#1E3A8A]',
                'text_class' => 'text-[#1E3A8A]',
            ],
        };
    }

    /**
     * Calcula el porcentaje acumulado de progreso según los reportes aprobados.
     * La pasantía dura de 6 a 7 meses:
     * - Por defecto (sin reportes aprobados): 0%
     * - Cada reporte aprobado incrementa la barra (ej: 14%-15% por mes).
     * - Al aprobarse el informe final o completar los meses: 100%.
     */
    public static function calcularProgresoActual(int $userId, bool $incluirNuevoAprobado = false): int
    {
        $reportesAprobados = \App\Models\Reporte::where('user_id', $userId)
            ->where('estado', 'Aprobado')
            ->count();

        if ($incluirNuevoAprobado) {
            $reportesAprobados++;
        }

        // Si ya hay informe final aprobado, 100%
        try {
            if (class_exists(\App\Models\InformeFinal::class)) {
                $informesFinalesAprobados = \App\Models\InformeFinal::where('user_id', $userId)
                    ->where('estado', 'Aprobado')
                    ->count();

                if ($informesFinalesAprobados > 0) {
                    return 100;
                }
            }
        } catch (\Throwable $th) {
            // Silencioso
        }

        // Escala progresiva para 6 a 7 meses de pasantía
        $escala = [
            0 => 0,
            1 => 15,
            2 => 30,
            3 => 45,
            4 => 60,
            5 => 75,
            6 => 88,
            7 => 95,
        ];

        return $escala[min($reportesAprobados, 7)] ?? min(100, $reportesAprobados * 14);
    }

    /**
     * Helper para registrar una nueva actividad de progreso fácilmente desde cualquier módulo.
     */
    public static function registrar(
        int $userId,
        string $categoria,
        string $titulo,
        ?string $descripcion = null,
        ?string $accionTexto = 'Ver reporte',
        ?string $accionUrl = null,
        int $progreso = 0,
        ?string $fechaExacta = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'categoria' => $categoria,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'accion_texto' => $accionTexto,
            'accion_url' => $accionUrl,
            'progreso' => $progreso,
            'fecha_exacta' => $fechaExacta ?? now(),
        ]);
    }
}
