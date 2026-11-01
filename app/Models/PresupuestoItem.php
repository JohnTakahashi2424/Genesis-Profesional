<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresupuestoItem extends Model
{
    protected $table = 'presupuesto_items';

    public $timestamps = true;

    protected $fillable = [
        'informe_final_id',
        'gasto',
        'orden',
        'montos',
        'total',
    ];

    protected $casts = [
        'montos' => 'array',
        'orden' => 'integer',
        'total' => 'decimal:2',
    ];

    // ─── Relaciones ──────────────────────────────────────────────

    /**
     * Relación con el informe final al que pertenece este item de presupuesto.
     */
    public function informeFinal(): BelongsTo
    {
        return $this->belongsTo(InformeFinal::class, 'informe_final_id', 'id');
    }

    // ─── Métodos de utilidad ─────────────────────────────────────

    /**
     * Recalcular el total de esta fila sumando todos los montos por mes.
     * Se invoca antes de guardar para mantener la consistencia.
     */
    public function recalcularTotal(): void
    {
        if (is_array($this->montos)) {
            $this->total = array_sum(array_map('floatval', $this->montos));
        } else {
            $this->total = 0;
        }
    }
}

