<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear el Estudiante para verificación (USSS027724)
        DB::table('estudiantes')->insert([
            'codigo_estudiante' => 'USSS027724',
            'nombres' => 'Emerson',
            'apellidos' => 'Chavarria',
            'genero' => 'Masculino',
            'estado_civil' => 'Soltero',
            'dui' => '06835489-5',
            'direccion' => 'CANTON EL CERRITO, CASERIO EL TANQUE, USULUTAN, USULUTAN.',
            'fecha_nacimiento' => '2005-02-26',
            'departamento_nacimiento' => 'SAN SALVADOR',
            'municipio_nacimiento' => 'SAN SALVADOR',
            'pais' => 'EL SALVADOR',
            'correo_principal' => 'emersonchavarria578@gmail.com',
            'correo_secundario' => 'usss027724@ugb.edu.sv',
            'telefono' => '7672-2390',
            'celular' => '7672-2390',
            'es_estudiante_activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Otro estudiante de prueba
        DB::table('estudiantes')->insert([
            'codigo_estudiante' => 'USM123456',
            'nombres' => 'Ana',
            'apellidos' => 'Martínez',
            'genero' => 'Femenino',
            'estado_civil' => 'Soltera',
            'dui' => '05432198-1',
            'direccion' => 'Barrio El Centro, San Miguel',
            'fecha_nacimiento' => '2002-05-15',
            'departamento_nacimiento' => 'SAN MIGUEL',
            'municipio_nacimiento' => 'SAN MIGUEL',
            'pais' => 'EL SALVADOR',
            'correo_principal' => 'ana.martinez@gmail.com',
            'correo_secundario' => 'usm123456@ugb.edu.sv',
            'telefono' => '7788-9900',
            'celular' => '7788-9900',
            'es_estudiante_activo' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 5 Estudiantes de prueba adicionales para registro (USSS, terminación entre 20 y 24)
        DB::table('estudiantes')->insert([
            [
                'codigo_estudiante' => 'USSS010120',
                'nombres' => 'Juan',
                'apellidos' => 'Pérez',
                'genero' => 'Masculino',
                'estado_civil' => 'Soltero',
                'dui' => '06012345-1',
                'direccion' => 'Colonia Médica, San Salvador',
                'fecha_nacimiento' => '2004-03-12',
                'departamento_nacimiento' => 'SAN SALVADOR',
                'municipio_nacimiento' => 'SAN SALVADOR',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'juan.perez@gmail.com',
                'correo_secundario' => 'usss010120@ugb.edu.sv',
                'telefono' => '2222-3333',
                'celular' => '7777-8888',
                'es_estudiante_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo_estudiante' => 'USSS020221',
                'nombres' => 'María',
                'apellidos' => 'Gómez',
                'genero' => 'Femenino',
                'estado_civil' => 'Soltera',
                'dui' => '06023456-2',
                'direccion' => 'Residencial Altavista, Ilopango',
                'fecha_nacimiento' => '2003-07-22',
                'departamento_nacimiento' => 'SAN SALVADOR',
                'municipio_nacimiento' => 'ILOPANGO',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'maria.gomez@gmail.com',
                'correo_secundario' => 'usss020221@ugb.edu.sv',
                'telefono' => '2233-4455',
                'celular' => '7654-3210',
                'es_estudiante_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo_estudiante' => 'USSS030322',
                'nombres' => 'Carlos',
                'apellidos' => 'López',
                'genero' => 'Masculino',
                'estado_civil' => 'Soltero',
                'dui' => '06034567-3',
                'direccion' => 'Urbanización La Cima, San Salvador',
                'fecha_nacimiento' => '2004-11-05',
                'departamento_nacimiento' => 'SAN SALVADOR',
                'municipio_nacimiento' => 'SAN SALVADOR',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'carlos.lopez@gmail.com',
                'correo_secundario' => 'usss030322@ugb.edu.sv',
                'telefono' => '2244-5566',
                'celular' => '7890-1234',
                'es_estudiante_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo_estudiante' => 'USSS040423',
                'nombres' => 'Gabriela',
                'apellidos' => 'Fernández',
                'genero' => 'Femenino',
                'estado_civil' => 'Soltera',
                'dui' => '06045678-4',
                'direccion' => 'Colonia Escalón, San Salvador',
                'fecha_nacimiento' => '2005-01-30',
                'departamento_nacimiento' => 'SAN SALVADOR',
                'municipio_nacimiento' => 'SAN SALVADOR',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'gaby.fer@gmail.com',
                'correo_secundario' => 'usss040423@ugb.edu.sv',
                'telefono' => '2255-6677',
                'celular' => '7531-9753',
                'es_estudiante_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'codigo_estudiante' => 'USSS050524',
                'nombres' => 'Luis',
                'apellidos' => 'Rodríguez',
                'genero' => 'Masculino',
                'estado_civil' => 'Soltero',
                'dui' => '06056789-5',
                'direccion' => 'Barrio El Calvario, San Miguel',
                'fecha_nacimiento' => '2004-09-18',
                'departamento_nacimiento' => 'SAN MIGUEL',
                'municipio_nacimiento' => 'SAN MIGUEL',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'luis.rod@gmail.com',
                'correo_secundario' => 'usss050524@ugb.edu.sv',
                'telefono' => '2661-2222',
                'celular' => '7182-9304',
                'es_estudiante_activo' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);

        // 2. Personal Administrativo (Supervisores y Vicedecano)
        $supervisorId = DB::table('personal_administrativo')->insertGetId([
            'nombres' => 'Ing. Carlos',
            'apellidos' => 'Ramírez',
            'correo_institucional' => 'supervisor@ugb.edu.sv',
            'password' => Hash::make('password123'),
            'cargo' => 'supervisor',
        ]);

        DB::table('personal_administrativo')->insert([
            'nombres' => 'Dr. Roberto',
            'apellidos' => 'Méndez',
            'correo_institucional' => 'vicedecano@ugb.edu.sv',
            'password' => Hash::make('password123'),
            'cargo' => 'vice_decano',
        ]);

        // 3. Usuarios de sistema (login)
        $pasante1Id = DB::table('usuarios')->insertGetId([
            'nombres' => 'Emerson',
            'apellidos' => 'Chavarria',
            'correo_institucional' => 'usss027724@ugb.edu.sv',
            'password' => Hash::make('password123'),
            'rol' => 'pasante',
            'estado' => 'activo'
        ]);

        $pasante2Id = DB::table('usuarios')->insertGetId([
            'nombres' => 'Ana',
            'apellidos' => 'Martínez',
            'correo_institucional' => 'usm123456@ugb.edu.sv',
            'password' => Hash::make('password123'),
            'rol' => 'pasante',
            'estado' => 'activo'
        ]);

        $usuarioSuper = DB::table('usuarios')->insertGetId([
            'nombres' => 'Ing. Carlos',
            'apellidos' => 'Ramírez',
            'correo_institucional' => 'supervisor@ugb.edu.sv',
            'password' => Hash::make('password123'),
            'rol' => 'supervisor',
            'estado' => 'activo'
        ]);

        $usuarioVice = DB::table('usuarios')->insertGetId([
            'nombres' => 'Dr. Roberto',
            'apellidos' => 'Méndez',
            'correo_institucional' => 'vicedecano@ugb.edu.sv',
            'password' => Hash::make('password123'),
            'rol' => 'vice_decano',
            'estado' => 'activo'
        ]);

        // 4. Perfiles de Pasantes
        DB::table('pasantes')->insert([
            'usuario_id' => $pasante1Id,
            'area' => 'Ingeniería en Sistemas',
            'tipo_pasantia' => 'interna',
            'estado' => 'en_proceso',
            'fase_actual' => 'Fase 1',
            'supervisor_id' => $supervisorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        DB::table('pasantes')->insert([
            'usuario_id' => $pasante2Id,
            'area' => 'Ingeniería en Sistemas',
            'tipo_pasantia' => 'externa',
            'estado' => 'en_proceso',
            'fase_actual' => 'Fase 2',
            'supervisor_id' => $supervisorId,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 5. Vacantes
        DB::table('vacantes')->insert([
            ['empresa' => 'TechCorp', 'area' => 'desarrollo', 'descripcion' => 'Desarrollador Frontend Jr. - React/Vue', 'estado' => 'activa'],
            ['empresa' => 'DesignStudio', 'area' => 'diseño', 'descripcion' => 'Diseñador UI/UX para aplicaciones móviles', 'estado' => 'activa'],
            ['empresa' => 'CloudNet', 'area' => 'infraestructura', 'descripcion' => 'Administrador de servidores Linux', 'estado' => 'activa'],
            ['empresa' => 'SecureTech', 'area' => 'seguridad', 'descripcion' => 'Analista de seguridad informática Jr.', 'estado' => 'activa'],
            ['empresa' => 'DataFlow', 'area' => 'desarrollo', 'descripcion' => 'Desarrollador Backend - Node.js/Python', 'estado' => 'activa']
        ]);
    }
}
