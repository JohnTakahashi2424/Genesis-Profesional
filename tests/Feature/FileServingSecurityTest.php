<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class FileServingSecurityTest extends TestCase
{
    private string $sentinelPath;
    private string $sentinelContent = 'SENTINEL_SECURITY_TEST_CONTENT_DO_NOT_SERVE';

    protected function setUp(): void
    {
        parent::setUp();
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $this->sentinelPath = $dir . DIRECTORY_SEPARATOR . 'sentinel_secret.txt';
        File::put($this->sentinelPath, $this->sentinelContent);
    }

    protected function tearDown(): void
    {
        if (File::exists($this->sentinelPath)) {
            File::delete($this->sentinelPath);
        }
        parent::tearDown();
    }

    public function test_api_storage_rechaza_path_traversal_con_puntos_y_barras(): void
    {
        $response = $this->get('/api/storage/../../framework/testing/sentinel_secret.txt');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
    }

    public function test_api_storage_rechaza_path_traversal_con_url_encoding(): void
    {
        $response = $this->get('/api/storage/..%2f..%2fframework/testing/sentinel_secret.txt');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
    }

    public function test_api_storage_rechaza_path_traversal_con_dot_encoding(): void
    {
        $response = $this->get('/api/storage/%2e%2e/%2e%2e/framework/testing/sentinel_secret.txt');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
    }

    public function test_api_storage_rechaza_path_traversal_con_doble_codificacion(): void
    {
        $response = $this->get('/api/storage/%252e%252e/%252e%252e/framework/testing/sentinel_secret.txt');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
    }

    public function test_api_storage_rechaza_byte_nulo(): void
    {
        $response = $this->get('/api/storage/test%00.png');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_api_storage_rechaza_archivos_ocultos(): void
    {
        $response = $this->get('/api/storage/.env');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_api_storage_rechaza_archivo_oculto_existente_en_disco(): void
    {
        $hiddenPath = storage_path('app/public/.oculto.png');
        File::put($hiddenPath, 'hidden_png_data');

        try {
            $response = $this->get('/api/storage/.oculto.png');
            $this->assertEquals(404, $response->getStatusCode());
        } finally {
            if (File::exists($hiddenPath)) {
                File::delete($hiddenPath);
            }
        }
    }

    public function test_api_storage_rechaza_extensiones_no_permitidas(): void
    {
        $scriptPath = storage_path('app/public/test_script.php');
        File::put($scriptPath, '<?php echo "malicious"; ?>');

        try {
            $response = $this->get('/api/storage/test_script.php');
            $this->assertEquals(404, $response->getStatusCode());
        } finally {
            if (File::exists($scriptPath)) {
                File::delete($scriptPath);
            }
        }
    }

    public function test_api_storage_rechaza_archivos_svg(): void
    {
        // SVG excluido de storage para mitigar Stored XSS
        $svgPath = storage_path('app/public/xss_vector.svg');
        File::put($svgPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        try {
            $response = $this->get('/api/storage/xss_vector.svg');
            $this->assertEquals(404, $response->getStatusCode());
        } finally {
            if (File::exists($svgPath)) {
                File::delete($svgPath);
            }
        }
    }

    public function test_api_storage_permite_archivo_con_puntos_consecutivos_en_el_nombre(): void
    {
        $file = storage_path('app/public/informe..final.pdf');
        File::put($file, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        try {
            $response = $this->get('/api/storage/informe..final.pdf');
            $this->assertEquals(200, $response->getStatusCode(), 'No debe rechazar archivos legítimos con .. dentro del nombre');
        } finally {
            if (File::exists($file)) {
                File::delete($file);
            }
        }
    }

    /**
     * NOTA DE AUDITORÍA (TEST VACUO):
     * Esta aserción es interceptada con 403 por la ruta signed 'storage.local' registrada automáticamente
     * por FilesystemServiceProvider de Laravel 11 ('serve' => true) antes de alcanzar routes/web.php.
     */
    public function test_web_storage_rechaza_archivos_svg(): void
    {
        $svgPath = storage_path('app/public/xss_vector.svg');
        File::put($svgPath, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        try {
            $response = $this->get('/storage/xss_vector.svg');
            $this->assertTrue(in_array($response->getStatusCode(), [404, 403], true));
        } finally {
            if (File::exists($svgPath)) {
                File::delete($svgPath);
            }
        }
    }

    public function test_api_storage_rechaza_carpeta_hermana_public_secret(): void
    {
        $secretDir = storage_path('app/public_secret');
        File::ensureDirectoryExists($secretDir);
        $secretFile = $secretDir . DIRECTORY_SEPARATOR . 'secret.png';
        File::put($secretFile, 'fake_png_data');

        try {
            // Intento de acceder a la carpeta hermana public_secret que comparte el prefijo "public"
            $response = $this->get('/api/storage/../public_secret/secret.png');
            $this->assertEquals(404, $response->getStatusCode());
            $this->assertFalse(str_contains($response->getContent(), 'fake_png_data'));
        } finally {
            if (File::exists($secretDir)) {
                File::deleteDirectory($secretDir);
            }
        }
    }

    public function test_is_path_within_base_rechaza_directorio_hermano_sin_separador_final(): void
    {
        $base = storage_path('app/public');
        $sibling = storage_path('app/public_secret');
        File::ensureDirectoryExists($base);
        File::ensureDirectoryExists($sibling);

        try {
            $this->assertFalse(
                \App\Support\SafeFileResolver::isPathWithinBase($sibling, $base),
                'isPathWithinBase debe rechazar un directorio hermano que comparta prefijo'
            );
        } finally {
            if (File::exists($sibling)) {
                File::deleteDirectory($sibling);
            }
        }
    }

    public function test_is_path_within_base_rechaza_ruta_sintactica_con_traversal_hacia_afuera(): void
    {
        $base = storage_path('app/public');
        $escapingPath = storage_path('app/public') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'testing';

        $this->assertFalse(
            \App\Support\SafeFileResolver::isPathWithinBase($escapingPath, $base),
            'Una ruta con traversal relativo que resuelva fuera debe rechazarse mediante realpath'
        );
    }

    public function test_api_storage_rechaza_symlink_que_apunte_fuera_de_la_base(): void
    {
        $symlinkPath = storage_path('app/public/symlink_out.png');

        // Intentar crear enlace simbolico si el sistema de archivos local lo soporta
        $symlinkCreated = false;
        try {
            if (function_exists('symlink')) {
                @symlink($this->sentinelPath, $symlinkPath);
                $symlinkCreated = file_exists($symlinkPath);
            }
        } catch (\Throwable $e) {
            $symlinkCreated = false;
        }

        if ($symlinkCreated) {
            try {
                $response = $this->get('/api/storage/symlink_out.png');
                $this->assertEquals(404, $response->getStatusCode(), 'Un symlink dentro de la base apuntando fuera debe rechazarse con 404');
                $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
            } finally {
                @unlink($symlinkPath);
            }
        } else {
            $this->markTestSkipped('Creacion de enlace simbolico no soportada en este entorno (Windows sin privilegios SeCreateSymbolicLinkPrivilege).');
        }
    }

    public function test_resolve_storage_file_rechaza_junction_ntfs_que_escapa_de_la_base(): void
    {
        $junctionPath = storage_path('app/public/junction_out');
        $targetDir = storage_path('framework/testing');
        $sentinelPng = $targetDir . DIRECTORY_SEPARATOR . 'secret.png';
        File::put($sentinelPng, 'SECRET_JUNCTION_PNG_DATA');

        $linkCreated = false;
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf('cmd.exe /c mklink /J %s %s 2>&1', escapeshellarg($junctionPath), escapeshellarg($targetDir));
            @exec($cmd, $output, $returnCode);
            $linkCreated = ($returnCode === 0 && is_dir($junctionPath));
        } else {
            if (function_exists('symlink')) {
                @symlink($targetDir, $junctionPath);
                $linkCreated = is_link($junctionPath);
            }
        }

        if (!$linkCreated) {
            if (File::exists($sentinelPng)) {
                File::delete($sentinelPng);
            }
            $this->markTestSkipped('No se pudo crear el enlace o junction de directorios en este entorno.');
        }

        try {
            // La junction está dentro de storage/app/public, no contiene ".." pero apunta físicamente fuera
            $resolved = \App\Support\SafeFileResolver::resolveStorageFile('junction_out/secret.png');
            $this->assertNull($resolved, 'resolveStorageFile debe devolver null para junctions que resuelvan fuera de la base');

            $response = $this->get('/api/storage/junction_out/secret.png');
            $this->assertEquals(404, $response->getStatusCode(), 'La ruta debe responder 404 ante archivos accedidos vía junction fuera de la base');
        } finally {
            // La junction o enlace se elimina SIEMPRE primero y NUNCA con borrado recursivo
            if (PHP_OS_FAMILY === 'Windows') {
                if (is_dir($junctionPath)) {
                    @exec(sprintf('cmd.exe /c rmdir %s', escapeshellarg($junctionPath)));
                }
            } else {
                if (is_link($junctionPath)) {
                    @unlink($junctionPath);
                }
            }

            clearstatcache(true, $junctionPath);
            $stillExists = (PHP_OS_FAMILY === 'Windows') ? is_dir($junctionPath) : is_link($junctionPath);
            if ($stillExists) {
                $this->fail("No se pudo desvincular la junction o enlace '{$junctionPath}'. Abortando limpieza de seguridad.");
            }

            if (File::exists($sentinelPng)) {
                File::delete($sentinelPng);
            }
        }

        // Verificar que el directorio target storage/framework/testing conservó su contenido intacto
        $this->assertTrue(File::exists($this->sentinelPath), 'El target framework/testing debe conservarse tras eliminar la junction');
    }

    public function test_resolve_storage_file_rechaza_junction_hacia_carpeta_hermana_public_secret(): void
    {
        $siblingDir = storage_path('app/public_secret');
        File::ensureDirectoryExists($siblingDir);
        $secretFile = $siblingDir . DIRECTORY_SEPARATOR . 'secret.png';
        File::put($secretFile, 'SECRET_SIBLING_PNG_DATA');

        $junctionPath = storage_path('app/public/junc_sibling');

        $linkCreated = false;
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = sprintf('cmd.exe /c mklink /J %s %s 2>&1', escapeshellarg($junctionPath), escapeshellarg($siblingDir));
            @exec($cmd, $output, $returnCode);
            $linkCreated = ($returnCode === 0 && is_dir($junctionPath));
        } else {
            if (function_exists('symlink')) {
                @symlink($siblingDir, $junctionPath);
                $linkCreated = is_link($junctionPath);
            }
        }

        if (!$linkCreated) {
            if (File::exists($siblingDir)) {
                File::deleteDirectory($siblingDir);
            }
            $this->markTestSkipped('No se pudo crear el enlace o junction de directorios hacia carpeta hermana en este entorno.');
        }

        try {
            $resolved = \App\Support\SafeFileResolver::resolveStorageFile('junc_sibling/secret.png');
            $this->assertNull(
                $resolved,
                'resolveStorageFile debe rechazar una junction que apunte a una carpeta hermana que comparta prefijo'
            );
        } finally {
            // La junction se elimina SIEMPRE antes de tocar public_secret, y nunca con borrado recursivo
            if (PHP_OS_FAMILY === 'Windows') {
                if (is_dir($junctionPath)) {
                    @exec(sprintf('cmd.exe /c rmdir %s', escapeshellarg($junctionPath)));
                }
            } else {
                if (is_link($junctionPath)) {
                    @unlink($junctionPath);
                }
            }

            clearstatcache(true, $junctionPath);
            $stillExists = (PHP_OS_FAMILY === 'Windows') ? is_dir($junctionPath) : is_link($junctionPath);
            if ($stillExists) {
                $this->fail("No se pudo desvincular la junction o enlace '{$junctionPath}'. NO se eliminará public_secret para evitar borrado involuntario de datos vinculados.");
            }

            if (File::exists($siblingDir)) {
                File::deleteDirectory($siblingDir);
            }
        }
    }

    public function test_cabeceras_de_seguridad_nosniff_y_content_disposition_en_pdf(): void
    {
        $pdfPath = storage_path('app/public/documento_test.pdf');
        File::put($pdfPath, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF");

        try {
            $response = $this->get('/api/storage/documento_test.pdf');
            $this->assertEquals(200, $response->getStatusCode());
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('Content-Type', 'application/pdf');
            $response->assertHeader('Cache-Control', 'no-store, private');

            $disposition = $response->headers->get('Content-Disposition');
            $this->assertNotNull($disposition);
            $this->assertStringContainsString('inline; filename="documento_test.pdf"', $disposition);
            $this->assertStringNotContainsString("\r", $disposition);
            $this->assertStringNotContainsString("\n", $disposition);
        } finally {
            if (File::exists($pdfPath)) {
                File::delete($pdfPath);
            }
        }
    }

    public function test_cabeceras_de_seguridad_cache_control_public_en_imagenes(): void
    {
        $imgPath = storage_path('app/public/imagen_test.png');
        // PNG de 1x1 transparente
        File::put($imgPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

        try {
            $response = $this->get('/api/storage/imagen_test.png');
            $this->assertEquals(200, $response->getStatusCode());
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('Content-Type', 'image/png');
            $response->assertHeader('Cache-Control', 'max-age=86400, public');
        } finally {
            if (File::exists($imgPath)) {
                File::delete($imgPath);
            }
        }
    }

    /**
     * NOTA DE AUDITORÍA (TEST VACUO):
     * Al igual que test_web_storage_rechaza_archivos_svg, es interceptada por la ruta signed
     * 'storage.local' de Laravel 11 antes de que tome efecto routes/web.php.
     */
    public function test_web_storage_rechaza_path_traversal(): void
    {
        $response = $this->get('/storage/../../framework/testing/sentinel_secret.txt');

        $this->assertTrue(in_array($response->getStatusCode(), [404, 403], true));
        $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
    }

    public function test_web_assets_rechaza_path_traversal(): void
    {
        $response = $this->get('/assets/../../storage/framework/testing/sentinel_secret.txt');

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse(str_contains($response->getContent(), $this->sentinelContent));
    }

    public function test_api_storage_cv_fotos_inexistente_retorna_avatar_svg_200(): void
    {
        $response = $this->get('/api/storage/cv_fotos/18/foto_1793846998.png');

        $this->assertEquals(200, $response->getStatusCode());
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }
}
