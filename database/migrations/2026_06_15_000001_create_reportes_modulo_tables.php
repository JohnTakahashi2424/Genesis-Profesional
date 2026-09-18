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
        // 1. Tabla principal de reportes
        Schema::create('reportes', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el pasante
            $table->foreignId('pasante_id')
                  ->constrained('pasantes')
                  ->onDelete('cascade');

            // Campos del reporte
            $table->string('nombre_reporte');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('horas_registradas')->default(0);
            
            // Estado del reporte: 'Enviado', 'En revisión', 'Aprobado'
            $table->string('estado', 30)->default('En revisión');

            // Evidencias fotográficas / archivos adjuntos en formato JSON
            $table->json('evidencias')->nullable();

            $table->timestamps();
        });

        // 2. Tabla de actividades asociadas al reporte
        Schema::create('actividades_reporte', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el reporte
            $table->foreignId('reporte_id')
                  ->constrained('reportes')
                  ->onDelete('cascade');

            // Campos detallados de la actividad
            $table->date('fecha_actividad');
            $table->text('objetivo');
            $table->text('actividad_realizada');
            $table->text('logros_obtenidos');

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
