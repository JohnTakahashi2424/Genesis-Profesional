<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Usuario;
use App\Models\PersonalAdministrativo;
use App\Models\Estudiante;
use App\Models\Pasante;

class AuthController extends Controller
{
    /**
     * Endpoint de Inicio de Sesión
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required'
        ]);

        $correo = strtolower(trim($request->correo));
        $contrasena = $request->contrasena;

        $usuario = null;
        $rol = null;

        // 1. Buscar en tabla usuarios (Tabla principal unificada)
        $usuarioDb = Usuario::where('correo_institucional', $correo)->first();

        if ($usuarioDb && Hash::check($contrasena, $usuarioDb->password)) {
            $usuario = [
                'id' => $usuarioDb->id,
                'nombres' => $usuarioDb->nombres,
                'apellidos' => $usuarioDb->apellidos,
                'correo' => $usuarioDb->correo_institucional
            ];
            $rol = $usuarioDb->rol; // Rol asignado en la BD

            // Regla: Si el rol es pasante, su correo debe empezar con "us"
            if ($rol === 'pasante' && !Str::startsWith($correo, 'us')) {
                return response()->json(['mensaje' => 'El correo de pasante debe iniciar con "us".'], 400);
            }
        }

        // 2. Buscar en tabla personal_administrativo (caso alterno / sincronización)
        if (!$usuario) {
            $personalDb = PersonalAdministrativo::where('correo_institucional', $correo)->first();

            if ($personalDb && Hash::check($contrasena, $personalDb->password)) {
                $usuario = [
                    'id' => $personalDb->id,
                    'nombres' => $personalDb->nombres,
                    'apellidos' => $personalDb->apellidos,
                    'correo' => $personalDb->correo_institucional
                ];
                
                $cargo = strtolower($personalDb->cargo);
                if (str_contains($cargo, 'decano')) {
                    $rol = 'vice_decano';
                } else {
                    $rol = 'supervisor';
                }
            }
        }

        if (!$usuario || !$rol) {
            return response()->json(['mensaje' => 'Correo o contraseña incorrectos.'], 401);
        }

        $usuario['rol'] = $rol;

        // Rutas de redirección sugeridas según rol
        $rutas = [
            'pasante' => '/dashboard/pasante',
            'supervisor' => '/dashboard/supervisor',
            'vice_decano' => '/dashboard/vicedecano'
        ];

        return response()->json([
            'mensaje' => 'Inicio de sesión exitoso.',
            'usuario' => $usuario,
            'redireccion' => $rutas[$rol] ?? '/login'
        ]);
    }

    /**
     * Endpoint de Registro Institucional
     * POST /api/auth/registro
     */
    public function registro(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required|string|min:4'
        ]);

        $correo = strtolower(trim($request->correo));

        // Si el correo empieza con "us" se trata de un Estudiante/Pasante
        if (Str::startsWith($correo, 'us')) {
            // 1. Validar que el estudiante exista en la base de datos institucional (usando el correo secundario o principal)
            $estudiante = Estudiante::where('correo_secundario', $correo)
                ->orWhere('correo_principal', $correo)
                ->first();
            
            if (!$estudiante) {
                return response()->json(['mensaje' => 'Este correo no pertenece a un estudiante activo matriculado.'], 400);
            }

            // 2. Verificar si ya se le creó una cuenta en la tabla 'usuarios'
            $existe = Usuario::where('correo_institucional', $correo)->exists();
            if ($existe) {
                return response()->json(['mensaje' => 'Esta cuenta ya está registrada. Por favor inicia sesión.'], 400);
            }

            // 3. Insertar usuario usando los datos oficiales de la base de datos institucional de estudiantes
            $usuario = Usuario::create([
                'nombres' => $estudiante->nombres,
                'apellidos' => $estudiante->apellidos,
                'correo_institucional' => $correo,
                'password' => Hash::make($request->contrasena),
                'estado' => 'activo',
                'rol' => 'pasante',
                'fecha_registro' => now()
            ]);

            // 4. Crear registro asociado en la tabla pasantes automáticamente
            Pasante::create([
                'usuario_id' => $usuario->id,
                'area' => $estudiante->carrera ?? 'Ingeniería en Sistemas',
                'tipo_pasantia' => 'Por definir',
                'estado' => 'en_proceso',
                'fase_actual' => 'Pendiente',
            ]);

            return response()->json([
                'mensaje' => 'Cuenta de pasante verificada y creada con éxito. Ahora puedes iniciar sesión.'
            ], 201);
        } else {
            // Se trata de Personal Administrativo (Supervisor o Vicedecano)
            // 1. Validar que el personal exista en la base de datos institucional (personal_administrativo)
            $personal = PersonalAdministrativo::where('correo_institucional', $correo)->first();
            if (!$personal) {
                return response()->json(['mensaje' => 'Este correo no pertenece al personal administrativo registrado.'], 400);
            }

            // 2. Verificar si ya tiene cuenta en la tabla 'usuarios'
            $existeUser = Usuario::where('correo_institucional', $correo)->exists();
            if ($existeUser) {
                return response()->json(['mensaje' => 'Esta cuenta administrativa ya está registrada. Por favor inicia sesión.'], 400);
            }

            // 3. Determinar rol
            $rol = 'supervisor';
            if (str_contains($correo, 'decano') || str_contains($correo, 'vicedecano') || strtolower($personal->cargo) === 'vice_decano') {
                $rol = 'vice_decano';
            }

            // 4. Crear en tabla usuarios (tabla de login unificada)
            $usuario = Usuario::create([
                'nombres' => $personal->nombres,
                'apellidos' => $personal->apellidos,
                'correo_institucional' => $correo,
                'password' => Hash::make($request->contrasena),
                'estado' => 'activo',
                'rol' => $rol,
                'fecha_registro' => now()
            ]);

            // 5. Actualizar la contraseña en la tabla personal_administrativo para mantener sincronía
            $personal->update([
                'password' => Hash::make($request->contrasena)
            ]);

            return response()->json([
                'mensaje' => 'Cuenta de personal administrativo creada con éxito. Ahora puedes iniciar sesión.'
            ], 201);
        }
    }
}
