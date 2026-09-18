<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\JwtService;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Token de sesión no proporcionado. Por favor, inicie sesión.'
            ], 401);
        }

        if (JwtService::esTokenRevocado($token)) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'El token ha sido destruido al cerrar la sesión. Inicie sesión nuevamente.'
            ], 401);
        }

        $payload = JwtService::validarToken($token);

        if (!$payload) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Token de sesión inválido o ha expirado. Por favor, inicie sesión nuevamente.'
            ], 401);
        }

        // Adjuntar datos del usuario autenticado a la solicitud
        $request->attributes->set('jwt_payload', $payload);
        $request->attributes->set('jwt_user', $payload->user ?? null);

        return $next($request);
    }
}
