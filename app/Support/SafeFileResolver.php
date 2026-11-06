<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SafeFileResolver
{
    /**
     * Extensiones y tipos MIME permitidos para almacenamiento de usuario.
     * SVG excluido intencionalmente para prevenir XSS almacenado.
     */
    public const STORAGE_ALLOWED_MIMES = [
        'pdf'  => 'application/pdf',
        'webp' => 'image/webp',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
    ];

    /**
     * Extensiones y tipos MIME permitidos para assets estáticos del frontend.
     */
    public const ASSET_ALLOWED_MIMES = [
        'js'    => 'application/javascript',
        'css'   => 'text/css',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'gif'   => 'image/gif',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'ico'   => 'image/x-icon',
    ];

    /**
     * Resolver y validar de forma estricta un archivo dentro de los directorios de almacenamiento.
     */
    public static function resolveStorageFile(string $rawPath): ?string
    {
        return self::resolveFileWithinBases(
            $rawPath,
            [
                storage_path('app/public'),
                public_path('storage'),
            ],
            self::STORAGE_ALLOWED_MIMES
        );
    }

    /**
     * Resolver y validar de forma estricta un asset dentro de public/assets o dist/assets.
     */
    public static function resolveAssetFile(string $rawFile): ?string
    {
        return self::resolveFileWithinBases(
            $rawFile,
            [
                public_path('assets'),
                base_path('dist/assets'),
            ],
            self::ASSET_ALLOWED_MIMES
        );
    }

    /**
     * Validar si un directorio de destino se encuentra confinado dentro de un directorio base.
     */
    public static function isPathWithinBase(string $targetDir, string $baseDir): bool
    {
        return self::confinePathWithinBase($targetDir, $baseDir, false) !== null;
    }

    /**
     * Sanitizar un nombre de archivo para evitar inyecciones en cabeceras HTTP.
     */
    public static function sanitizeFilename(string $filename): string
    {
        $clean = str_replace(["\r", "\n", "\0", '"', "'", '\\', '/'], '', $filename);
        $clean = preg_replace('/[^A-Za-z0-9._\-]/', '_', $clean);
        return $clean ?: 'archivo_descarga';
    }

    /**
     * Servir un archivo de almacenamiento validado con cabeceras de seguridad.
     */
    public static function serveStorage(string $path): \Symfony\Component\HttpFoundation\Response
    {
        $realFile = self::resolveStorageFile($path);
        if (!$realFile) {
            // Si el archivo físico desapareció del disco efímero de Render (ej. fotos de CV previas a la persistencia Base64)
            if (preg_match('#^cv_fotos/\d+/foto_\d+\.(png|jpe?g|webp)$#i', $path)) {
                return self::serveDefaultAvatarSvg();
            }
            abort(404);
        }

        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mime = self::STORAGE_ALLOWED_MIMES[$ext] ?? 'application/octet-stream';
        $safeFilename = self::sanitizeFilename(basename($realFile));

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type'           => $mime,
        ];

        if ($ext === 'pdf') {
            $headers['Content-Disposition'] = "inline; filename=\"{$safeFilename}\"";
        }

        $response = response()->file($realFile, $headers);
        if ($ext === 'pdf') {
            $response->setPrivate();
            $response->headers->addCacheControlDirective('no-store');
        } else {
            $response->setPublic();
            $response->setMaxAge(86400);
        }

        return $response;
    }

    /**
     * Retorna un avatar SVG neutral con código 200 cuando una foto histórica de CV
     * no se encuentra en el disco efímero del servidor, evitando errores 404 en la consola.
     */
    public static function serveDefaultAvatarSvg(): \Symfony\Component\HttpFoundation\Response
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128">'
             . '<circle cx="64" cy="64" r="64" fill="#E2E8F0"/>'
             . '<circle cx="64" cy="46" r="24" fill="#94A3B8"/>'
             . '<path d="M20,112 C20,88 39,78 64,78 C89,78 108,88 108,112 Z" fill="#94A3B8"/>'
             . '</svg>';

        return response($svg, 200, [
            'Content-Type'           => 'image/svg+xml',
            'Cache-Control'          => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Servir un asset estático validado con cabeceras de seguridad.
     */
    public static function serveAsset(string $file): BinaryFileResponse
    {
        $realFile = self::resolveAssetFile($file);
        if (!$realFile) {
            abort(404);
        }

        $ext = strtolower(pathinfo($realFile, PATHINFO_EXTENSION));
        $mime = self::ASSET_ALLOWED_MIMES[$ext] ?? 'application/octet-stream';

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Content-Type'           => $mime,
            'Cache-Control'          => 'public, max-age=31536000, immutable',
        ];

        return response()->file($realFile, $headers);
    }

    /**
     * Lógica central de resolución y validación sin duplicación.
     */
    private static function resolveFileWithinBases(string $rawInput, array $baseDirs, array $allowedMimes): ?string
    {
        // 1. Decodificar URL
        $decoded = rawurldecode($rawInput);

        // 2. Rechazar byte nulo y separadores inversos Windows
        if (str_contains($decoded, "\0") || str_contains($decoded, '\\')) {
            return null;
        }

        // 3. Rechazar segmentos de salto de directorio (..) y archivos/carpetas ocultos (.env, .git)
        $segments = explode('/', $decoded);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '..' || $segment === '.' || str_starts_with($segment, '.')) {
                return null;
            }
        }

        // 4. Validar extensión contra lista blanca estricta
        $ext = strtolower(pathinfo($decoded, PATHINFO_EXTENSION));
        if (!isset($allowedMimes[$ext])) {
            return null;
        }

        // 5. Validar confinamiento estricto dentro de cualquiera de las bases permitidas
        foreach ($baseDirs as $baseDir) {
            $candidatePath = rtrim($baseDir, '\\/') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $decoded);
            $confinedFile = self::confinePathWithinBase($candidatePath, $baseDir, true);

            if ($confinedFile !== null) {
                return $confinedFile;
            }
        }

        return null;
    }

    /**
     * Motor central de confinamiento: valida físicamente mediante realpath con separador final.
     * Retorna la ruta física canónica si es válida, o null si no existe o escapa.
     */
    private static function confinePathWithinBase(string $candidatePath, string $baseDir, bool $mustBeFile = false): ?string
    {
        $realBase = realpath($baseDir);
        $realTarget = realpath($candidatePath);

        if (!$realBase || !$realTarget) {
            return null;
        }

        if ($mustBeFile && !is_file($realTarget)) {
            return null;
        }

        $baseWithSep = rtrim($realBase, '\\/') . DIRECTORY_SEPARATOR;
        $targetWithSep = rtrim($realTarget, '\\/') . ($mustBeFile ? '' : DIRECTORY_SEPARATOR);

        $checkTarget = $mustBeFile ? $realTarget : $targetWithSep;

        if (str_starts_with($checkTarget, $baseWithSep)) {
            return $realTarget;
        }

        return null;
    }
}

