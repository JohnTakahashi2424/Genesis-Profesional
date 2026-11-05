<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pasante;
use App\Models\User;
use App\Models\CurriculumVitae;
use App\Models\Reporte;
use App\Models\InformeFinal;
use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;

class PasantiaController extends Controller
{
    /**
     * Obtener el estado del proceso de pasantía para un usuario/pasante
     * GET /api/pasante/estado
     */
    public function obtenerEstado(Request $request)
    {
        $userId = $request->query('user_id') ?? $request->user()?->id;
        $correo = $request->query('correo') ?? $request->query('user_email') ?? $request->query('email');

        $user = null;
        if ($userId) {
            $user = User::find($userId);
        }
        if (!$user && $correo) {
            $c = strtolower(trim($correo));
            $user = User::whereRaw('LOWER(correo_institucional) = ?', [$c])
                ->orWhereRaw('LOWER(email) = ?', [$c])
                ->first();
        }

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'mensaje' => 'Usuario no encontrado.'
            ], 404);
        }

        // 1. Evaluar si el usuario tiene currículum vitae registrado
        $tieneCv = false;
        try {
            if (class_exists(CurriculumVitae::class)) {
                $tieneCv = CurriculumVitae::where('usuario_id', $user->id)->exists();
            }
        } catch (\Throwable $e) { 
            Log::error("Error consultando estado de CV en PasantiaController", ['user_id' => $user->id, 'error' => $e->getMessage()]); 
        }

        // 2. Evaluar cantidad de reportes mensuales del usuario
        $cantReportes = 0;
        try {
            if (class_exists(Reporte::class)) {
                $cantReportes = Reporte::where('user_id', $user->id)->count();
            }
        } catch (\Throwable $e) { 
            Log::error("Error consultando reportes en PasantiaController", ['user_id' => $user->id, 'error' => $e->getMessage()]); 
        }

        // 3. Evaluar si ya tiene informe final registrado
        $tieneInformeFinal = false;
        try {
            if (class_exists(InformeFinal::class)) {
                $tieneInformeFinal = InformeFinal::where('user_id', $user->id)->exists();
            }
        } catch (\Throwable $e) { 
            Log::error("Error consultando informe final en PasantiaController", ['user_id' => $user->id, 'error' => $e->getMessage()]); 
        }

        // 4. Lógica de coherencia de las 4 fases de pasantía:
        // - Inicialmente todas en gris y pendientes.
        // - Al crear CV: Fase 1 se marca como 'completado' (verde).
        // - Al crear reporte por primera vez: Fase 2 pasa a 'completado' (verde),
        //   y Fase 3 pasa a 'en_revision' (amarillo con estilo de en revisión).
        // - Al subir el reporte final: Fase 3 pasa a 'completado' (verde),
        //   y Fase 4 pasa a 'en_revision' (amarillo con estilo de en revisión).
        $fase1 = $tieneCv ? 'completado' : 'pendiente';
        $fase2 = ($cantReportes > 0 || $tieneInformeFinal) ? 'completado' : 'pendiente';

        if ($tieneInformeFinal) {
            $fase3 = 'completado';
            $fase4 = 'en_revision';
            $faseActual = 'Fase 4';
        } elseif ($cantReportes > 0) {
            $fase3 = 'en_revision';
            $fase4 = 'pendiente';
            $faseActual = 'Fase 3';
        } elseif ($tieneCv) {
            $fase3 = 'pendiente';
            $fase4 = 'pendiente';
            $faseActual = 'Fase 2';
        } else {
            $fase3 = 'pendiente';
            $fase4 = 'pendiente';
            $faseActual = 'Fase 1';
        }

        // Buscar o crear registro de pasante
        $pasante = Pasante::with(['user', 'estudiante', 'supervisor'])->where('user_id', $user->id)->first();

        if (!$pasante) {
            $estudiante = $user->estudiante;
            $pasante = Pasante::create([
                'user_id' => $user->id,
                'estudiante_id' => $estudiante?->id,
                'area' => $estudiante?->carrera ?? 'Ingeniería en Sistemas',
                'tipo_pasantia' => 'interna',
                'estado' => 'en_proceso',
                'fase_actual' => $faseActual,
                'fase1_curriculum' => $fase1,
                'fase2_aceptado' => $fase2,
                'fase3_practicas' => $fase3,
                'fase4_informe_final' => $fase4,
                'horas_aprobadas' => 0,
            ]);
        } else {
            // Mantener sincronizadas las fases en la base de datos
            $pasante->update([
                'fase_actual' => $faseActual,
                'fase1_curriculum' => $fase1,
                'fase2_aceptado' => $fase2,
                'fase3_practicas' => $fase3,
                'fase4_informe_final' => $fase4,
            ]);
        }

        // 5. Emitir notificaciones automáticas al completarse cada fase si aún no se han notificado
        try {
            $this->verificarNotificacionesFases($user->id, $fase1, $fase2, $fase3, $fase4);
        } catch (\Throwable $eNotif) {
            \Illuminate\Support\Facades\Log::warning('Error al emitir notificaciones de fases: ' . $eNotif->getMessage());
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
                'fase_actual' => $faseActual,
                'fases' => [
                    'fase_1' => [
                        'nombre' => 'Fase 1: Currículum',
                        'estado' => $fase1
                    ],
                    'fase_2' => [
                        'nombre' => 'Fase 2: Aceptado',
                        'estado' => $fase2
                    ],
                    'fase_3' => [
                        'nombre' => 'Fase 3: Prácticas',
                        'estado' => $fase3
                    ],
                    'fase_4' => [
                        'nombre' => 'Fase 4: Informe final',
                        'estado' => $fase4
                    ],
                ],
                'horas_aprobadas' => (float) $pasante->horas_aprobadas,
            ]
        ], 200);
    }

    /**
     * Notificar al usuario cuando una de las 4 fases de la pasantía se ha completado
     */
    protected function verificarNotificacionesFases(int $userId, string $fase1, string $fase2, string $fase3, string $fase4): void
    {
        $fases = [
            'fase_1' => [
                'completado' => ($fase1 === 'completado'),
                'titulo' => 'Fase 1 completada',
                'mensaje' => '¡Felicidades! Has completado la Fase 1: Currículum. Tu perfil profesional está registrado y listo.',
                'enlace' => '/cv'
            ],
            'fase_2' => [
                'completado' => ($fase2 === 'completado'),
                'titulo' => 'Fase 2 completada',
                'mensaje' => '¡Felicidades! Has sido aceptado en tu institución de pasantía. La Fase 2: Aceptado ha sido completada.',
                'enlace' => '/reportes'
            ],
            'fase_3' => [
                'completado' => ($fase3 === 'completado'),
                'titulo' => 'Fase 3 completada',
                'mensaje' => 'Has culminado tu periodo de prácticas profesionales. La Fase 3: Prácticas ha sido completada.',
                'enlace' => '/reportes'
            ],
            'fase_4' => [
                'completado' => ($fase4 === 'completado'),
                'titulo' => 'Fase 4 completada',
                'mensaje' => 'Tu informe final ha sido evaluado y completado exitosamente. Has culminado tu proceso de pasantía.',
                'enlace' => '/reportes'
            ]
        ];

        foreach ($fases as $cfg) {
            if ($cfg['completado']) {
                $existe = Notificacion::where('user_id', $userId)
                    ->where('titulo', $cfg['titulo'])
                    ->exists();

                if (!$existe) {
                    Notificacion::crearNotificacion(
                        userId: $userId,
                        titulo: $cfg['titulo'],
                        mensaje: $cfg['mensaje'],
                        tipo: 'sistema',
                        origenTipo: 'sistema',
                        remitenteNombre: 'Notificaciones',
                        enlace: $cfg['enlace']
                    );
                }
            }
        }
    }
}
