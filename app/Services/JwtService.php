<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\User;
use Exception;

class JwtService
{
    /**
     * Obtener la clave secreta para la firma desde config('app.key').
     */
    private static function obtenerSecretKey(): string
    {
        $key = config('app.key');
        if (str_starts_with($key, 'base64:')) {
            return base64_decode(substr($key, 7));
        }
        return $key ?: 'GenesisProfesionalSecretKeyDefault123!';
    }

    /**
     * Generar un token JWT firmado para un usuario.
     * 
     * @param User $user
     * @param int|null $ttlSegundos Tiempo de vida en segundos (por defecto 24h = 86400s)
     * @return string
     */
    public static function generarToken(User $user, ?int $ttlSegundos = null): string
    {
        $ttl = $ttlSegundos ?? (int) env('JWT_TTL', 86400);
        $ahora = time();

        $payload = [
            'iss' => config('app.url', 'http://localhost'),
            'jti' => (string) Str::uuid(),
            'iat' => $ahora,
            'nbf' => $ahora,
            'exp' => $ahora + $ttl,
            'sub' => $user->id,
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'correo' => $user->correo_institucional,
                'rol' => $user->rol,
            ]
        ];

        return JWT::encode($payload, self::obtenerSecretKey(), 'HS256');
    }

    /**
     * Decodificar y validar un token JWT.
     *
     * @param string $token
     * @return object|null Devuelve el payload si es válido, o null si expiró / no es válido o está revocado.
     */
    public static function validarToken(string $token): ?object
    {
        if (self::esTokenRevocado($token)) {
            return null;
        }

        try {
            return JWT::decode($token, new Key(self::obtenerSecretKey(), 'HS256'));
        } catch (ExpiredException $e) {
            return null;
        } catch (SignatureInvalidException $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Destruir e invalidar un token JWT agregándolo a la lista negra en Cache.
     *
     * @param string $token
     * @return bool
     */
    public static function invalidarToken(string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        try {
            $payload = self::validarToken($token);
            $exp = $payload->exp ?? (time() + 86400);
            $ttlRestante = max(60, $exp - time());

            // Agregar el hash del token a la lista negra por la duración restante de su validez
            $tokenHash = md5($token);
            Cache::put('jwt_blacklist:' . $tokenHash, true, $ttlRestante);
            return true;
        } catch (Exception $e) {
            // Incluso si no se decodifica por expiración, agregarlo a la lista negra por al menos 1 hora
            Cache::put('jwt_blacklist:' . md5($token), true, 3600);
            return true;
        }
    }

    /**
     * Comprobar si el token está presente en la lista negra.
     *
     * @param string $token
     * @return bool
     */
    public static function esTokenRevocado(string $token): bool
    {
        if (empty($token)) {
            return true;
        }
        return Cache::has('jwt_blacklist:' . md5($token));
    }
}
