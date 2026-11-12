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
        // Limpiar cualquier registro previo con variantes mayúsculas o minúsculas
        DB::table('estudiantes')
            ->whereIn('codigo_estudiante', ['USIS034921', 'usis034921'])
            ->orWhereIn('correo_secundario', ['USIS034921@ugb.edu.sv', 'usis034921@ugb.edu.sv'])
            ->delete();

        DB::table('estudiantes')->insert([
            'codigo_estudiante'       => 'usis034921',
            'nombres'                 => 'Rigoberto Alexander',
            'apellidos'               => 'Mejía Sorto',
            'genero'                  => 'Masculino',
            'estado_civil'            => 'Soltero',
            'dui'                     => '06148293-1',
            'direccion'               => 'Colonia San Patricio, Polígono B, San Miguel, El Salvador',
            'fecha_nacimiento'        => '2002-11-20',
            'departamento_nacimiento' => 'SAN MIGUEL',
            'municipio_nacimiento'    => 'SAN MIGUEL',
            'pais'                    => 'EL SALVADOR',
            'correo_principal'        => 'rigoberto.mejia.ugb@gmail.com',
            'correo_secundario'       => 'usis034921@ugb.edu.sv',
            'telefono'                => '2660-1234',
            'celular'                 => '7654-3210',
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

    /**
     * Elimina el registro del estudiante.
     */
    public function down(): void
    {
        DB::table('estudiantes')
            ->whereIn('codigo_estudiante', ['USIS034921', 'usis034921'])
            ->delete();
    }
};
