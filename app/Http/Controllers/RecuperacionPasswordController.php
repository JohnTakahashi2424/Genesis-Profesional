<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EnviarCodigoRequest;
use App\Http\Requests\VerificarCodigoRequest;
use App\Http\Requests\RestablecerContrasenaRequest;
use App\Models\User;
use App\Models\CodigoRecuperacion;
use App\Services\BrevoMailService;

class RecuperacionPasswordController extends Controller
{
    /**
     * Endpoint para comprobación en tiempo real de existencia de cuenta para recuperación
     * POST /api/auth/verificar-correo-recuperacion
     */
    public function verificarCorreoRecuperacion(Request $request)
    {
        $request->validate([
            'correo' => 'required|email|max:100'
        ]);

        $correo = strtolower(trim($request->correo));
        $user = User::where('correo_institucional', $correo)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'existe' => false,
                'mensaje' => 'No se encontró ninguna cuenta asociada a este correo institucional.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'existe' => true,
            'mensaje' => 'Cuenta encontrada.'
        ], 200);
    }

    /**
     * Endpoint para solicitar código de recuperación de contraseña (6 dígitos vía SMTP)
     * POST /api/auth/enviar-codigo
     */
    public function enviarCodigo(EnviarCodigoRequest $request)
    {
        $correo = strtolower(trim($request->correo));

        // 1. Buscar que el usuario exista en la tabla users
        $user = User::where('correo_institucional', $correo)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No se encontró ninguna cuenta asociada a este correo institucional.'
            ], 404);
        }

        // 2. Generar código de 6 dígitos con 1 minuto y 30 segundos de vigencia (90 segundos)
        $registroCodigo = CodigoRecuperacion::generarParaCorreo($correo);

        // 3. Enviar correo mediante SMTP
        $envio = BrevoMailService::enviarCodigo($correo, $user->nombres, $registroCodigo->codigo);

        // 4. Enmascarar el correo según maqueta de interfaz (ej. us***********)
        $partes = explode('@', $correo);
        $prefijo = $partes[0];
        $longitudPrefijo = strlen($prefijo);
        $enmascarado = substr($prefijo, 0, 2) . str_repeat('*', max($longitudPrefijo - 2, 8));

        $respuesta = [
            'status' => 'success',
            'mensaje' => 'Se ha enviado un código de verificación a tu correo electrónico.',
            'correo_enmascarado' => $enmascarado,
            'tiempo_expiracion_segundos' => 90
        ];

        if (!$envio['exito']) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible enviar el código de verificación al correo ingresado. Por favor, intente más tarde.'
            ], 500);
        }

        return response()->json($respuesta, 200);
    }

    /**
     * Endpoint para verificar el código de 6 dígitos ingresado
     * POST /api/auth/verificar-codigo
     */
    public function verificarCodigo(VerificarCodigoRequest $request)
    {
        $correo = strtolower(trim($request->correo));
        $codigoIngresado = trim($request->codigo);

        // Buscar el código más reciente generado para este correo
        $registro = CodigoRecuperacion::where('correo', $correo)
            ->where('utilizado', false)
            ->latest()
            ->first();

        if (!$registro || !$registro->esValido()) {
            return response()->json([
                'status' => 'error',
                'valido' => false,
                'mensaje' => 'El código de verificación ha expirado o no es válido. Solicita un nuevo código.'
            ], 422);
        }

        // Validar coincidencia de código
        if ($registro->codigo !== $codigoIngresado) {
            $registro->increment('intentos');

            return response()->json([
                'status' => 'error',
                'valido' => false,
                'mensaje' => 'Código de verificación incorrecto. Inténtalo de nuevo.'
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'valido' => true,
            'mensaje' => 'Código de verificación confirmado con éxito.'
        ], 200);
    }

    /**
     * Endpoint para restablecer la contraseña tras validar el código
     * POST /api/auth/recuperar
     */
    public function recuperar(RestablecerContrasenaRequest $request)
    {
        $correo = strtolower(trim($request->correo));
        $codigo = trim($request->codigo);
        $nuevaContrasena = $request->contrasena;

        // 1. Verificar validez del código
        $registro = CodigoRecuperacion::where('correo', $correo)
            ->where('codigo', $codigo)
            ->where('utilizado', false)
            ->latest()
            ->first();

        if (!$registro || !$registro->esValido()) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'El código de verificación ha expirado o ya fue utilizado.'
            ], 422);
        }

        // 2. Buscar usuario
        $user = User::where('correo_institucional', $correo)->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No se encontró la cuenta de usuario especificada.'
            ], 404);
        }

        // 3. Actualizar contraseña (se aplica hash automático del modelo User)
        $user->password = $nuevaContrasena;
        $user->save();

        // 4. Invalidar el código utilizado
        $registro->update(['utilizado' => true]);

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Tu contraseña ha sido restablecida exitosamente. Ahora puedes iniciar sesión con tu nueva contraseña.'
        ], 200);
    }
}

