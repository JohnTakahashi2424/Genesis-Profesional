<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Portafolio;
use Illuminate\Support\Facades\Log;

class PortafolioController extends Controller
{
    /**
     * Obtener el ID del usuario autenticado o solicitado
     */
    protected function getUserId(Request $request)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        return $jwtUser->id 
            ?? $request->user()?->id
            ?? $request->input('usuario_id') 
            ?? $request->query('usuario_id') 
            ?? $request->query('user_id') 
            ?? $request->header('X-User-Id');
    }

    /**
     * Listar todos los enlaces de portafolio del usuario autenticado
     * GET /api/portafolio
     */
    public function index(Request $request)
    {
        try {
            $userId = $this->getUserId($request);

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'Usuario no autenticado o no especificado.',
                    'data' => []
                ], 401);
            }

            $portafolios = Portafolio::where('usuario_id', $userId)
                ->orderBy('id', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $portafolios
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al listar portafolios: ' . $e->getMessage(), [
                'usuario_id' => $this->getUserId($request)
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'Error al obtener la lista de portafolios.',
                'data' => []
            ], 500);
        }
    }

    /**
     * Crear un nuevo enlace de portafolio para el usuario autenticado
     * POST /api/portafolio
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'link' => 'nullable|string|max:500',
            'link_portafolio' => 'nullable|string|max:500',
        ]);

        try {
            $userId = $this->getUserId($request);

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'Usuario no autenticado o no especificado.'
                ], 401);
            }

            $link = $request->filled('link') 
                ? trim($request->link) 
                : ($request->filled('link_portafolio') ? trim($request->link_portafolio) : '');

            if (empty($link)) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'El enlace del portafolio es obligatorio.'
                ], 422);
            }

            $portafolio = Portafolio::create([
                'usuario_id' => $userId,
                'titulo' => trim($request->titulo),
                'link_portafolio' => $link
            ]);

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Enlace de portafolio agregado exitosamente.',
                'data' => $portafolio
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Error al crear portafolio: ' . $e->getMessage(), [
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible agregar el enlace de portafolio.'
            ], 500);
        }
    }

    /**
     * Actualizar un enlace de portafolio existente
     * PUT /api/portafolio/{id}
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'link' => 'nullable|string|max:500',
            'link_portafolio' => 'nullable|string|max:500',
        ]);

        try {
            $userId = $this->getUserId($request);

            $portafolio = Portafolio::where('id', $id)
                ->where(function ($query) use ($userId) {
                    if ($userId) {
                        $query->where('usuario_id', $userId);
                    }
                })
                ->first();

            if (!$portafolio) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'Enlace de portafolio no encontrado.'
                ], 404);
            }

            $link = $request->filled('link') 
                ? trim($request->link) 
                : ($request->filled('link_portafolio') ? trim($request->link_portafolio) : $portafolio->link_portafolio);

            $portafolio->update([
                'titulo' => trim($request->titulo),
                'link_portafolio' => $link
            ]);

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Enlace de portafolio actualizado exitosamente.',
                'data' => $portafolio
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar portafolio: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible actualizar el enlace de portafolio.'
            ], 500);
        }
    }

    /**
     * Eliminar un enlace de portafolio
     * DELETE /api/portafolio/{id}
     */
    public function destroy(Request $request, $id)
    {
        try {
            $userId = $this->getUserId($request);

            $portafolio = Portafolio::where('id', $id)
                ->where(function ($query) use ($userId) {
                    if ($userId) {
                        $query->where('usuario_id', $userId);
                    }
                })
                ->first();

            if (!$portafolio) {
                return response()->json([
                    'status' => 'error',
                    'mensaje' => 'Enlace de portafolio no encontrado.'
                ], 404);
            }

            $portafolio->delete();

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Enlace de portafolio eliminado exitosamente.'
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al eliminar portafolio: ' . $e->getMessage(), [
                'id' => $id
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible eliminar el enlace de portafolio.'
            ], 500);
        }
    }

    /**
     * Métodos heredados para retrocompatibilidad
     * GET /api/portafolio/obtener/{usuarioId}
     */
    public function obtener($usuarioId)
    {
        try {
            $portafolio = Portafolio::where('usuario_id', $usuarioId)->first();

            return response()->json([
                'status' => 'success',
                'link_portafolio' => $portafolio ? $portafolio->link_portafolio : null,
                'data' => $portafolio ? [$portafolio] : []
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al obtener portafolio: ' . $e->getMessage(), [
                'usuario_id' => $usuarioId,
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'Error al obtener el portafolio.',
                'link_portafolio' => null,
            ], 500);
        }
    }

    /**
     * Guardar o actualizar un portafolio de manera heredada
     * POST /api/portafolio/guardar
     */
    public function guardar(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required',
            'link_portafolio' => 'nullable|string|max:500',
        ]);

        try {
            $usuarioId = $request->usuario_id;
            $link = $request->filled('link_portafolio') ? trim($request->link_portafolio) : null;
            $titulo = $request->input('titulo', 'My portafolio');

            $portafolio = Portafolio::where('usuario_id', $usuarioId)->first();

            if ($portafolio) {
                $portafolio->update([
                    'titulo' => $titulo,
                    'link_portafolio' => $link
                ]);
            } else {
                $portafolio = Portafolio::create([
                    'usuario_id' => $usuarioId,
                    'titulo' => $titulo,
                    'link_portafolio' => $link
                ]);
            }

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Link de portafolio guardado exitosamente.',
                'link_portafolio' => $portafolio->link_portafolio,
                'data' => $portafolio
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Error al guardar portafolio: ' . $e->getMessage(), [
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'mensaje' => 'No fue posible guardar el portafolio.',
            ], 500);
        }
    }
}
