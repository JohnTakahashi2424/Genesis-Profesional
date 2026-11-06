<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CvStorageService;
use Illuminate\Support\Facades\Storage;

class CvStorageServiceTest extends TestCase
{
    protected CvStorageService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CvStorageService();
        Storage::fake('public');
    }

    public function test_procesar_foto_retorna_null_si_input_es_nulo(): void
    {
        $this->assertNull($this->service->procesarFoto(1, null));
    }

    public function test_procesar_foto_retorna_url_externa_sin_alterar(): void
    {
        $url = 'https://images.unsplash.com/photo-test.jpg';
        $this->assertEquals($url, $this->service->procesarFoto(1, $url));
    }

    public function test_procesar_foto_decodifica_base64_y_guarda(): void
    {
        // 1x1 PNG transparente
        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $resultado = $this->service->procesarFoto(999, $base64);

        $this->assertNotNull($resultado);
        $this->assertStringStartsWith('/api/storage/cv_fotos/999/', $resultado);
    }

    public function test_procesar_foto_fuerza_extension_segura_ante_tipo_no_permitido(): void
    {
        // Data URL con mime type no permitido (ej. php/executable)
        $maliciousBase64 = 'data:image/php;base64,PD9waHAgZWNobyAnaGVsbG8nOyA/Pg==';
        $resultado = $this->service->procesarFoto(999, $maliciousBase64);

        $this->assertNotNull($resultado);
        // Debe forzar extension png segura
        $this->assertStringEndsWith('.png', $resultado);
    }

    public function test_guardar_pdf_guarda_archivo_correctamente(): void
    {
        $pdfBase64 = 'data:application/pdf;base64,JVBERi0xLjQK';
        $resultado = $this->service->guardarPdf(999, $pdfBase64, 'Mi_CV', 'TestUser');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('url_publica', $resultado);
        $this->assertStringStartsWith('/api/storage/cvs/999/', $resultado['url_publica']);
    }
}
