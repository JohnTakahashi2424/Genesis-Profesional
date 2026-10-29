<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para las tablas del módulo de informe final.
     * Estructura basada en la maqueta del formulario:
     * - informes_finales: datos generales del informe
     * - informe_final_reportes: pivote para vincular reportes mensuales aprobados
     * - presupuesto_items: filas dinámicas de gastos con montos por mes
     */
    public function up(): void
    {
        // 1. Tabla principal del informe final
        Schema::create('informes_finales', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el usuario de la tabla 'users'
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            // Llave foránea hacia el registro de pasante
            $table->foreignId('pasante_id')
                  ->nullable()
                  ->constrained('pasantes')
                  ->nullOnDelete();

            // Sección: Encabezado del informe
            $table->string('nombre_pasante', 150);
            $table->string('institucion', 200);

            // Sección: Campos descriptivos del informe (textos largos)
            $table->text('presentacion');
            $table->text('generalidades');
            $table->text('procedimiento_pasantia');
            $table->text('descripcion_empresa');
            $table->text('descripcion_pasantia');

            // Sección: Anexos (archivos adjuntos en formato JSON)
            $table->json('anexos')->nullable();

            // Sección: Presupuesto - columnas dinámicas definidas por el usuario
            // Almacena los nombres de las columnas de meses (ej: ["Ene.", "Feb.", "Mar.", ...])
            $table->json('presupuesto_columnas')->nullable();

            // Total general del presupuesto
            $table->decimal('presupuesto_total', 12, 2)->default(0);

            // Estado del informe final: 'Borrador', 'Enviado', 'En revisión', 'Aprobado', 'Observado'
            $table->string('estado', 30)->default('Borrador');

            $table->timestamps();
        });

        // 2. Tabla pivote para vincular reportes mensuales al informe final
        Schema::create('informe_final_reportes', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el informe final
            $table->foreignId('informe_final_id')
                  ->constrained('informes_finales')
                  ->onDelete('cascade');

            // Llave foránea hacia el reporte mensual adjuntado
            $table->foreignId('reporte_id')
                  ->constrained('reportes')
                  ->onDelete('cascade');

            // Evitar duplicados: un reporte solo se vincula una vez al mismo informe
            $table->unique(['informe_final_id', 'reporte_id']);

            $table->timestamps();
        });

        // 3. Tabla de filas dinámicas del presupuesto (cada fila es un tipo de gasto)
        Schema::create('presupuesto_items', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia el informe final
            $table->foreignId('informe_final_id')
                  ->constrained('informes_finales')
                  ->onDelete('cascade');

            // Nombre del gasto (ej: "Comida", "Pasaje", "Materiales")
            $table->string('gasto', 100);

            // Orden de la fila en la tabla del presupuesto
            $table->integer('orden')->default(0);

            // Montos por columna/mes en formato JSON (ej: {"Ene.": 50, "Feb.": 30, ...})
            // Permite agregar columnas dinámicamente sin modificar la estructura de la tabla
            $table->json('montos')->nullable();

            // Total calculado de esta fila de gasto
            $table->decimal('total', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Revierte las migraciones en orden inverso de dependencia.
     */
    public function down(): void
    {
        Schema::dropIfExists('presupuesto_items');
        Schema::dropIfExists('informe_final_reportes');
        Schema::dropIfExists('informes_finales');
    }
};

