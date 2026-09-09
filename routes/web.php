<?php

use Illuminate\Support\Facades\Route;

// Ruta principal y SPA: sirve la interfaz de Génesis Profesional compilada
Route::get('/', function () {
    $indexPath = base_path('dist/index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath))->header('Content-Type', 'text/html');
    }
    return response()->json([
        'aplicacion' => 'Génesis Profesional API',
        'version' => '1.0.0',
        'estado' => 'activo',
        'mensaje' => 'Frontend no compilado. Ejecute npm run build.'
    ], 200, [], JSON_UNESCAPED_UNICODE);
});

// Ruta para servir assets compilados
Route::get('/assets/{file}', function ($file) {
    $path = public_path("assets/{$file}");
    if (!file_exists($path)) {
        $path = base_path("dist/assets/{$file}");
    }
    if (file_exists($path)) {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        $contentType = match ($extension) {
            'js' => 'application/javascript',
            'css' => 'text/css',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            default => 'application/octet-stream',
        };
        return response()->file($path, ['Content-Type' => $contentType]);
    }
    abort(404);
})->where('file', '.*');

// Ruta de respaldo (Fallback) para servir archivos de almacenamiento público en entornos con problemas de enlaces simbólicos (ej. OneDrive)
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path("app/public/{$path}");
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }
    abort(404);
})->where('path', '.*');

// Rutas SPA para login / registro / dashboard
Route::get('/login', function () {
    $indexPath = base_path('dist/index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath))->header('Content-Type', 'text/html');
    }
    return redirect('/');
})->name('login');

Route::get('/registro', function () {
    $indexPath = base_path('dist/index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath))->header('Content-Type', 'text/html');
    }
    return redirect('/');
});

Route::get('/dashboard/{any}', function () {
    $indexPath = base_path('dist/index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath))->header('Content-Type', 'text/html');
    }
    return redirect('/');
})->where('any', '.*');
