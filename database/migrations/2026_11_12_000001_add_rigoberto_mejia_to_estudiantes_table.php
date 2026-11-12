<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega a Rigoberto Alexander Mejía Sorto a la tabla de estudiantes.
     */
    public function up(): void
    {
        // Verificar si ya existe para evitar duplicados
        $existe = DB::table('estudiantes')
            ->where('codigo_estudiante', 'USIS034921')
            ->exists();

        if (!$existe) {
            DB::table('estudiantes')->insert([
                'nombres'                 => 'Rigoberto Alexander',
                'apellidos'               => 'Mejía Sorto',
                'genero'                  => null,
                'estado_civil'            => null,
                'dui'                     => null,
                'direccion'               => null,
                'fecha_nacimiento'        => null,
                'departamento_nacimiento' => null,
                'municipio_nacimiento'    => null,
                'pais'                    => 'EL SALVADOR',
                'correo_principal'        => null,
                'correo_secundario'       => 'USIS034921@ugb.edu.sv',
                'codigo_estudiante'       => 'USIS034921',
                'telefono'                => null,
                'celular'                 => null,
                'es_estudiante_activo'    => true,
                'carrera'                 => 'Ingeniería en Sistemas y Redes Informáticas',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);

            // Resetear la secuencia de PostgreSQL para evitar conflictos de PK si el motor es pgsql
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("SELECT setval(pg_get_serial_sequence('estudiantes', 'id'), (SELECT MAX(id) FROM estudiantes))");
            }
        }
    }

    /**
     * Elimina el registro del estudiante.
     */
    public function down(): void
    {
        DB::table('estudiantes')->where('codigo_estudiante', 'USIS034921')->delete();
    }
};
