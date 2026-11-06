<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\SafeFileResolver;
use App\Models\User;

class CvStorageService
{
    /**
     * Guarda el archivo PDF decodificado en almacenamiento público.
     */
    public function guardarPdf(int $usuarioId, string $pdfBase64, ?string $tituloCv, ?string $nombreUsuario = 'Usuario'): array
    {
        // 1. Validar longitud de cadena Base64 antes de decodificar (máximo ~14MB base64 para 10MB binario)
        $maxBase64Length = 14 * 1024 * 1024;
        if (strlen($pdfBase64) > $maxBase64Length) {
            throw new \InvalidArgumentException('El archivo excede el tamaño máximo permitido de 10MB.');
        }

        // 2. Extraer la porción base64 tolerando "data:" en cualquier mayúscula/minúscula y metadata (ej. filename=)
        $cleanBase64 = $pdfBase64;
        if (stripos($pdfBase64, 'data:') === 0) {
            $commaPos = strpos($pdfBase64, ',');
            if ($commaPos !== false) {
                $cleanBase64 = substr($pdfBase64, $commaPos + 1);
            }
        }

        // 3. Tolerar y normalizar: eliminar saltos de línea (\r, \n) y convertir espacios a '+' (decodificación HTTP/URL)
        $cleanBase64 = str_replace(["\r", "\n"], '', $cleanBase64);
        $cleanBase64 = str_replace(' ', '+', $cleanBase64);

        $pdfData = base64_decode($cleanBase64, true);

        if ($pdfData === false) {
            throw new \InvalidArgumentException('Formato de datos base64 inválido.');
        }

        // 2. Validar tamaño máximo binario (10MB)
        $maxBytes = 10 * 1024 * 1024;
        if (strlen($pdfData) > $maxBytes) {
            throw new \InvalidArgumentException('El archivo excede el tamaño máximo permitido de 10MB.');
        }

        // 3. Validar cabecera mágica %PDF-
        if (!str_starts_with($pdfData, '%PDF-')) {
            throw new \InvalidArgumentException('El archivo no tiene una cabecera PDF válida.');
        }

        $titulo = $tituloCv ?: ('CV_' . $nombreUsuario);
        $slugTitulo = Str::slug($titulo, '_') ?: 'cv';
        $safeUserId = (int)$usuarioId;
        $nombreArchivo = "{$slugTitulo}_{$safeUserId}_" . time() . '.pdf';
        $carpeta = "cvs/{$safeUserId}";
        $ruta = "{$carpeta}/{$nombreArchivo}";

        Storage::disk('public')->put($ruta, $pdfData);
        $urlPublica = "/api/storage/{$ruta}";

        return [
            'nombre_archivo' => $nombreArchivo,
            'ruta_archivo'   => $ruta,
            'url_publica'    => $urlPublica,
            'titulo_cv'      => $titulo,
        ];
    }

    /**
     * Procesa y almacena la fotografía de perfil.
     * En Opción 1 (Persistencia en Base de Datos): guarda la copia de respaldo en disco
     * y retorna la cadena Base64 directamente para almacenarla en la columna 'foto_url' (TEXT)
     * de PostgreSQL, garantizando permanencia ante reinicios de Render (disco efímero)
     * y eliminando cualquier dependencia o error 429 de servicios externos como Imgur.
     */
    public function procesarFoto(int $usuarioId, ?string $fotoInput, ?string $fotoActual = null): ?string
    {
        if (empty($fotoInput)) {
            if ($fotoActual) {
                return str_starts_with($fotoActual, '/storage/') ? ('/api' . $fotoActual) : $fotoActual;
            }
            return null;
        }

        if (preg_match('#^data:image/(\w+);base64,#i', $fotoInput, $matches)) {
            $ext = strtolower($matches[1]);
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $ext = 'png';
                $fotoInput = preg_replace('#^data:image/\w+;base64,#i', 'data:image/png;base64,', $fotoInput);
            }
            $fotoDecoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $fotoInput));
            $nombreFoto = 'foto_' . time() . '.' . $ext;
            $carpetaFoto = "cv_fotos/{$usuarioId}";
            $rutaFoto = "{$carpetaFoto}/{$nombreFoto}";

            Storage::disk('public')->put($rutaFoto, $fotoDecoded);

            // Sincronización física únicamente en entorno local (ej. Windows/OneDrive)
            if (app()->isLocal()) {
                $safeNombre = \App\Support\SafeFileResolver::sanitizeFilename(basename($nombreFoto));
                $safeFolder = (int)$usuarioId;
                $dirPath = public_path("storage/cv_fotos/{$safeFolder}");
                \Illuminate\Support\Facades\File::ensureDirectoryExists($dirPath, 0755);
                $publicPath = $dirPath . DIRECTORY_SEPARATOR . $safeNombre;
                if (\App\Support\SafeFileResolver::isPathWithinBase($dirPath, public_path('storage'))) {
                    \Illuminate\Support\Facades\File::put($publicPath, $fotoDecoded);
                }
            }

            // Opción 1: Retornar Base64 para persistencia permanente en PostgreSQL
            return $fotoInput;
        }

        return str_starts_with($fotoInput, '/storage/') ? ('/api' . $fotoInput) : $fotoInput;
    }

    /**
     * Sincroniza la foto del usuario en la tabla de usuarios.
     */
    public function sincronizarFotoUsuario(int $usuarioId, ?string $fotoUrl): void
    {
        if (!$usuarioId || !$fotoUrl) {
            return;
        }

        try {
            $user = User::find($usuarioId);
            if ($user) {
                $user->foto = $fotoUrl;
                $user->foto_url = $fotoUrl;
                $user->save();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("No se pudo sincronizar avatar de usuario en CvStorageService", [
                'user_id' => $usuarioId,
                'error' => $e->getMessage()
            ]);
        }
    }
}

