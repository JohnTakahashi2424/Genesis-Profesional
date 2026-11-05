<?php

namespace Tests\Feature;

use Tests\TestCase;

class CvApiTest extends TestCase
{
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
}

