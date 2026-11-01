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
        Schema::create('portafolios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id')->unique();
            $table->string('link_portafolio', 500)->nullable();
            $table->timestamps();

            // Llave foránea hacia users
            if (Schema::hasTable('users')) {
                $table->foreign('usuario_id')->references('id')->on('users')->onDelete('cascade');
            } elseif (Schema::hasTable('usuarios')) {
                $table->foreign('usuario_id')->references('id')->on('usuarios')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portafolios');
    }
};

