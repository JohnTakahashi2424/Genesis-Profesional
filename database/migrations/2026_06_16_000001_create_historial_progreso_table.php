<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la tabla 'historial_progreso'.
     */
    public function up(): void
    {
        Schema::create('historial_progreso', function (Blueprint $table) {
            $table->id();

            // Llave foránea directa al usuario autenticado de la aplicación web
            // Esto asegura que cada usuario solo vea sus propios registros de progreso
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Categoría/Indicador correspondiente a la leyenda lateral del diseño:
            // 'cv', 'reportes', 'anexos', 'reportes_rechazados', 'reportes_aprobados', 'cambio_estado', 'perfil'
            $table->string('categoria', 50)->default('reportes');

            // Título de la actividad (Ej: 'Envío de reporte', 'Actualización de fotografía', 'Reporte aprovado')
            $table->string('titulo', 150);

            // Descripción o detalle (Ej: 'Envío de reporte del mes de febrero', 'reporte aprovado por - Jenny Flores')
            $table->text('descripcion')->nullable();

            // Momento exacto del suceso (permite mostrar: "01 de marzo de 2026 - 08:16:03 a.m.")
            $table->dateTime('fecha_exacta');

            // Texto de la acción (Ej: 'Ver reporte', 'Ver perfil')
            $table->string('accion_texto', 50)->default('Ver reporte');

            // Enlace o ruta interna hacia donde navega la acción
            $table->string('accion_url', 255)->nullable();

            // Porcentaje numérico (0 a 100) para renderizar la barra de la columna 'Progreso'
            $table->unsignedTinyInteger('progreso')->default(0);

            $table->timestamps();

            // Índices para optimizar las consultas filtradas por usuario y fecha
            $table->index(['user_id', 'fecha_exacta']);
            $table->index(['user_id', 'categoria']);
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_progreso');
    }
};

