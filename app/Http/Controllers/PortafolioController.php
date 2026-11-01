<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Portafolio;
use Illuminate\Support\Facades\Log;

class PortafolioController extends Controller
{
    /**
     * Obtener el portafolio asociado al ID de usuario
     * GET /api/portafolio/obtener/{usuarioId}
     */
    public function obtener($usuarioId)
    {
        try {
            $portafolio = Portafolio::where('usuario_id', $usuarioId)->first();

            return response()->json([
                'status' => 'success',
                'link_portafolio' => $portafolio ? $portafolio->link_portafolio : null,
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
     * Guardar o actualizar el portafolio del usuario
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

            $portafolio = Portafolio::updateOrCreate(
                ['usuario_id' => $usuarioId],
                ['link_portafolio' => $link]
            );

            return response()->json([
                'status' => 'success',
                'mensaje' => 'Link de portafolio guardado exitosamente.',
                'link_portafolio' => $portafolio->link_portafolio,
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

