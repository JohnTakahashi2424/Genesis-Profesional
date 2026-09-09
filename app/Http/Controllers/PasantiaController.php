<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasante;
use App\Models\User;

class PasantiaController extends Controller
{
    /**
     * Obtener el estado del proceso de pasantía para un usuario/pasante
     * GET /api/pasante/estado
     */
    public function obtenerEstado(Request $request)
    {
        $userId = $request->query('user_id') ?? $request->user()?->id;
        $correo = $request->query('correo');

        $user = null;
        if ($userId) {
            $user = User::find($userId);
        } elseif ($correo) {
            $user = User::where('correo_institucional', strtolower(trim($correo)))->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Usuario no encontrado.'
            ], 404);
        }

        // Buscar o crear estado inicial de pasantía para el estudiante/pasante
        $pasante = Pasante::with(['user', 'estudiante', 'supervisor'])->where('user_id', $user->id)->first();

        if (!$pasante) {
            // Intentar vincular con su registro de estudiante en la base de datos
            $estudiante = $user->estudiante;

            $pasante = Pasante::create([
                'user_id' => $user->id,
                'estudiante_id' => $estudiante?->id,
                'area' => $estudiante?->carrera ?? 'Ingeniería en Sistemas',
                'tipo_pasantia' => 'interna',
                'estado' => 'en_proceso',
                'fase_actual' => 'Fase 1',
                'fase1_curriculum' => 'completado',
                'fase2_aceptado' => 'completado',
                'fase3_practicas' => 'pendiente',
                'fase4_informe_final' => 'pendiente',
                'horas_aprobadas' => 0,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'usuario' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'correo' => $user->correo_institucional,
                'rol' => $user->rol,
            ],
            'pasantia' => [
                'id' => $pasante->id,
                'user_id' => $pasante->user_id,
                'estudiante_id' => $pasante->estudiante_id,
                'supervisor_id' => $pasante->supervisor_id,
                'area' => $pasante->area,
                'tipo_pasantia' => $pasante->tipo_pasantia,
                'estado_general' => $pasante->estado,
                'fase_actual' => $pasante->fase_actual,
                'fases' => [
                    'fase_1' => [
                        'nombre' => 'Fase 1: Currículum',
                        'estado' => $pasante->fase1_curriculum
                    ],
                    'fase_2' => [
                        'nombre' => 'Fase 2: Aceptado',
                        'estado' => $pasante->fase2_aceptado
                    ],
                    'fase_3' => [
                        'nombre' => 'Fase 3: Prácticas',
                        'estado' => $pasante->fase3_practicas
                    ],
                    'fase_4' => [
                        'nombre' => 'Fase 4: Informe final',
                        'estado' => $pasante->fase4_informe_final
                    ],
                ],
                'horas_aprobadas' => (float) $pasante->horas_aprobadas,
            ]
        ], 200);
    }
}
