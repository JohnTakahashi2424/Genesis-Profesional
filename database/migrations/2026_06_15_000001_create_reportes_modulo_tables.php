<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para las tablas del módulo de reportes.
     */
    public function up(): void
    {
        // 1. Tabla principal de reportes mensuales
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el pasante
            $table->foreignId('pasante_id')
                  ->constrained('pasantes')
                  ->onDelete('cascade');

            // Sección: Información General
            $table->string('area_pasantia', 150);
            $table->string('nombre_pasante', 150);
            $table->string('mes', 50);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->text('objetivo');

            // Estado del reporte: 'Enviado', 'En revisión', 'Aprobado', 'Observado'
            $table->string('estado', 30)->default('En revisión');

            // Evidencias fotográficas / archivos adjuntos en formato JSON
            $table->json('evidencias')->nullable();

            $table->timestamps();
        });

        // 2. Tabla de actividades realizadas del reporte
        Schema::create('actividades_reporte', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el reporte
            $table->foreignId('reporte_id')
                  ->constrained('reportes')
                  ->onDelete('cascade');

            // Campos detallados de la actividad
            $table->integer('numero')->default(1);
            $table->text('actividad');
            $table->date('fecha');
            $table->text('resultado_aprendizaje');

            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades_reporte');
        Schema::dropIfExists('reportes');
    }
};
