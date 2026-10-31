<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Notificacion;
use App\Models\User;

class NotificacionController extends Controller
{
    /**
     * Obtener el listado de notificaciones del usuario autenticado.
     * Soporta los filtros de la maqueta:
     * - estado: 'todos' | 'no_leidos' | 'leidos'
     * - orden: 'recientes' (desc) | 'antiguos' (asc)
     * - tipo: 'reporte' | 'observacion' | 'proceso_pasantia' | 'general'
     *
     * GET /api/notificaciones
     */
    public function index(Request $request)
    {
        // 1. Validar parámetros de consulta
        $validador = Validator::make($request->all(), [
            'estado'  => 'nullable|string|in:todos,Todos,no_leidos,no-leidos,leidos',
            'orden'   => 'nullable|string|in:recientes,antiguos',
            'tipo'    => 'nullable|string|in:todos,Todos,reporte,observacion,proceso_pasantia,general',
            'user_id' => 'nullable|integer|exists:users,id',
            'limit'   => 'nullable|integer|min:1|max:100',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Parámetros de filtrado no válidos.',
                'errores' => $validador->errors(),
            ], 422);
        }

        // 2. Resolver identidad del usuario autenticado
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        if (!$userId) {
            $primerUsuario = User::first();
            $userId = $primerUsuario?->id;
        }

        if (!$userId) {
            return response()->json([
                'exito' => true,
                'total' => 0,
                'no_leidas_count' => 0,
                'items' => [],
            ]);
        }

        // 3. Consulta base aislada por usuario
        $query = Notificacion::paraUsuario((int)$userId);

        // Filtro 1: Lectura ('no_leidos' o 'leidos')
        $estado = strtolower($request->query('estado', 'todos'));
        if ($estado === 'no_leidos' || $estado === 'no-leidos') {
            $query->noLeidas();
        } elseif ($estado === 'leidos') {
            $query->leidas();
        }

        // Filtro 2: Tipo de notificación
        $tipo = $request->query('tipo');
        if (!empty($tipo) && strtolower($tipo) !== 'todos') {
            $query->porTipo($tipo);
        }

        // Filtro 3: Orden cronológico (Recientes primero por defecto)
        $orden = strtolower($request->query('orden', 'recientes'));
        if ($orden === 'antiguos') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        if ($request->has('limit')) {
            $query->limit((int)$request->query('limit'));
        }

        $items = $query->get();

        // Contador de notificaciones no leídas para badge o campana
        $noLeidasCount = Notificacion::paraUsuario((int)$userId)->noLeidas()->count();

        return response()->json([
            'exito' => true,
            'total' => $items->count(),
            'no_leidas_count' => $noLeidasCount,
            'items' => $items,
        ]);
    }

    /**
     * Marcar una notificación específica como leída (al dar clic en el ojo 👁).
     * PATCH /api/notificaciones/{id}/leer
     */
    public function marcarLeida(Request $request, $id)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id') ?? $request->input('user_id');

        if (!$userId) {
            $userId = User::first()?->id;
        }

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Usuario no autenticado.',
            ], 401);
        }

        $notificacion = Notificacion::where('id', $id)
            ->where('user_id', (int)$userId)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Notificación no encontrada o no pertenece al usuario autenticado.',
            ], 404);
        }

        $notificacion->marcarComoLeida();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Notificación marcada como leída.',
            'data' => $notificacion,
        ]);
    }

    /**
     * Marcar todas las notificaciones pendientes como leídas.
     * POST /api/notificaciones/marcar-todas-leidas
     */
    public function marcarTodasLeidas(Request $request)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id') ?? $request->input('user_id');

        if (!$userId) {
            $userId = User::first()?->id;
        }

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Usuario no autenticado.',
            ], 401);
        }

        Notificacion::paraUsuario((int)$userId)
            ->noLeidas()
            ->update(['leido_at' => now()]);

        return response()->json([
            'exito' => true,
            'mensaje' => 'Todas las notificaciones fueron marcadas como leídas.',
        ]);
    }

    /**
     * Eliminar una notificación (al pulsar el basurero rojo 🗑).
     * DELETE /api/notificaciones/{id}
     */
    public function destroy(Request $request, $id)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        if (!$userId) {
            $userId = User::first()?->id;
        }

        if (!$userId) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Usuario no autenticado.',
            ], 401);
        }

        $notificacion = Notificacion::where('id', $id)
            ->where('user_id', (int)$userId)
            ->first();

        if (!$notificacion) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Notificación no encontrada o no pertenece al usuario autenticado.',
            ], 404);
        }

        $notificacion->delete();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Notificación eliminada correctamente.',
        ]);
    }

    /**
     * Crear manualmente una notificación mediante la API (con validación completa).
     * POST /api/notificaciones
     */
    public function store(Request $request)
    {
        $validador = Validator::make($request->all(), [
            'user_id'          => 'required|integer|exists:users,id',
            'titulo'           => 'required|string|min:3|max:150',
            'mensaje'          => 'required|string|min:3|max:1000',
            'origen_tipo'      => 'nullable|string|in:sistema,usuario',
            'remitente_nombre' => 'nullable|string|max:100',
            'remitente_id'     => 'nullable|integer|exists:users,id',
            'tipo'             => 'nullable|string|in:reporte,observacion,proceso_pasantia,general',
            'enlace'           => 'nullable|string|max:255',
        ], [
            'user_id.required' => 'El receptor de la notificación es obligatorio.',
            'user_id.exists'   => 'El usuario receptor no existe.',
            'titulo.required'  => 'El título de la notificación es obligatorio.',
            'mensaje.required' => 'El mensaje de la notificación es obligatorio.',
        ]);

        if ($validador->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Error de validación al crear la notificación.',
                'errores' => $validador->errors(),
            ], 422);
        }

        $notificacion = Notificacion::create([
            'user_id'          => $request->input('user_id'),
            'titulo'           => trim($request->input('titulo')),
            'mensaje'          => trim($request->input('mensaje')),
            'origen_tipo'      => $request->input('origen_tipo', 'sistema'),
            'remitente_nombre' => $request->input('remitente_nombre', 'Notificaciones'),
            'remitente_id'     => $request->input('remitente_id'),
            'tipo'             => $request->input('tipo', 'general'),
            'enlace'           => $request->input('enlace'),
            'leido_at'         => null,
        ]);

        return response()->json([
            'exito' => true,
            'mensaje' => 'Notificación creada exitosamente.',
            'data' => $notificacion,
        ], 201);
    }
}

