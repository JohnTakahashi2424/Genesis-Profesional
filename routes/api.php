<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

    // Inicio de sesión con protección contra enumeración de usuarios
    Route::post('/login', [AuthController::class, 'login'])->name('api.auth.login');
});
