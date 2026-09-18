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
        Schema::create('codigos_recuperacion', function (Blueprint $table) {
            $table->id();
            $table->string('correo', 100)->index();
            $table->string('codigo', 6);
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->boolean('utilizado')->default(false);
            $table->timestamp('expira_en')->index();
            $table->timestamps();

            // Índice compuesto para búsqueda eficiente y atómica
            $table->index(['correo', 'codigo', 'utilizado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('codigos_recuperacion');
    }
};
