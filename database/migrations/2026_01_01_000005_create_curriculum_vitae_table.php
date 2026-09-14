<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para la tabla 'curriculum_vitae'.
     */
    public function up(): void
    {
        Schema::create('curriculum_vitae', function (Blueprint $table) {
            $table->id();

            // Llave foránea hacia la tabla de usuarios
            $table->unsignedBigInteger('usuario_id')->nullable();

            // Archivo y título del CV
            $table->string('titulo_cv', 150)->nullable();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('ruta_archivo', 255)->nullable();
            $table->string('url_publica', 255)->nullable();

            // Información personal
            $table->string('nombre_completo', 150)->nullable();
            $table->string('profesion', 150)->nullable();
            $table->string('foto_url', 255)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('telefono', 50)->nullable();

            // Secciones descriptivas
            $table->text('sobre_mi')->nullable();
            $table->text('objetivo')->nullable();

            // Secciones estructuradas (almacenadas como JSON para arreglos/listas)
            $table->json('educacion')->nullable();
            $table->json('valores')->nullable();
            $table->json('conocimientos')->nullable();
            $table->json('idiomas')->nullable();
            $table->json('certificados')->nullable();
            $table->json('habilidades')->nullable();
            $table->json('logros')->nullable();
            $table->json('proyectos_sociales')->nullable();

            // Personalización visual y estado
            $table->string('color_plantilla', 50)->default('#010C67');
            $table->string('fuente', 50)->default('Lora');
            $table->string('estado', 30)->default('borrador'); // 'borrador', 'completado', 'publicado'

            $table->timestamps();
        });

        // Aplicar la clave foránea 
        Schema::table('curriculum_vitae', function (Blueprint $table) {
            if (Schema::hasTable('usuarios')) {
                $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            } elseif (Schema::hasTable('users')) {
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            }
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('curriculum_vitae');
    }
};
