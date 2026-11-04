<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('estudiantes')->updateOrInsert(
            ['codigo_estudiante' => 'USSS003024'],
            [
                'nombres' => 'Ana Elisa',
                'apellidos' => 'Ramírez Portillo',
                'genero' => 'Femenino',
                'estado_civil' => 'Soltera',
                'dui' => '06841257-2',
                'direccion' => 'San Salvador, El Salvador',
                'fecha_nacimiento' => '2004-06-14',
                'departamento_nacimiento' => 'SAN SALVADOR',
                'municipio_nacimiento' => 'SAN SALVADOR',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'ana.ramirez.portillo@gmail.com',
                'correo_secundario' => 'usss003024@ugb.edu.sv',
                'telefono' => '2255-0030',
                'celular' => '7890-0030',
                'es_estudiante_activo' => true,
                'carrera' => 'Ingeniería en Sistemas y Redes Informáticas',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('estudiantes')->where('codigo_estudiante', 'USSS003024')->delete();
    }
};

