<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

use App\Http\Controllers\PasantiaController;

/*
|--------------------------------------------------------------------------
| Rutas de la API (Genesis Profesional)
|--------------------------------------------------------------------------
|
| Todas las rutas definidas aquí son cargadas automáticamente por Laravel
| bajo el prefijo '/api' y aplican el grupo de middleware 'api' (stateless).
|
*/

Route::prefix('auth')->group(function () {
    // Registro de usuarios comprobando padrón de estudiantes
    Route::post('/registro', [AuthController::class, 'registro'])->name('api.auth.registro');

    // Verificación preliminar de correo institucional
    Route::post('/verificar-correo', [AuthController::class, 'verificarCorreo'])->name('api.auth.verificar_correo');

    // Inicio de sesión con protección contra enumeración de usuarios
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');

    // Verificación preliminar de correo para recuperación
    Route::post('/verificar-correo-recuperacion', [AuthController::class, 'verificarCorreoRecuperacion'])->name('api.auth.verificar_correo_recuperacion');

    // Recuperación de contraseña: envío de código vía Brevo SMTP
    Route::post('/enviar-codigo', [AuthController::class, 'enviarCodigo'])->name('api.auth.enviar_codigo');

    // Recuperación de contraseña: comprobación del código de 6 dígitos
    Route::post('/verificar-codigo', [AuthController::class, 'verificarCodigo'])->name('api.auth.verificar_codigo');

    // Recuperación de contraseña: cambio seguro de contraseña
    Route::post('/recuperar', [AuthController::class, 'recuperar'])->name('api.auth.recuperar');
});

// Rutas de Pasantías
Route::get('/pasante/estado', [PasantiaController::class, 'obtenerEstado'])->name('api.pasante.estado');

// Rutas del Módulo de Curriculum Vitae (CV)
use App\Http\Controllers\CvController;

Route::prefix('cv')->group(function () {
    Route::post('/guardar', [CvController::class, 'guardar'])->name('api.cv.guardar');
    Route::get('/obtener/{usuarioId}', [CvController::class, 'obtener'])->name('api.cv.obtener');
    Route::delete('/eliminar/{cvId}', [CvController::class, 'eliminar'])->name('api.cv.eliminar');
});

// Serving storage files cleanly via API to avoid Windows symlink 403 errors
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path("app/public/{$path}");
    if (!file_exists($filePath)) {
        $filePath = public_path("storage/{$path}");
    }
    if (file_exists($filePath) && !is_dir($filePath)) {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        $mime = match (strtolower($ext)) {
            'pdf'  => 'application/pdf',
            'webp' => 'image/webp',
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'  => 'image/gif',
            'svg'  => 'image/svg+xml',
            default => 'application/octet-stream',
        };
        return response()->file($filePath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }
    return response()->json(['mensaje' => 'Archivo no encontrado'], 404);
})->where('path', '.*');
