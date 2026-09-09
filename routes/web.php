<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Rutas de API
Route::prefix('api')->group(function () {
    // Autenticación
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.login');
    Route::post('/auth/registro', [AuthController::class, 'registro'])->name('api.registro');
});

// Ruta para la vista de login (Blade) - Si prefieren usar blade en vez de Vue
Route::get('/login', function () {
    return view('login');
})->name('login');

// Ruta principal para la landing page (Blade)
Route::get('/', function () {
    return view('welcome');
});

// Ruta de respaldo (Fallback) para servir archivos de almacenamiento público en entornos con problemas de enlaces simbólicos (ej. OneDrive)
Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path("app/public/{$path}");
    if (file_exists($filePath)) {
        return response()->file($filePath);
    }
    abort(404);
})->where('path', '.*');

// Ruta "Catch-all" para la aplicación Vue (Dashboards)
Route::get('/dashboard/{any}', function () {
    // Retornamos una vista especial blade que contendrá el div #app para Vue
    return view('app');
})->where('any', '.*');
