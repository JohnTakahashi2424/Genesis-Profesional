<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_estudiante', 20)->unique();
            $table->string('nombres', 50);
            $table->string('apellidos', 50);
            $table->string('genero', 20)->nullable();
            $table->string('estado_civil', 20)->nullable();
            $table->string('dui', 15)->nullable();
            $table->text('direccion')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('departamento_nacimiento', 50)->nullable();
            $table->string('municipio_nacimiento', 50)->nullable();
            $table->string('pais', 50)->nullable()->default('EL SALVADOR');
            $table->string('correo_principal', 100)->nullable();
            $table->string('correo_secundario', 100)->unique(); // Correo institucional
            $table->string('telefono', 20)->nullable();
            $table->string('celular', 20)->nullable();
            $table->boolean('es_estudiante_activo')->default(true);
            $table->string('carrera', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
