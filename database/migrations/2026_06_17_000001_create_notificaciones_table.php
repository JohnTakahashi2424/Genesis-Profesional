<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la tabla 'notificaciones'.
     * Asociada directamente al usuario receptor (user_id de la tabla users).
     *
     * Campos mapeados desde la maqueta:
     * - Icono campana  → origen_tipo = 'sistema'
     * - Icono persona  → origen_tipo = 'usuario', remitente_nombre = 'Jenny Flores'
     * - Título         → "Reporte enviado", "Observaciones", "Proceso de pasantía"
     * - Mensaje        → Texto completo (se muestra truncado en la lista)
     * - Fecha          → created_at formateado: "28 de mar. 2026 - 01:08:09 p.m."
     * - Ojo (👁)       → leido_at = now() al marcar como vista
     * - Basurero (🗑)  → soft delete / eliminación directa
     * - Filtro "Todos" → tipo (reporte | observacion | proceso_pasantia | general)
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();

            // ── Receptor ──────────────────────────────────────────────────────
            // El usuario de la aplicación web que debe ver esta notificación
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // ── Origen / Remitente ────────────────────────────────────────────
            // 'sistema'  → icono de campana (notificación automática del sistema)
            // 'usuario'  → icono de persona (enviada por un supervisor/docente)
            $table->string('origen_tipo', 20)->default('sistema');

            // ID del usuario que generó la notificación (nullable para origen sistema)
            $table->foreignId('remitente_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Nombre visible del remitente (Ej: "Jenny Flores" o "Notificaciones")
            $table->string('remitente_nombre', 100)->default('Notificaciones');

            // ── Contenido ─────────────────────────────────────────────────────
            // Título corto visible en la columna "Título"
            // Ej: "Reporte enviado", "Observaciones", "Proceso de pasantía"
            $table->string('titulo', 150);

            // Mensaje completo (se muestra truncado con "..." en la lista)
            $table->text('mensaje');

            // ── Clasificación para el filtro "Todos" / "Filtrar" ─────────────
            // Valores: 'reporte', 'observacion', 'proceso_pasantia', 'general'
            $table->string('tipo', 50)->default('general');

            // ── Enlace de acción ─────────────────────────────────────────────
            // URL interna opcional donde navegar al hacer clic en el ojo (👁)
            // Ej: '/reportes/5', '/historial', '/perfil'
            $table->string('enlace', 255)->nullable();

            // ── Estado de lectura ─────────────────────────────────────────────
            // NULL  → no leída (fondo diferenciado en la UI)
            // fecha → leída (fecha exacta en que el usuario hizo clic en el ojo)
            $table->timestamp('leido_at')->nullable();

            $table->timestamps();

            // ── Índices para optimizar consultas frecuentes ───────────────────
            $table->index(['user_id', 'leido_at']);    // listar no leídas rápido
            $table->index(['user_id', 'tipo']);         // filtro por tipo
            $table->index(['user_id', 'created_at']);   // ordenar por fecha
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};

