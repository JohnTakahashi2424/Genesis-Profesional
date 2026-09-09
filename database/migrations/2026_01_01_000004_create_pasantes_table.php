<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la tabla 'pasantes' (Estado del proceso de pasantías).
     */
    public function up(): void
    {
        Schema::create('pasantes', function (Blueprint $table) {
            $table->id();

            // Llaves Foráneas (FK) principales
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('estudiante_id')
                  ->nullable()
                  ->constrained('estudiantes')
                  ->nullOnDelete();

            $table->foreignId('supervisor_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Información general de la pasantía
            $table->string('area', 100)->nullable();
            $table->string('tipo_pasantia', 30)->default('interna'); // 'interna', 'externa'
            $table->string('estado', 30)->default('en_proceso'); // 'en_proceso', 'completada', 'cancelada'
            $table->string('fase_actual', 30)->default('Fase 1');

            // Estados individuales de cada una de las 4 fases (según dashboard de la maqueta)
            // Valores posibles: 'completado', 'pendiente', 'en_proceso', 'rechazado'
            $table->string('fase1_curriculum', 30)->default('completado');
            $table->string('fase2_aceptado', 30)->default('completado');
            $table->string('fase3_practicas', 30)->default('pendiente');
            $table->string('fase4_informe_final', 30)->default('pendiente');

            // Control de horas de práctica
            $table->decimal('horas_aprobadas', 8, 2)->default(0.00);

            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasantes');
    }
};
