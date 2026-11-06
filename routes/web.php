<?php

use Illuminate\Support\Facades\Route;
use App\Support\SafeFileResolver;

// Rutas de diagnóstico exclusivas para entorno local
if (app()->isLocal()) {
Route::get('/debug-db', function () {
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $dbName = \Illuminate\Support\Facades\DB::connection()->getDatabaseName();
        $userCount = \App\Models\User::count();
        return response()->json([
            'status' => 'connected',
            'database' => $dbName,
            'driver' => \Illuminate\Support\Facades\DB::connection()->getDriverName(),
            'users' => $userCount,
            'env_db_url' => env('DB_URL') ? 'set' : 'not_set',
            'env_database_url' => env('DATABASE_URL') ? 'set' : 'not_set',
            'env_db_conn' => env('DB_CONNECTION'),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], 500);
    }
});

Route::get('/debug-logs', function () {
    $logFile = storage_path('logs/laravel.log');
    if (file_exists($logFile)) {
        $content = file_get_contents($logFile);
        return response(substr($content, -8000), 200, ['Content-Type' => 'text/plain']);
    }
    return response('No log file found at ' . $logFile, 200);
});

Route::get('/debug-codigo', function () {
    $codigo = \App\Models\CodigoRecuperacion::latest()->first();
    return response()->json([
        'correo' => $codigo ? $codigo->correo : null,
        'codigo' => $codigo ? $codigo->codigo : null,
        'valido' => $codigo ? $codigo->esValido() : false,
        'creado' => $codigo ? $codigo->created_at : null,
    ]);
});
}

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

// Ruta para servir assets compilados con proteccion contra Path Traversal
Route::get('/assets/{file}', function ($file) {
    return SafeFileResolver::serveAsset($file);
})->where('file', '[A-Za-z0-9._\-]+');

// Ruta de respaldo (Fallback) para servir archivos de almacenamiento publico con aislamiento estricto
Route::get('/storage/{path}', function ($path) {
    return SafeFileResolver::serveStorage($path);
})->where('path', '[A-Za-z0-9._\-/]+');

// Rutas SPA para landing, modales y paneles del sistema (pasante, estudiante, etc.)
$spaHandler = function () {
    $indexPath = base_path('dist/index.html');
    if (file_exists($indexPath)) {
        return response(file_get_contents($indexPath))->header('Content-Type', 'text/html');
    }
    return redirect('/');
};

Route::get('/login', $spaHandler)->name('login');
Route::get('/registro', $spaHandler);
Route::get('/recuperar', $spaHandler);
Route::get('/recuperar-password', $spaHandler);
Route::get('/empresas', $spaHandler);
Route::get('/areas', $spaHandler);
Route::get('/requisitos', $spaHandler);
Route::get('/alcance', $spaHandler);

Route::get('/pasante/{any?}', $spaHandler)->where('any', '.*');
Route::get('/estudiante/{any?}', $spaHandler)->where('any', '.*');
Route::get('/supervisor/{any?}', $spaHandler)->where('any', '.*');
Route::get('/vicedecano/{any?}', $spaHandler)->where('any', '.*');
Route::get('/panel-pasante/{any?}', $spaHandler)->where('any', '.*');
Route::get('/panel/{any?}', $spaHandler)->where('any', '.*');
Route::get('/dashboard/{any?}', $spaHandler)->where('any', '.*');
