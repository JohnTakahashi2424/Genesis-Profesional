<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Reporte;
use App\Models\ActividadReporte;
use App\Models\Pasante;

class ReporteController extends Controller
{
    /**
     * Obtener la lista de reportes con filtros por búsqueda y estado.
     * GET /api/reportes
     */
    public function index(Request $request)
    {
        $userId = $request->header('X-User-Id', $request->query('user_id'));
        $pasanteId = $request->query('pasante_id');

        $query = Reporte::with('actividades')->orderByDesc('created_at');

        // Filtrar por pasante si se especifica pasante_id o user_id
        if ($pasanteId) {
            $query->where('pasante_id', $pasanteId);
        } elseif ($userId) {
            $pasante = Pasante::where('user_id', $userId)->first();
            if ($pasante) {
                $query->where('pasante_id', $pasante->id);
            }
        }

        // Filtro de búsqueda por nombre del reporte
        if ($request->has('search') && !empty($request->query('search'))) {
            $search = $request->query('search');
            $query->where('nombre_reporte', 'like', "%{$search}%");
        }

        // Filtro por estado ('Enviado', 'En revisión', 'Aprobado')
        if ($request->has('estado') && !empty($request->query('estado')) && $request->query('estado') !== 'Todos') {
            $query->where('estado', $request->query('estado'));
        }

        $reportes = $query->get();

        // Mapear respuesta con formato de período legible
        $reportes->transform(function ($reporte) {
            $reporte->periodo = $reporte->fecha_inicio && $reporte->fecha_fin
                ? $reporte->fecha_inicio->format('d M') . ' – ' . $reporte->fecha_fin->format('d M Y')
                : null;
            return $reporte;
        });

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
        $reporte = Reporte::with('actividades')->find($id);

        if (!$reporte) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Reporte no encontrado'
            ], 404);
        }

        $reporte->periodo = $reporte->fecha_inicio && $reporte->fecha_fin
            ? $reporte->fecha_inicio->format('d M') . ' – ' . $reporte->fecha_fin->format('d M Y')
            : null;

        return response()->json([
            'exito' => true,
            'data' => $reporte
        ]);
    }

    /**
     * Crear un nuevo reporte junto con sus actividades e imágenes de evidencia.
     * POST /api/reportes
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pasante_id' => 'nullable|integer',
            'user_id' => 'nullable|integer',
            'nombre_reporte' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'horas_registradas' => 'required|numeric|min:0',
            'estado' => 'nullable|string|in:Enviado,En revisión,Aprobado',
            'evidencias' => 'nullable|array|max:4',
            'actividades' => 'nullable|array',
            'actividades.*.fecha_actividad' => 'required|date',
            'actividades.*.objetivo' => 'required|string',
            'actividades.*.actividad_realizada' => 'required|string',
            'actividades.*.logros_obtenidos' => 'required|string',
        ], [
            'evidencias.max' => 'Solo se permiten como máximo 4 imágenes por reporte.'
        ]);

        // Determinar pasante_id
        $pasanteId = $validated['pasante_id'] ?? null;
        if (!$pasanteId) {
            $userId = $validated['user_id'] ?? $request->header('X-User-Id');
            if ($userId) {
                $pasante = Pasante::where('user_id', $userId)->first();
                $pasanteId = $pasante ? $pasante->id : null;
            }
        }

        if (!$pasanteId) {
            // Si aún no se encuentra pasante, tomar el primero o retornar error
            $pasanteId = Pasante::value('id');
            if (!$pasanteId) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'No se encontró el registro del pasante.'
                ], 422);
            }
        }

        // Procesar imágenes de evidencia si se envían en Base64 o archivos
        $urlsEvidencias = [];
        if ($request->has('evidencias') && is_array($request->evidencias)) {
            foreach ($request->evidencias as $index => $item) {
                if (is_string($item) && preg_match('#^data:image/(\w+);base64,#i', $item, $matches)) {
                    $ext = strtolower($matches[1]);
                    $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $item));
                    $filename = 'evidencia_' . time() . "_{$index}." . $ext;
                    $path = "reportes/evidencias/{$filename}";
                    Storage::disk('public')->put($path, $decoded);
                    $urlsEvidencias[] = "/api/storage/{$path}";
                } elseif (is_string($item)) {
                    $urlsEvidencias[] = $item;
                }
            }
        }

        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $file) {
                $path = $file->store('reportes/evidencias', 'public');
                $urlsEvidencias[] = "/api/storage/{$path}";
            }
        }

        return DB::transaction(function () use ($validated, $pasanteId, $urlsEvidencias) {
            // 1. Crear registro principal de Reporte
            $reporte = Reporte::create([
                'pasante_id' => $pasanteId,
                'nombre_reporte' => $validated['nombre_reporte'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'horas_registradas' => $validated['horas_registradas'],
                'estado' => $validated['estado'] ?? 'En revisión',
                'evidencias' => $urlsEvidencias,
            ]);

            // 2. Insertar actividades registradas
            if (!empty($validated['actividades'])) {
                foreach ($validated['actividades'] as $act) {
                    $reporte->actividades()->create([
                        'fecha_actividad' => $act['fecha_actividad'],
                        'objetivo' => $act['objetivo'],
                        'actividad_realizada' => $act['actividad_realizada'],
                        'logros_obtenidos' => $act['logros_obtenidos'],
                    ]);
                }
            }

            return response()->json([
                'exito' => true,
                'mensaje' => 'Reporte creado correctamente.',
                'data' => $reporte->load('actividades')
            ], 201);
        });
    }

    /**
     * Actualizar un reporte existente y sus actividades.
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

        $validated = $request->validate([
            'nombre_reporte' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'horas_registradas' => 'required|numeric|min:0',
            'estado' => 'nullable|string|in:Enviado,En revisión,Aprobado',
            'evidencias' => 'nullable|array|max:4',
            'actividades' => 'nullable|array',
            'actividades.*.fecha_actividad' => 'required|date',
            'actividades.*.objetivo' => 'required|string',
            'actividades.*.actividad_realizada' => 'required|string',
            'actividades.*.logros_obtenidos' => 'required|string',
        ], [
            'evidencias.max' => 'Solo se permiten como máximo 4 imágenes por reporte.'
        ]);

        // Procesar imágenes
        $urlsEvidencias = [];
        if ($request->has('evidencias') && is_array($request->evidencias)) {
            foreach ($request->evidencias as $index => $item) {
                if (is_string($item) && preg_match('#^data:image/(\w+);base64,#i', $item, $matches)) {
                    $ext = strtolower($matches[1]);
                    $decoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $item));
                    $filename = 'evidencia_' . time() . "_{$index}." . $ext;
                    $path = "reportes/evidencias/{$filename}";
                    Storage::disk('public')->put($path, $decoded);
                    $urlsEvidencias[] = "/api/storage/{$path}";
                } elseif (is_string($item)) {
                    $urlsEvidencias[] = $item;
                }
            }
        } else {
            $urlsEvidencias = $reporte->evidencias ?? [];
        }

        return DB::transaction(function () use ($reporte, $validated, $urlsEvidencias) {
            // Update datos generales del reporte
            $reporte->update([
                'nombre_reporte' => $validated['nombre_reporte'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_fin' => $validated['fecha_fin'],
                'horas_registradas' => $validated['horas_registradas'],
                'estado' => $validated['estado'] ?? $reporte->estado,
                'evidencias' => $urlsEvidencias,
            ]);

            // Reemplazar actividades
            if (isset($validated['actividades'])) {
                $reporte->actividades()->delete();
                foreach ($validated['actividades'] as $act) {
                    $reporte->actividades()->create([
                        'fecha_actividad' => $act['fecha_actividad'],
                        'objetivo' => $act['objetivo'],
                        'actividad_realizada' => $act['actividad_realizada'],
                        'logros_obtenidos' => $act['logros_obtenidos'],
                    ]);
                }
            }

            return response()->json([
                'exito' => true,
                'mensaje' => 'Reporte actualizado correctamente.',
                'data' => $reporte->load('actividades')
            ]);
        });
    }

    /**
     * Eliminar un reporte y sus actividades asociadas.
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

        // Eliminar imágenes de evidencias almacenadas si corresponden
        if (!empty($reporte->evidencias)) {
            foreach ($reporte->evidencias as $url) {
                if (str_contains($url, '/storage/reportes/evidencias/')) {
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
     * Cambiar el estado de un reporte (ej. para revisión/aprobación por supervisor).
     * PATCH /api/reportes/{id}/estado
     */
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|string|in:Enviado,En revisión,Aprobado',
        ]);

        $reporte = Reporte::find($id);

        if (!$reporte) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Reporte no encontrado'
            ], 404);
        }

        $reporte->update(['estado' => $request->estado]);

        return response()->json([
            'exito' => true,
            'mensaje' => 'Estado del reporte actualizado.',
            'data' => $reporte
        ]);
    }
}
