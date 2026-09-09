<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Requests\RegistroRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\EnviarCodigoRequest;
use App\Http\Requests\VerificarCodigoRequest;
use App\Http\Requests\RestablecerContrasenaRequest;
use App\Models\User;
use App\Models\Estudiante;
use App\Models\CodigoRecuperacion;
use App\Mail\CodigoRecuperacionMail;

class AuthController extends Controller
{
    /**
     * Endpoint de Inicio de Sesión (Protegido contra enumeración de usuarios / phishing)
     * POST /api/auth/login
     */
    public function login(LoginRequest $request)
    {
        $correo = strtolower(trim($request->correo));
        $contrasena = $request->contrasena;

        // Buscar el usuario en la tabla 'users'
        $user = User::where('correo_institucional', $correo)->first();

        // Política Anti-Phishing (OWASP): No revelar si el fallo se debe al correo o a la contraseña
        // Coincide con mensaje exacto de la interfaz gráfica: "Correo o contraseña incorrectos. Intentalo de nuevo."
        if (!$user || !Hash::check($contrasena, $user->password)) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Correo o contraseña incorrectos. Intentalo de nuevo.'
            ], 401);
        }

        // Verificar estado de la cuenta
        if ($user->estado !== 'activo') {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'La cuenta no se encuentra activa en el sistema.'
            ], 403);
        }

        // Generar token de sesión para autenticación en cliente
        $token = Str::random(64);

        $usuarioData = [
            'id' => $user->id,
            'nombres' => $user->nombres,
            'apellidos' => $user->apellidos,
            'correo' => $user->correo_institucional,
            'rol' => $user->rol,
        ];

        // Si es un estudiante, enriquecer los datos con su información del padrón de estudiantes
        if ($user->rol === 'estudiante' && $user->estudiante) {
            $usuarioData['codigo_estudiante'] = $user->estudiante->codigo_estudiante;
            $usuarioData['carrera'] = $user->estudiante->carrera;
        }

        // Rutas sugeridas para redirección en frontend
        $rutas = [
            'estudiante' => '/dashboard/estudiante',
            'pasante' => '/dashboard/pasante',
            'supervisor' => '/dashboard/supervisor',
            'vice_decano' => '/dashboard/vicedecano'
        ];

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Inicio de sesión exitoso.',
            'token' => $token,
            'usuario' => $usuarioData,
            'redireccion' => $rutas[$user->rol] ?? '/dashboard'
        ], 200);
    }

    /**
     * Endpoint de Registro con la Entidad User
     * POST /api/auth/registro
     */
    public function registro(RegistroRequest $request)
    {
        // 1. Sanitización de entradas (limpieza de espacios múltiples y normalización a minúsculas)
        $nombres = trim(preg_replace('/\s+/', ' ', $request->nombres));
        $apellidos = trim(preg_replace('/\s+/', ' ', $request->apellidos));
        $correo = strtolower(trim($request->correo));
        $contrasena = $request->contrasena;

        // 2. Determinación de rol inicial escalable
        $rol = 'estudiante';
        if (str_contains($correo, 'decano') || str_contains($correo, 'vicedecano')) {
            $rol = 'vice_decano';
        } elseif (!Str::startsWith($correo, 'us')) {
            $rol = 'supervisor';
        }

        // 3. Verificación contra el padrón de Estudiantes
        // Comprobar si el estudiante está registrado y activo en la tabla de estudiantes
        $estudiante = null;
        if ($rol === 'estudiante') {
            $estudiante = Estudiante::where('correo_secundario', $correo)->first();

            // Verificación: existencia del estudiante en la base de datos de estudiantes
            if (!$estudiante) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'No fue posible procesar el registro con el correo institucional proporcionado. Verifique sus datos o contacte a administración.'
                ], 400);
            }

            // Verificación: estado académico activo
            if (!$estudiante->es_estudiante_activo) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'El estudiante asociado a este correo institucional no se encuentra en estado activo en el sistema académico.'
                ], 403);
            }
        }

        // 4. Transacción atómica en la base de datos PostgreSQL
        DB::beginTransaction();

        try {
            $user = User::create([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo_institucional' => $correo,
                'password' => $contrasena, // Cast automático a hashed en el modelo User
                'rol' => $rol,
                'estado' => 'activo',
            ]);

            DB::commit();

            $usuarioData = [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'correo' => $user->correo_institucional,
                'rol' => $user->rol,
            ];

            if ($estudiante) {
                $usuarioData['codigo_estudiante'] = $estudiante->codigo_estudiante;
                $usuarioData['carrera'] = $estudiante->carrera;
            }

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Usuario registrado exitosamente. Ahora puedes iniciar sesión con tus credenciales.',
                'usuario' => $usuarioData
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error en registro de usuario en PostgreSQL (nueva BD): ' . $e->getMessage(), [
                'correo' => $correo,
                'exception' => $e->getTraceAsString()
            ]);

            // Mensaje de respuesta general para evitar fugas de información
            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible completar el registro en este momento. Por favor intente más tarde.'
            ], 500);
        }
    }

    /**
     * Endpoint para comprobación rápida de disponibilidad del correo institucional
     * POST /api/auth/verificar-correo
     */
    public function verificarCorreo(Request $request)
    {
        $request->validate([
            'correo' => 'required|email|max:100'
        ], [
            'correo.required' => 'El correo institucional es obligatorio.',
            'correo.email' => 'El formato del correo institucional es inválido.'
        ]);

        $correo = strtolower(trim($request->correo));

        $existe = User::where('correo_institucional', $correo)->exists();
        if ($existe) {
            return response()->json([
                'status' => 'error',
                'disponible' => false,
                'mensaje' => 'Correo institucional ya registrado'
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'disponible' => true,
            'mensaje' => 'Correo institucional disponible'
        ], 200);
    }

    /**
     * Endpoint para solicitar código de recuperación de contraseña (6 dígitos vía Brevo SMTP)
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

        // 3. Enviar correo mediante Brevo SMTP
        try {
            Mail::to($correo)->send(new CodigoRecuperacionMail($registroCodigo->codigo, $user->nombres));
        } catch (\Throwable $e) {
            Log::error('Error al enviar correo de recuperación mediante Brevo SMTP: ' . $e->getMessage(), [
                'correo' => $correo,
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible enviar el código de verificación en este momento. Intente más tarde.'
            ], 500);
        }

        // 4. Enmascarar el correo según maqueta de interfaz (ej. us***********)
        $partes = explode('@', $correo);
        $prefijo = $partes[0];
        $longitudPrefijo = strlen($prefijo);
        $enmascarado = substr($prefijo, 0, 2) . str_repeat('*', max($longitudPrefijo - 2, 8));

        return response()->json([
            'status' => 'success',
            'mensaje' => 'Se ha enviado un código de verificación a tu correo electrónico.',
            'correo_enmascarado' => $enmascarado,
            'tiempo_expiracion_segundos' => 90
        ], 200);
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


