<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Asegura la inserción de Rigoberto Alexander Mejía Sorto en el padrón de estudiantes.
     * Diseñada para ser 100% idempotente y tolerante a fallos de secuencia en PostgreSQL.
     */
    public function up(): void
    {
        // 1. Eliminar cualquier registro previo de Rigoberto (sin importar mayúsculas/minúsculas)
        DB::table('estudiantes')
            ->whereRaw('LOWER(codigo_estudiante) = ?', ['usis034921'])
            ->orWhereRaw('LOWER(correo_secundario) = ?', ['usis034921@ugb.edu.sv'])
            ->delete();

        // 2. Calcular el siguiente ID disponible para no depender de secuencias desincronizadas
        $maxId = DB::table('estudiantes')->max('id');
        $nextId = $maxId ? ($maxId + 1) : 1;

        // 3. Sincronizar secuencia en PostgreSQL antes de insertar (si aplica)
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement("SELECT setval(pg_get_serial_sequence('estudiantes', 'id'), ?, true)", [$maxId ?? 1]);
            } catch (\Throwable $e) {
                // Silencioso para evitar abortar si la secuencia tiene permisos restringidos
            }
        }

        // 4. Inserción directa con ID explícito
        DB::table('estudiantes')->insert([
            'id'                      => $nextId,
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

        // 5. Ajustar la secuencia de PostgreSQL al nuevo ID
        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement("SELECT setval(pg_get_serial_sequence('estudiantes', 'id'), ?, true)", [$nextId]);
            } catch (\Throwable $e) {
                // Silencioso
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('estudiantes')
            ->whereRaw('LOWER(codigo_estudiante) = ?', ['usis034921'])
            ->delete();
    }
};
