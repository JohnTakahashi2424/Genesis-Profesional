<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'origen_tipo',
        'remitente_id',
        'remitente_nombre',
        'titulo',
        'mensaje',
        'tipo',
        'enlace',
        'leido_at',
    ];

    protected $casts = [
        'leido_at' => 'datetime',
    ];

    protected $appends = [
        'fecha_formateada',
        'es_leida',
        'icono_tipo',
    ];

    /**
     * Relación con el usuario receptor de la notificación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Relación opcional con el usuario que envió la notificación (supervisor / docente).
     */
    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'remitente_id', 'id');
    }

    /**
     * Scope para consultar únicamente las notificaciones del usuario autenticado.
     */
    public function scopeParaUsuario(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar notificaciones pendientes de lectura.
     */
    public function scopeNoLeidas(Builder $query): Builder
    {
        return $query->whereNull('leido_at');
    }

    /**
     * Scope para filtrar notificaciones ya leídas.
     */
    public function scopeLeidas(Builder $query): Builder
    {
        return $query->whereNotNull('leido_at');
    }

    /**
     * Scope para filtrar por tipo según los filtros de la maqueta.
     * (reporte | observacion | proceso_pasantia | general)
     */
    public function scopePorTipo(Builder $query, ?string $tipo): Builder
    {
        if (!empty($tipo) && $tipo !== 'todos' && $tipo !== 'Todos') {
            return $query->where('tipo', $tipo);
        }

        return $query;
    }

    /**
     * Marcar esta notificación como leída.
     */
    public function marcarComoLeida(): bool
    {
        return $this->update(['leido_at' => now()]);
    }

    /**
     * Accesor para saber de forma booleana si fue leída.
     */
    public function getEsLeidaAttribute(): bool
    {
        return !is_null($this->leido_at);
    }

    /**
     * Formatear fecha y hora idéntica a la maqueta (Ej: "28 de mar. 2026 - 01:08:09 p.m.").
     */
    public function getFechaFormateadaAttribute(): string
    {
        $fecha = Carbon::parse($this->created_at)
            ->setTimezone(config('app.timezone', 'America/El_Salvador'))
            ->locale('es');
        $dia = str_replace('..', '.', $fecha->isoFormat('D [de] MMM. YYYY'));
        $hora = $fecha->isoFormat('hh:mm:ss a');

        return "{$dia} - {$hora}";
    }

    /**
     * Identificar qué icono mostrar ('campana' o 'usuario') según la columna origen_tipo.
     */
    public function getIconoTipoAttribute(): string
    {
        return $this->origen_tipo === 'usuario' ? 'usuario' : 'campana';
    }

    /**
     * Helper estático para enviar una notificación rápidamente desde cualquier controlador o evento.
     */
    public static function crearNotificacion(
        int $userId,
        string $titulo,
        string $mensaje,
        string $tipo = 'general',
        string $origenTipo = 'sistema',
        ?string $remitenteNombre = 'Notificaciones',
        ?int $remitenteId = null,
        ?string $enlace = null
    ): self {
        return self::create([
            'user_id'          => $userId,
            'titulo'           => $titulo,
            'mensaje'          => $mensaje,
            'tipo'             => $tipo,
            'origen_tipo'      => $origenTipo,
            'remitente_nombre' => $remitenteNombre ?? 'Notificaciones',
            'remitente_id'     => $remitenteId,
            'enlace'           => $enlace,
            'leido_at'         => null,
        ]);
    }
}

