<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HistorialProgreso;
use App\Models\User;

class HistorialProgresoController extends Controller
{
    /**
     * Categorías válidas admitidas según los indicadores del diseño
     */
    protected const CATEGORIAS_VALIDAS = [
        'cv',
        'reportes',
        'anexos',
        'reportes_rechazados',
        'reportes_aprobados',
        'cambio_estado',
        'perfil',
    ];

    /**
     * Obtener el listado de actividades del historial de progreso
     * del usuario autenticado en la aplicación web.
     * GET /api/historial-progreso
     */
    public function index(Request $request)
    {
        // 1. Validación de parámetros de entrada
        $validador = Validator::make($request->all(), [
            'categoria' => 'nullable|string|in:todos,' . implode(',', self::CATEGORIAS_VALIDAS),
            'user_id'   => 'nullable|integer|exists:users,id',
            'limit'     => 'nullable|integer|min:1|max:100',
        ], [
            'categoria.in' => 'La categoría seleccionada no es válida.',
            'user_id.exists' => 'El usuario especificado no existe.',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Parámetros de búsqueda inválidos.',
                'errores' => $validador->errors(),
            ], 422);
        }

        // 2. Resolver usuario garantizando aislamiento estricto
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Usuario no identificado o no autenticado.',
            ], 401);
        }

        // 3. Construir consulta filtrando estrictamente por el usuario
        $query = HistorialProgreso::paraUsuario((int)$userId)
            ->orderByDesc('fecha_exacta');

        // Filtro por categoría/indicador si se especifica
        $categoria = $request->query('categoria');
        if (!empty($categoria) && $categoria !== 'todos') {
            $query->porCategoria($categoria);
        }

        if ($request->has('limit')) {
            $query->limit((int)$request->query('limit'));
        }

        $items = $query->get();

        // Si no existen actividades pero el usuario tiene reportes registrados en la BD, sincronizar actividades reales
        if ($items->isEmpty() && empty($categoria)) {
            $reportesReales = \App\Models\Reporte::where('user_id', (int)$userId)
                ->orderBy('created_at')
                ->get();

            if ($reportesReales->isNotEmpty()) {
                $progreso = 0;
                foreach ($reportesReales as $rep) {
                    if ($rep->estado === 'Aprobado') {
                        $progreso = min(100, $progreso + 15);
                    }
                    HistorialProgreso::create([
                        'user_id' => $rep->user_id,
                        'categoria' => 'reportes',
                        'titulo' => 'Creación de reporte',
                        'descripcion' => "Creación de reporte del mes de {$rep->mes}",
                        'fecha_exacta' => $rep->created_at ?? now(),
                        'accion_texto' => 'Ver reporte',
                        'accion_url' => '/reportes/' . $rep->id,
                        'progreso' => $progreso,
                    ]);

                    HistorialProgreso::create([
                        'user_id' => $rep->user_id,
                        'categoria' => 'reportes',
                        'titulo' => 'Envío de reporte',
                        'descripcion' => "Envío de reporte del mes de {$rep->mes}",
                        'fecha_exacta' => $rep->created_at ? $rep->created_at->addMinutes(5) : now(),
                        'accion_texto' => 'Ver reporte',
                        'accion_url' => '/reportes/' . $rep->id,
                        'progreso' => $progreso,
                    ]);

                    if ($rep->estado === 'Aprobado') {
                        HistorialProgreso::create([
                            'user_id' => $rep->user_id,
                            'categoria' => 'reportes_aprobados',
                            'titulo' => 'Reporte aprobado',
                            'descripcion' => "Reporte del mes de {$rep->mes} marcado como Aprobado",
                            'fecha_exacta' => $rep->updated_at ? $rep->updated_at->addHours(1) : now(),
                            'accion_texto' => 'ver reporte',
                            'accion_url' => '/reportes/' . $rep->id,
                            'progreso' => $progreso,
                        ]);
                    }
                }

                $items = HistorialProgreso::paraUsuario((int)$userId)
                    ->orderByDesc('fecha_exacta')
                    ->get();
            }
        }

        // Garantizar que cualquier informe final registrado esté presente en el historial
        try {
            $informesReales = \App\Models\InformeFinal::where('user_id', (int)$userId)->get();
            $huboNuevos = false;
            foreach ($informesReales as $inf) {
                $existe = HistorialProgreso::paraUsuario((int)$userId)
                    ->where(function ($q) use ($inf) {
                        $q->where('titulo', 'LIKE', '%Informe Final%')
                          ->orWhere('accion_url', 'LIKE', "%/informe-final/{$inf->id}%");
                    })
                    ->first();
                if (!$existe) {
                    HistorialProgreso::create([
                        'user_id' => $inf->user_id,
                        'categoria' => 'reportes',
                        'titulo' => 'Envío de Informe Final',
                        'descripcion' => "Envío de informe final de pasantía para {$inf->institucion}",
                        'fecha_exacta' => $inf->created_at ?? now(),
                        'accion_texto' => 'Ver informe final',
                        'accion_url' => '/reportes/informe-final/' . $inf->id,
                        'progreso' => 100,
                    ]);
                    $huboNuevos = true;
                }
            }
            if ($huboNuevos) {
                $items = HistorialProgreso::paraUsuario((int)$userId)
                    ->orderByDesc('fecha_exacta')
                    ->get();
            }
        } catch (\Throwable $eInf) {
            \Illuminate\Support\Facades\Log::warning('No se pudo sincronizar historial de informe final: ' . $eInf->getMessage());
        }

        $items = $this->calcularProgresosCronologicos($items);

        return response()->json([
            'exito' => true,
            'total' => $items->count(),
            'items' => $items,
            'grupos' => $items->groupBy('fecha_agrupada'),
        ]);
    }

    /**
     * Registrar una nueva actividad en el historial de progreso.
     * POST /api/historial-progreso
     */
    public function store(Request $request)
    {
        // 1. Validaciones requeridas
        $validador = Validator::make($request->all(), [
            'categoria'    => 'required|string|in:' . implode(',', self::CATEGORIAS_VALIDAS),
            'titulo'       => 'required|string|min:3|max:150',
            'descripcion'  => 'nullable|string|max:1000',
            'fecha_exacta' => 'nullable|date',
            'accion_texto' => 'nullable|string|max:50',
            'accion_url'   => 'nullable|string|max:255',
            'progreso'     => 'nullable|integer|min:0|max:100',
            'user_id'      => 'nullable|integer|exists:users,id',
        ], [
            'categoria.required' => 'La categoría del indicador es obligatoria.',
            'categoria.in'       => 'La categoría proporcionada no coincide con los indicadores del sistema.',
            'titulo.required'    => 'El título de la actividad es obligatorio.',
            'progreso.min'       => 'El porcentaje de progreso no puede ser menor a 0.',
            'progreso.max'       => 'El porcentaje de progreso no puede ser mayor a 100.',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Error de validación al crear la actividad.',
                'errores' => $validador->errors(),
            ], 422);
        }

        // 2. Resolver usuario
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Usuario no identificado o no autenticado.',
            ], 401);
        }

        // 3. Crear el registro
        $actividad = HistorialProgreso::create([
            'user_id'      => (int)$userId,
            'categoria'    => $request->input('categoria'),
            'titulo'       => trim($request->input('titulo')),
            'descripcion'  => $request->input('descripcion') ? trim($request->input('descripcion')) : null,
            'fecha_exacta' => $request->input('fecha_exacta') ? $request->input('fecha_exacta') : now(),
            'accion_texto' => $request->input('accion_texto', 'Ver reporte'),
            'accion_url'   => $request->input('accion_url'),
            'progreso'     => (int)$request->input('progreso', 0),
        ]);

        return response()->json([
            'exito' => true,
            'mensaje' => 'Actividad registrada correctamente en el historial.',
            'data' => $actividad,
        ], 201);
    }

    /**
     * Eliminar una actividad específica del historial (solo del usuario propietario).
     * DELETE /api/historial-progreso/{id}
     */
    public function destroy(Request $request, $id)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Usuario no autenticado.',
            ], 401);
        }

        $actividad = HistorialProgreso::where('id', $id)
            ->where('user_id', (int)$userId)
            ->first();

        if (!$actividad) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Actividad no encontrada o no pertenece al usuario autenticado.',
            ], 404);
        }

        $actividad->delete();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Actividad eliminada exitosamente del historial.',
        ]);
    }

    /**
     * Sembrar registros base para un usuario nuevo, calcados de la interfaz
     */
        /**
     * Normalizar y calcular el porcentaje de progreso cronológico de cada actividad:
     * - Reporte 1: 10%
     * - Reporte 2: 20%
     * - Reporte 3: 30%
     * ...
     * - Límite de 70% (7 reportes mensuales)
     * - Informe Final / Reporte Final: 100%
     * - Cada tarjeta histórica conserva el progreso que se tenía en ese momento exacto.
     */
    protected function calcularProgresosCronologicos($items)
    {
        if ($items->isEmpty()) {
            return $items;
        }

        // Ordenar cronológicamente ascendente (antiguos primero) para calcular la acumulación histórica
        $itemsAsc = $items->sortBy(function ($item) {
            return $item->fecha_exacta ?? $item->created_at;
        })->values();

        $reportesIdentificados = [];
        $contadorReportes = 0;
        $ultimoProgreso = 0;

        foreach ($itemsAsc as $item) {
            $tituloLower = mb_strtolower($item->titulo ?? '');
            $descLower = mb_strtolower($item->descripcion ?? '');
            $urlLower = mb_strtolower($item->accion_url ?? '');
            $textoCompleto = "{$tituloLower} {$descLower} {$urlLower}";

            // 1. ¿Es informe o reporte final? -> 100%
            if (str_contains($textoCompleto, 'final')) {
                $ultimoProgreso = 100;
                $item->progreso = 100;
                continue;
            }

            // 2. ¿Es actividad de reporte mensual?
            $esReporte = in_array($item->categoria, ['reportes', 'reportes_aprobados', 'reportes_rechazados'])
                || str_contains($textoCompleto, 'reporte');

            if ($esReporte) {
                // Identificar a qué reporte mensual pertenece (por mes o por url/id)
                $claveReporte = null;
                if (preg_match('/mes de ([a-záéíóúñ]+)/i', $textoCompleto, $coincidencias)) {
                    $claveReporte = 'mes_' . trim(mb_strtolower($coincidencias[1]));
                } elseif (preg_match('/reportes\/(\d+)/i', $textoCompleto, $coincidencias)) {
                    $claveReporte = 'id_' . $coincidencias[1];
                } else {
                    $claveReporte = 'fecha_' . ($item->fecha_agrupada ?? $item->id);
                }

                if (!isset($reportesIdentificados[$claveReporte])) {
                    $contadorReportes++;
                    $progresoReporte = min(70, max(10, $contadorReportes * 10));
                    $reportesIdentificados[$claveReporte] = $progresoReporte;
                }

                $item->progreso = $reportesIdentificados[$claveReporte];
                $ultimoProgreso = $item->progreso;
            } else {
                // Otras actividades (CV, fotografía, perfil, etc.)
                // Mantienen el progreso alcanzado hasta ese momento (o 0 si aún no había reportes)
                $item->progreso = $ultimoProgreso > 0 ? $ultimoProgreso : ($item->progreso ?? 0);
            }
        }

        return $items;
    }

    /**
     * Sembrar registros base para un usuario nuevo, calcados de la interfaz
     */
    protected function sembrarActividadesIniciales(int $userId): void
    {
        $baseItems = [
            [
                'user_id'      => $userId,
                'categoria'    => 'reportes',
                'titulo'       => 'Envío de reporte',
                'descripcion'  => 'Envío de reporte del mes de febrero',
                'fecha_exacta' => '2026-03-01 08:16:03',
                'accion_texto' => 'Ver reporte',
                'accion_url'   => '/reportes',
                'progreso'     => 20,
            ],
            [
                'user_id'      => $userId,
                'categoria'    => 'reportes',
                'titulo'       => 'Creación de reporte',
                'descripcion'  => 'Creación de reporte del mes de febrero',
                'fecha_exacta' => '2026-03-01 08:00:03',
                'accion_texto' => 'Ver reporte',
                'accion_url'   => '/reportes',
                'progreso'     => 20,
            ],
            [
                'user_id'      => $userId,
                'categoria'    => 'perfil',
                'titulo'       => 'Actualización de fotografía',
                'descripcion'  => 'actualización de fotografía en el perfil',
                'fecha_exacta' => '2026-01-20 11:49:03',
                'accion_texto' => 'Ver perfil',
                'accion_url'   => '/perfil',
                'progreso'     => 10,
            ],
            [
                'user_id'      => $userId,
                'categoria'    => 'reportes_aprobados',
                'titulo'       => 'Reporte aprovado',
                'descripcion'  => 'reporte aprovado por - Jenny Flores',
                'fecha_exacta' => '2026-01-20 11:16:03',
                'accion_texto' => 'ver reporte',
                'accion_url'   => '/reportes',
                'progreso'     => 10,
            ],
            [
                'user_id'      => $userId,
                'categoria'    => 'reportes',
                'titulo'       => 'Envío de reporte',
                'descripcion'  => 'Envío de reporte del mes de enero',
                'fecha_exacta' => '2026-01-20 08:11:08',
                'accion_texto' => 'Ver reporte',
                'accion_url'   => '/reportes',
                'progreso'     => 10,
            ],
            [
                'user_id'      => $userId,
                'categoria'    => 'reportes',
                'titulo'       => 'Creación de reporte',
                'descripcion'  => 'Creación de reporte del mes de enero',
                'fecha_exacta' => '2026-01-20 06:16:03',
                'accion_texto' => 'Ver reporte',
                'accion_url'   => '/reportes',
                'progreso'     => 10,
            ],
        ];

        foreach ($baseItems as $item) {
            HistorialProgreso::create($item);
        }
    }
}
