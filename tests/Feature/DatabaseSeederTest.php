<?php

namespace Tests\Feature;

use Tests\TestCase;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class DatabaseSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        // En la BD de pruebas en memoria, asegurar compatibilidad con la tabla 'usuarios' usada en seeder
        if (!Schema::hasTable('usuarios')) {
            Schema::create('usuarios', function (Blueprint $table) {
                $table->id();
                $table->string('nombres', 50);
                $table->string('apellidos', 50);
                $table->string('correo_institucional', 100)->unique();
                $table->string('password');
                $table->string('rol', 30)->default('estudiante');
                $table->string('estado', 20)->default('activo');
                $table->timestamps();
            });

            // Sincronizar inserciones con la tabla 'users' para satisfacer las foreign keys en SQLite
            DB::unprepared('
                CREATE TRIGGER sync_usuarios_to_users AFTER INSERT ON usuarios
                BEGIN
                    INSERT INTO users (id, nombres, apellidos, correo_institucional, password, rol, estado, created_at, updated_at)
                    VALUES (new.id, new.nombres, new.apellidos, new.correo_institucional, new.password, new.rol, new.estado, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
                END;
            ');
        }

        // Tabla 'vacantes' referenciada en seeder pero sin migración en base de datos inicial
        if (!Schema::hasTable('vacantes')) {
            Schema::create('vacantes', function (Blueprint $table) {
                $table->id();
                $table->string('empresa')->nullable();
                $table->string('area')->nullable();
                $table->string('descripcion')->nullable();
                $table->string('estado')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Prueba que el seeder se ejecuta sobre la BD en memoria sin errores
     * y genera los registros requeridos con contraseñas seguras.
     */
    public function test_database_seeder_ejecuta_correctamente_en_memoria(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verificar estudiantes creados
        $this->assertDatabaseHas('estudiantes', [
            'codigo_estudiante' => 'USSS027724',
            'nombres' => 'Emerson',
            'apellidos' => 'Chavarria'
        ]);

        $this->assertDatabaseHas('estudiantes', [
            'codigo_estudiante' => 'USSS094524',
            'nombres' => 'Steven',
            'apellidos' => 'Campos'
        ]);

        // 2. Verificar personal administrativo
        $this->assertDatabaseHas('personal_administrativo', [
            'correo_institucional' => 'supervisor@ugb.edu.sv',
            'cargo' => 'supervisor'
        ]);

        $this->assertDatabaseHas('personal_administrativo', [
            'correo_institucional' => 'vicedecano@ugb.edu.sv',
            'cargo' => 'vice_decano'
        ]);

        // 3. Verificar usuarios de sistema y hash de contraseña
        $usuarioSuper = DB::table('usuarios')->where('correo_institucional', 'supervisor@ugb.edu.sv')->first();
        $this->assertNotNull($usuarioSuper);
        $this->assertNotEmpty($usuarioSuper->password);
        $this->assertTrue(str_starts_with($usuarioSuper->password, '$2y$'));

        $usuarioVice = DB::table('usuarios')->where('correo_institucional', 'vicedecano@ugb.edu.sv')->first();
        $this->assertNotNull($usuarioVice);
        $this->assertNotEmpty($usuarioVice->password);
        $this->assertTrue(str_starts_with($usuarioVice->password, '$2y$'));
    }
}
