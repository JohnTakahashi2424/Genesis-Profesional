<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Reporte;
use App\Models\ActividadReporte;
use App\Models\Pasante;
use App\Models\User;

class ReporteController extends Controller
{
    /**
     * Obtener datos iniciales para el formulario de nuevo reporte
     * (Nombre del pasante, área de pasantía y sugerencia de mes).
     * GET /api/reportes/formulario-inicial
     */
    public function formularioInicial(Request $request)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        $user = $userId ? User::with('estudiante')->find($userId) : null;
        $pasante = $userId ? Pasante::where('user_id', $userId)->first() : null;

        $nombreCompleto = $user ? trim("{$user->nombres} {$user->apellidos}") : '';
        $area = $pasante->area ?? ($user?->estudiante?->carrera ?? 'Ingeniería en Sistemas y Redes Informáticas');

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        $mesActual = $meses[(int)date('n')];

        return response()->json([
            'exito' => true,
            'data' => [
                'user_id' => $user?->id,
                'pasante_id' => $pasante?->id,
                'nombre_pasante' => $nombreCompleto,
                'area_pasantia' => $area,
                'mes_sugerido' => $mesActual,
            ]
        ]);
    }

    /**
     * Obtener la lista de reportes con filtros por búsqueda y estado.
     * GET /api/reportes
     */
    public function index(Request $request)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');
        $pasanteId = $request->query('pasante_id');

        $query = Reporte::with('actividades')
            ->withCount('actividades')
            ->orderByDesc('created_at');

        // Filtrar directamente por el usuario (user_id de la tabla users) o pasante
        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($pasanteId) {
            $query->where('pasante_id', $pasanteId);
        }

        // Filtro de búsqueda por mes, área, nombre del pasante u objetivo
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('mes', 'ilike', "%{$search}%")
                  ->orWhere('area_pasantia', 'ilike', "%{$search}%")
                  ->orWhere('nombre_pasante', 'ilike', "%{$search}%")
                  ->orWhere('objetivo', 'ilike', "%{$search}%");
            });
        }

        // Filtro por estado ('Enviado', 'En revisión', 'Aprobado', 'Observado')
        if ($request->filled('estado') && $request->query('estado') !== 'Todos') {
            $query->where('estado', $request->query('estado'));
        }

        $reportes = $query->get();

        return response()->json([
            'exito' => true,
            'data' => $reportes
        ]);
    }

    /**
     * Obtener un reporte específico por su ID.
     * GET /api/reportes/{id}
     */
    public function show($id)
    {
        $reporte = Reporte::with('actividades')
            ->withCount('actividades')
            ->find($id);

        if (!$reporte) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Reporte no encontrado'
            ], 404);
        }

        return response()->json([
            'exito' => true,
            'data' => $reporte
        ]);
    }

    /**
     * Crear un nuevo reporte mensual con sus actividades e imágenes.
     * POST /api/reportes
     */
    public function store(Request $request)
    {
        // Normalizar estructura de actividades recibidas (por si vienen con nombres alternativos)
        $this->normalizarDatosActividades($request);

        $validated = $request->validate([
            'pasante_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'area_pasantia' => 'required|string|max:150',
            'nombre_pasante' => 'required|string|max:150',
            'mes' => 'required|string|max:50',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'objetivo' => 'required|string',
            'estado' => 'nullable|string|in:Enviado,En revisión,Aprobado,Observado',
            'evidencias' => 'nullable|array|max:10',
            'actividades' => 'required|array|min:1',
            'actividades.*.numero' => 'nullable|integer',
            'actividades.*.actividad' => 'required|string',
            'actividades.*.fecha' => 'required|date',
            'actividades.*.resultado_aprendizaje' => 'required|string',
        ], [
            'area_pasantia.required' => 'El área de pasantía es requerida.',
            'nombre_pasante.required' => 'El nombre del pasante es requerido.',
            'mes.required' => 'El mes del reporte es requerido.',
            'fecha_inicio.required' => 'La fecha de inicio es requerida.',
            'fecha_fin.required' => 'La fecha de finalización es requerida.',
            'fecha_fin.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio.',
            'objetivo.required' => 'El objetivo del reporte es requerido.',
            'actividades.required' => 'Debe registrar al menos una actividad realizada.',
            'actividades.min' => 'Debe registrar al menos una actividad realizada.',
            'actividades.*.actividad.required' => 'La descripción de la actividad es requerida.',
            'actividades.*.fecha.required' => 'La fecha de la actividad es requerida.',
            'actividades.*.resultado_aprendizaje.required' => 'El resultado de aprendizaje es requerido.',
        ]);

        // Determinar user_id y pasante_id
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $validated['user_id'] ?? ($jwtUser->id ?? $request->header('X-User-Id'));

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'No se encontró la identidad del usuario para vincular el reporte.'
            ], 422);
        }

        $pasanteId = $validated['pasante_id'] ?? null;
        if (!$pasanteId) {
            $pasante = Pasante::where('user_id', $userId)->first();
            if ($pasante) {
                $pasanteId = $pasante->id;
            }
        }

        // Procesar imágenes de evidencias (Base64 o archivos multipart)
        $urlsEvidencias = $this->procesarEvidencias($request);

        return DB::transaction(function () use ($validated, $userId, $pasanteId, $urlsEvidencias) {
            // 1. Crear el reporte principal vinculado directamente al usuario
            $reporte = Reporte::create([
                'user_id' => $userId,
                'pasante_id' => $pasanteId,
                'area_pasantia' => $validated['area_pasantia'],
                'nombre_pasante' => $validated['nombre_pasante'],
                'mes' => $validated['mes'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'objetivo' => $validated['objetivo'],
                'estado' => $validated['estado'] ?? 'En revisión',
                'evidencias' => $urlsEvidencias,
            ]);

            // 2. Crear las actividades realizadas asociadas
            foreach ($validated['actividades'] as $index => $act) {
                $reporte->actividades()->create([
                    'numero' => $act['numero'] ?? ($index + 1),
                    'actividad' => $act['actividad'],
                    'fecha' => $act['fecha'],
                    'resultado_aprendizaje' => $act['resultado_aprendizaje'],
                ]);
            }

            // 3. Registrar actividad en el Historial de Progreso del estudiante
            try {
                $esEnvio = ($validated['estado'] ?? '') === 'Enviado';
                $titulo = $esEnvio ? 'Envío de reporte' : 'Creación de reporte';
                \App\Models\HistorialProgreso::registrar(
                    userId: (int)$userId,
                    categoria: 'reportes',
                    titulo: $titulo,
                    descripcion: "{$titulo} del mes de {$validated['mes']}",
                    accionTexto: 'Ver reporte',
                    accionUrl: '/reportes/' . $reporte->id,
                    progreso: $esEnvio ? 25 : 20
                );

                // 4. Crear Notificación de acción propia para el usuario
                \App\Models\Notificacion::crearNotificacion(
                    userId: (int)$userId,
                    titulo: $esEnvio ? 'Reporte enviado' : 'Reporte creado',
                    mensaje: "El reporte del mes de {$validated['mes']} ha sido " . ($esEnvio ? 'enviado exitosamente.' : 'creado correctamente.'),
                    tipo: 'reporte',
                    origenTipo: 'sistema',
                    remitenteNombre: 'Notificaciones',
                    enlace: '/reportes/' . $reporte->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo registrar en historial o notificaciones: ' . $e->getMessage());
            }

            return response()->json([
                'exito' => true,
                'mensaje' => 'Reporte mensual creado correctamente.',
                'data' => $reporte->load('actividades')
            ], 201);
        });
    }

    /**
     * Actualizar un reporte mensual existente y sus actividades.
     * PUT /api/reportes/{id}
     */
    public function update(Request $request, $id)
    {
        $reporte = Reporte::find($id);

        if (!$reporte) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Reporte no encontrado'
            ], 404);
        }

        // Normalizar estructura de actividades recibidas
        $this->normalizarDatosActividades($request);

        $validated = $request->validate([
            'area_pasantia' => 'required|string|max:150',
            'nombre_pasante' => 'required|string|max:150',
            'mes' => 'required|string|max:50',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'objetivo' => 'required|string',
            'estado' => 'nullable|string|in:Enviado,En revisión,Aprobado,Observado',
            'evidencias' => 'nullable|array|max:10',
            'actividades' => 'nullable|array',
            'actividades.*.numero' => 'nullable|integer',
            'actividades.*.actividad' => 'required|string',
            'actividades.*.fecha' => 'required|date',
            'actividades.*.resultado_aprendizaje' => 'required|string',
        ]);

        // Procesar evidencias nuevas o mantener existentes
        $urlsEvidencias = $this->procesarEvidencias($request, $reporte->evidencias ?? []);

        return DB::transaction(function () use ($reporte, $validated, $urlsEvidencias) {
            // Actualizar datos del reporte
            $reporte->update([
                'area_pasantia' => $validated['area_pasantia'],
                'nombre_pasante' => $validated['nombre_pasante'],
                'mes' => $validated['mes'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'objetivo' => $validated['objetivo'],
                'estado' => $validated['estado'] ?? $reporte->estado,
                'evidencias' => $urlsEvidencias,
            ]);

            // Reemplazar actividades si se enviaron
            if (isset($validated['actividades'])) {
                $reporte->actividades()->delete();
                foreach ($validated['actividades'] as $index => $act) {
                    $reporte->actividades()->create([
                        'numero' => $act['numero'] ?? ($index + 1),
                        'actividad' => $act['actividad'],
                        'fecha' => $act['fecha'],
                        'resultado_aprendizaje' => $act['resultado_aprendizaje'],
                    ]);
                }
            }

            return response()->json([
                'exito' => true,
                'mensaje' => 'Reporte mensual actualizado correctamente.',
                'data' => $reporte->load('actividades')
            ]);
        });
    }

    /**
     * Eliminar un reporte mensual, sus actividades y evidencias.
     * DELETE /api/reportes/{id}
     */
    public function destroy($id)
    {
        $reporte = Reporte::find($id);

        if (!$reporte) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Reporte no encontrado'
            ], 404);
        }

        // Eliminar archivos físicos de evidencias si residen en storage local
        if (!empty($reporte->evidencias) && is_array($reporte->evidencias)) {
            foreach ($reporte->evidencias as $url) {
                if (is_string($url) && str_contains($url, '/reportes/evidencias/')) {
                    $path = str_replace('/api/storage/', '', $url);
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $reporte->delete();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Reporte eliminado correctamente.'
        ]);
    }

    /**
     * Cambiar el estado del reporte (ej. para revisión o aprobación de supervisores).
     * PATCH /api/reportes/{id}/estado
     */
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string|in:Enviado,En revisión,Aprobado,Observado',
        ]);

        $reporte = Reporte::find($id);

        if (!$reporte) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Reporte no encontrado'
            ], 404);
        }

        $reporte->update(['estado' => $request->estado]);

        // Registrar cambio de estado o aprobación en el Historial de Progreso
        try {
            $estado = $request->estado;
            $categoria = match ($estado) {
                'Aprobado' => 'reportes_aprobados',
                'Observado' => 'reportes_rechazados',
                default => 'reportes',
            };
            $titulo = match ($estado) {
                'Aprobado' => 'Reporte aprobado',
                'Observado' => 'Observaciones en reporte',
                default => 'Cambio de estado de reporte',
            };
            \App\Models\HistorialProgreso::registrar(
                userId: (int)$reporte->user_id,
                categoria: $categoria,
                titulo: $titulo,
                descripcion: "Reporte del mes de {$reporte->mes} marcado como {$estado}",
                accionTexto: 'Ver reporte',
                accionUrl: '/reportes/' . $reporte->id,
                progreso: $estado === 'Aprobado' ? 50 : 30
            );

            // Crear Notificación recibida para el pasante (ej. Observaciones o Aprobación)
            $esObservado = $estado === 'Observado';
            $remitenteNombre = $esObservado ? 'Jenny Flores' : 'Notificaciones';
            $origenTipo = $esObservado ? 'usuario' : 'sistema';
            $tituloNotif = $esObservado ? 'Observaciones' : ($estado === 'Aprobado' ? 'Reporte aprobado' : 'Proceso de pasantía');
            $mensajeNotif = $esObservado
                ? "Agregar 2 o 3 anexos más en el reporte del mes de {$reporte->mes}."
                : "El proceso de pasantía ha cambiado: tu reporte de {$reporte->mes} fue {$estado}.";

            \App\Models\Notificacion::crearNotificacion(
                userId: (int)$reporte->user_id,
                titulo: $tituloNotif,
                mensaje: $mensajeNotif,
                tipo: $esObservado ? 'observacion' : 'reporte',
                origenTipo: $origenTipo,
                remitenteNombre: $remitenteNombre,
                enlace: '/reportes/' . $reporte->id
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo registrar historial o notificacion al cambiar estado: ' . $e->getMessage());
        }

        return response()->json([
            'exito' => true,
            'mensaje' => 'Estado del reporte actualizado correctamente.',
            'data' => $reporte
        ]);
    }

    /**
     * Normalizar campos alternativos de actividades para asegurar compatibilidad.
     */
    private function normalizarDatosActividades(Request $request): void
    {
        if ($request->has('actividades') && is_array($request->actividades)) {
            $actividades = $request->actividades;
            foreach ($actividades as $i => $act) {
                if (is_array($act)) {
                    // Mapear 'actividad_realizada' -> 'actividad' si viene con nombre previo
                    if (!isset($act['actividad']) && isset($act['actividad_realizada'])) {
                        $actividades[$i]['actividad'] = $act['actividad_realizada'];
                    }
                    // Mapear 'fecha_actividad' -> 'fecha' si viene con nombre previo
                    if (!isset($act['fecha']) && isset($act['fecha_actividad'])) {
                        $actividades[$i]['fecha'] = $act['fecha_actividad'];
                    }
                    // Mapear 'logros_obtenidos' -> 'resultado_aprendizaje' si viene con nombre previo
                    if (!isset($act['resultado_aprendizaje']) && isset($act['logros_obtenidos'])) {
                        $actividades[$i]['resultado_aprendizaje'] = $act['logros_obtenidos'];
                    }
                    // Asignar número si falta
                    if (!isset($act['numero'])) {
                        $actividades[$i]['numero'] = $i + 1;
                    }
                }
            }
            $request->merge(['actividades' => $actividades]);
        }
    }

    /**
     * Procesar imágenes de evidencias (soporta base64, multipart uploads o URLs).
     */
    private function procesarEvidencias(Request $request, array $existentes = []): array
    {
        $urls = $existentes;

        if ($request->has('evidencias') && is_array($request->evidencias)) {
            $urls = [];
            foreach ($request->evidencias as $index => $item) {
                if (is_string($item) && preg_match('#^data:image/(\w+);base64,#i', $item, $matches)) {
                    $ext = strtolower($matches[1]);
                    $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $item));
                    $filename = 'evidencia_' . time() . "_{$index}." . $ext;
                    $path = "reportes/evidencias/{$filename}";
                    Storage::disk('public')->put($path, $decoded);
                    $urls[] = "/api/storage/{$path}";
                } elseif (is_string($item)) {
                    $urls[] = $item;
                }
            }
        }

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                $path = $file->store('reportes/evidencias', 'public');
                $urls[] = "/api/storage/{$path}";
            }
        }

        return $urls;
    }
}
