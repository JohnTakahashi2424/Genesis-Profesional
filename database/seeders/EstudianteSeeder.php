<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstudianteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('estudiantes')->upsert([
            [
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
                'carrera' => 'Ingeniería en Sistemas y Redes Informáticas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
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
                'carrera' => 'Licenciatura en Computación',
                'created_at' => now(),
                'updated_at' => now(),
            ],
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
                'carrera' => 'Ingeniería en Sistemas y Redes Informáticas',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo_estudiante' => 'USSS990011',
                'nombres' => 'Carlos',
                'apellidos' => 'Inactivo',
                'genero' => 'Masculino',
                'estado_civil' => 'Soltero',
                'dui' => '06099999-9',
                'direccion' => 'Colonia San Benito, San Salvador',
                'fecha_nacimiento' => '2003-01-10',
                'departamento_nacimiento' => 'SAN SALVADOR',
                'municipio_nacimiento' => 'SAN SALVADOR',
                'pais' => 'EL SALVADOR',
                'correo_principal' => 'carlos.inactivo@gmail.com',
                'correo_secundario' => 'usss990011@ugb.edu.sv',
                'telefono' => '2299-8877',
                'celular' => '7000-0001',
                'es_estudiante_activo' => false, // Estudiante inactivo
                'carrera' => 'Ingeniería en Sistemas',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ], ['codigo_estudiante'], ['nombres', 'apellidos', 'correo_secundario', 'es_estudiante_activo', 'carrera', 'updated_at']);
    }
}
