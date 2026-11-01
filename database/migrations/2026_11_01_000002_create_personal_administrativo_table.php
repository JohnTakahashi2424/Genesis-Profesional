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
        if (!Schema::hasTable('personal_administrativo')) {
            Schema::create('personal_administrativo', function (Blueprint $table) {
                $table->id();
                $table->string('nombres', 100);
                $table->string('apellidos', 100);
                $table->string('correo_institucional', 100)->unique();
                $table->string('password');
                $table->string('cargo', 50)->default('supervisor');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_administrativo');
    }
};

