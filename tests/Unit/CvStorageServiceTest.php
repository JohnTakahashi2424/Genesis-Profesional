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
        // En Opción 1, retorna el dataUrl Base64 para persistencia en base de datos
        $this->assertStringStartsWith('data:image/png;base64,', $resultado);
    }

    public function test_procesar_foto_fuerza_extension_segura_ante_tipo_no_permitido(): void
    {
        // Data URL con mime type no permitido (ej. php/executable)
        $maliciousBase64 = 'data:image/php;base64,PD9waHAgZWNobyAnaGVsbG8nOyA/Pg==';
        $resultado = $this->service->procesarFoto(999, $maliciousBase64);

        $this->assertNotNull($resultado);
        // Debe forzar extension png segura en el Data URL
        $this->assertStringStartsWith('data:image/png;base64,', $resultado);
    }

    public function test_procesar_foto_omite_escritura_en_public_storage_fuera_de_entorno_local(): void
    {
        // En testing, app()->isLocal() es false
        $this->assertFalse($this->app->isLocal());

        $userId = 8888;
        $publicDir = public_path("storage/cv_fotos/{$userId}");
        \Illuminate\Support\Facades\File::deleteDirectory($publicDir);

        $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
        $resultado = $this->service->procesarFoto($userId, $base64);

        $this->assertNotNull($resultado);
        $this->assertStringStartsWith('data:image/png;base64,', $resultado);
        // NO debe haberse escrito en public_path('storage') en entorno no-local
        $this->assertFalse(\Illuminate\Support\Facades\File::exists($publicDir));
    }

    public function test_procesar_foto_escribe_en_public_storage_bajo_entorno_local(): void
    {
        $originalEnv = $this->app['env'];
        $this->app['env'] = 'local';
        $this->assertTrue($this->app->isLocal());

        $userId = 7777;
        $publicDir = public_path("storage/cv_fotos/{$userId}");
        \Illuminate\Support\Facades\File::deleteDirectory($publicDir);

        try {
            $base64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
            $resultado = $this->service->procesarFoto($userId, $base64);

            $this->assertNotNull($resultado);
            // En entorno local, DEBE sincronizar en public_path('storage')
            $this->assertTrue(\Illuminate\Support\Facades\File::exists($publicDir));
        } finally {
            $this->app['env'] = $originalEnv;
            \Illuminate\Support\Facades\File::deleteDirectory($publicDir);
        }
    }

    public function test_guardar_pdf_guarda_archivo_correctamente(): void
    {
        $pdfBase64 = 'data:application/pdf;base64,JVBERi0xLjQK';
        $resultado = $this->service->guardarPdf(999, $pdfBase64, 'Mi CV Profesional 2026', 'TestUser');

        $this->assertIsArray($resultado);
        $this->assertArrayHasKey('url_publica', $resultado);
        $this->assertStringStartsWith('/api/storage/cvs/999/', $resultado['url_publica']);
        $this->assertStringStartsWith('mi_cv_profesional_2026_999_', $resultado['nombre_archivo']);
    }

    public function test_guardar_pdf_rechaza_archivo_sin_cabecera_pdf(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El archivo no tiene una cabecera PDF válida.');

        // Base64 de texto plano "NOT_A_PDF"
        $invalidBase64 = 'data:application/pdf;base64,' . base64_encode('NOT_A_PDF_CONTENT');
        $this->service->guardarPdf(999, $invalidBase64, 'Fake', 'TestUser');
    }

    public function test_guardar_pdf_rechaza_archivo_que_excede_tamano_maximo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El archivo excede el tamaño máximo permitido de 10MB.');

        // Generar carga simulada de > 10MB con cabecera %PDF-
        $largeData = '%PDF-' . str_repeat('A', (10 * 1024 * 1024) + 10);
        $largeBase64 = 'data:application/pdf;base64,' . base64_encode($largeData);
        $this->service->guardarPdf(999, $largeBase64, 'TooLarge', 'TestUser');
    }

    public function test_guardar_pdf_soporta_payload_real_jspdf_con_metadata_mayusculas_y_saltos_de_linea(): void
    {
        // Simula la salida de doc.output('datauristring') con 'DATA:', 'filename=', espacios (convertidos desde +) y saltos \r\n
        $rawPdf = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF";
        $base64 = base64_encode($rawPdf);
        // Introducir espacios en lugar de '+' para simular decodificación de formularios web
        $withSpaces = str_replace('+', ' ', $base64);
        $encodedChunked = chunk_split($withSpaces, 64, "\r\n");
        $jspdfPayload = "DATA:application/pdf;filename=curriculum.pdf;base64,\r\n" . $encodedChunked;

        $resultado = $this->service->guardarPdf(123, $jspdfPayload, 'CV jsPDF Test', 'Usuario');

        $this->assertIsArray($resultado);
        $this->assertStringStartsWith('/api/storage/cvs/123/', $resultado['url_publica']);
        $this->assertStringStartsWith('cv_jspdf_test_123_', $resultado['nombre_archivo']);
    }
}
