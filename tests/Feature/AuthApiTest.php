<?php

namespace Tests\Feature;

use Tests\TestCase;

class AuthApiTest extends TestCase
{
    /**
     * Prueba que el login rechaza correos que no pertenecen al dominio @ugb.edu.sv
     */
    public function test_login_rechaza_correo_no_institucional(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'correo' => 'usuario@gmail.com',
            'contrasena' => 'Secret123*'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([
                     'status',
                     'mensaje',
                     'errores' => ['correo']
                 ]);
    }

    /**
     * Prueba que el login valida campos obligatorios vacíos
     */
    public function test_login_valida_campos_requeridos(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'correo' => '',
            'contrasena' => ''
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'error',
                     'mensaje' => 'Debe completar todos los campos para continuar'
                 ]);
    }

    /**
     * Prueba que las rutas protegidas rechazan peticiones sin token JWT
     */
    public function test_rutas_protegidas_requieren_token(): void
    {
        $responseEstado = $this->getJson('/api/pasante/estado');
        $responseEstado->assertStatus(401);

        $responseCv = $this->getJson('/api/cv/obtener/1');
        $responseCv->assertStatus(401);
    }
}
