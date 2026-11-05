<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use App\Models\User;

class CvStorageService
{
    /**
     * Guarda el archivo PDF decodificado en almacenamiento público.
     */
    public function guardarPdf(int $usuarioId, string $pdfBase64, ?string $tituloCv, ?string $nombreUsuario = 'Usuario'): array
    {
        $pdfData = base64_decode(preg_replace('#^data:.*?base64,#i', '', $pdfBase64));
        $titulo = $tituloCv ?: ('CV_' . $nombreUsuario);
        $nombreArchivo = str_replace(' ', '_', $titulo) . '_' . $usuarioId . '_' . time() . '.pdf';
        $carpeta = "cvs/{$usuarioId}";
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
     * Procesa y almacena la fotografía de perfil en disco.
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
            }
            $fotoDecoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $fotoInput));
            $nombreFoto = 'foto_' . time() . '.' . $ext;
            $carpetaFoto = "cv_fotos/{$usuarioId}";
            $rutaFoto = "{$carpetaFoto}/{$nombreFoto}";

            Storage::disk('public')->put($rutaFoto, $fotoDecoded);

            $publicPath = public_path("storage/{$rutaFoto}");
            $dirPath = dirname($publicPath);
            if (!is_dir($dirPath)) {
                mkdir($dirPath, 0755, true);
            }
            file_put_contents($publicPath, $fotoDecoded);

            return "/api/storage/{$rutaFoto}";
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
            // Silencioso ante variaciones de columnas en entornos heterogéneos
        }
    }
}

