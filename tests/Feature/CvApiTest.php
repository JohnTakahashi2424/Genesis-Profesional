<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Services\JwtService;

class CvApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
    }
    /**
     * Prueba que el guardado de CV exige autenticación JWT
     */
    public function test_guardar_cv_requiere_autenticacion(): void
    {
        $response = $this->postJson('/api/cv/guardar', [
            'usuario_id' => 1,
            'pdf_base64' => 'data:application/pdf;base64,JVBERi0xLjQK...'
        ]);

        $response->assertStatus(401);
    }

    /**
     * Prueba que el guardado de CV con payload inválido devuelve 422 y no 500
     */
    public function test_guardar_cv_con_archivo_invalido_devuelve_422(): void
    {
        $user = User::create([
            'nombres' => 'Test',
            'apellidos' => 'User',
            'correo_institucional' => 'testcv@ugb.edu.sv',
            'password' => bcrypt('password123'),
            'rol' => 'estudiante',
            'estado' => 'activo'
        ]);

        $token = JwtService::generarToken($user);

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/cv/guardar', [
                'usuario_id' => $user->id,
                'pdf_base64' => 'data:application/pdf;base64,' . base64_encode('TEXTO_PLANO_NO_PDF'),
                'titulo_cv' => 'CV_Invalido'
            ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'mensaje',
                     'errores' => ['pdf_base64']
                 ]);
    }

    /**
     * Prueba que la obtención de CV exige autenticación JWT
     */
    public function test_obtener_cv_requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/cv/obtener/1');
        $response->assertStatus(401);
    }

    /**
     * Prueba que la eliminación de CV exige autenticación JWT
     */
    public function test_eliminar_cv_requiere_autenticacion(): void
    {
        $response = $this->deleteJson('/api/cv/eliminar/1');
        $response->assertStatus(401);
    }

    public function test_guardar_cv_exitoso(): void
    {
        $user = User::create([
            'nombres' => 'Test',
            'apellidos' => 'User',
            'correo_institucional' => 'testcv2@ugb.edu.sv',
            'password' => bcrypt('password123'),
            'rol' => 'estudiante',
            'estado' => 'activo'
        ]);

        $token = JwtService::generarToken($user);

        $validPdf = 'data:application/pdf;base64,' . base64_encode("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/cv/guardar', [
                'usuario_id' => $user->id,
                'pdf_base64' => $validPdf,
                'titulo_cv' => 'Mi CV Test',
                'perfil' => [
                    'nombre' => 'Test User',
                    'email' => 'testcv2@ugb.edu.sv',
                    'fotoUrl' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
                ],
                'objetivos' => ['objetivo' => 'Aprender'],
                'logros' => ['habilidades' => 'PHP, Vue'],
                'diseno' => ['color' => '#010C67', 'fuente' => 'Montserrat']
            ]);

        $response->assertStatus(200);
    }
}

