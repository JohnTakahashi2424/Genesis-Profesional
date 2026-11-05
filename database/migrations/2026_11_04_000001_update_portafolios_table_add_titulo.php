<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Asegurar que la tabla portafolios exista
        if (!Schema::hasTable('portafolios')) {
            Schema::create('portafolios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('usuario_id');
                $table->string('titulo', 255)->default('My portafolio');
                $table->string('link_portafolio', 500)->nullable();
                $table->timestamps();

                if (Schema::hasTable('users')) {
                    $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
                } elseif (Schema::hasTable('usuarios')) {
                    $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
                }
            });
            return;
        }

        // 2. Si ya existe, agregar columna titulo si no está presente
        Schema::table('portafolios', function (Blueprint $table) {
            if (!Schema::hasColumn('portafolios', 'titulo')) {
                $table->string('titulo', 255)->default('My portafolio')->after('usuario_id');
            }
            if (!Schema::hasColumn('portafolios', 'link_portafolio')) {
                $table->string('link_portafolio', 500)->nullable()->after('titulo');
            }
        });

        // 3. Eliminar restricción o índice UNIQUE sobre usuario_id para permitir múltiples enlaces por pasante
        try {
            Schema::table('portafolios', function (Blueprint $table) {
                $table->dropUnique(['usuario_id']);
            });
        } catch (\Throwable $e) {
            // Ignorar si el índice tiene otro nombre o ya fue removido
        }

        // En PostgreSQL / SQL directo como refuerzo seguro
        try {
            DB::statement('ALTER TABLE portafolios DROP CONSTRAINT IF EXISTS portafolios_usuario_id_unique;');
        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::info('Columna ya configurada en migracion'); }

        try {
            DB::statement('DROP INDEX IF EXISTS portafolios_usuario_id_unique;');
        } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::info('Columna ya configurada en migracion'); }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portafolios', function (Blueprint $table) {
            if (Schema::hasColumn('portafolios', 'titulo')) {
                $table->dropColumn('titulo');
            }
        });
    }
};

