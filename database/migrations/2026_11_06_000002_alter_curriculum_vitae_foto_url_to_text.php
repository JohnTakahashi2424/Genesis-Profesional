<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones para cambiar foto_url a TEXT
     * permitiendo almacenar imágenes directamente en Base64 en PostgreSQL.
     */
    public function up(): void
    {
        Schema::table('curriculum_vitae', function (Blueprint $table) {
            $table->text('foto_url')->nullable()->change();
        });
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('curriculum_vitae', function (Blueprint $table) {
            $table->string('foto_url', 255)->nullable()->change();
        });
    }
};

