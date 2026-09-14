<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\CurriculumVitae;

class CvController extends Controller
{
    /**
     * Guardar un NUEVO CV para un usuario (permite múltiples CVs por usuario).
     */
    public function guardar(Request $request)
    {
        $request->validate([
            'usuario_id'  => 'required|integer',
            'pdf_base64'  => 'required|string',
            'titulo_cv'   => 'nullable|string|max:255',
            'cv_id'       => 'nullable|integer',
        ]);

        $usuarioId = $request->usuario_id;
        $cvId = $request->cv_id;

        // Verificar que el usuario existe en la tabla 'usuarios' o 'users'
        $usuario = null;
        if (Schema::hasTable('usuarios')) {
            $usuario = DB::table('usuarios')->where('id', $usuarioId)->first();
        }
        if (!$usuario && Schema::hasTable('users')) {
            $usuario = DB::table('users')->where('id', $usuarioId)->first();
        }

        if (!$usuario) {
            return response()->json(['mensaje' => 'Usuario no encontrado.'], 404);
        }

        // Decodificar el PDF de base64 (removiendo cualquier prefijo de URI de datos de forma segura)
        $pdfData = base64_decode(preg_replace('#^data:.*?base64,#i', '', $request->pdf_base64));

        // Nombre del archivo basado en el título del CV
        $tituloCv   = $request->titulo_cv ?? ('CV_' . ($usuario->nombres ?? 'Usuario'));
        $nombreArchivo = str_replace(' ', '_', $tituloCv) . '_' . $usuarioId . '_' . time() . '.pdf';

        // Ruta: storage/app/public/cvs/{usuario_id}/
        $carpeta  = "cvs/{$usuarioId}";
        $ruta     = "{$carpeta}/{$nombreArchivo}";

        // Guardar en disco
        Storage::disk('public')->put($ruta, $pdfData);

        $urlPublica = "/storage/{$ruta}";

        // Procesar foto de perfil
        $fotoUrl = null;
        if (isset($request->perfil['fotoUrl']) && !empty($request->perfil['fotoUrl'])) {
            $fotoData = $request->perfil['fotoUrl'];
            // Si es una imagen codificada en base64
            if (preg_match('#^data:image/(\w+);base64,#i', $fotoData, $matches)) {
                $ext = strtolower($matches[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'png';
                }
                $fotoDecoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $fotoData));
                $nombreFoto = 'foto_' . time() . '.' . $ext;
                $carpetaFoto = "cv_fotos/{$usuarioId}";
                $rutaFoto = "{$carpetaFoto}/{$nombreFoto}";
                
                Storage::disk('public')->put($rutaFoto, $fotoDecoded);
                $fotoUrl = "/storage/{$rutaFoto}";
            } else {
                // Si ya es una URL existente en el servidor o externa
                $fotoUrl = $fotoData;
            }
        }

        $data = [
            'usuario_id'       => $usuarioId,
            'titulo_cv'        => $tituloCv,
            'nombre_archivo'   => $nombreArchivo,
            'ruta_archivo'     => $ruta,
            'url_publica'      => $urlPublica,
            'nombre_completo'  => $request->perfil['nombre']          ?? null,
            'profesion'        => $request->perfil['profesion']       ?? null,
            'foto_url'         => $fotoUrl,
            'direccion'        => $request->perfil['direccion']        ?? null,
            'email'            => $request->perfil['email']            ?? null,
            'telefono'         => $request->perfil['telefono']         ?? null,
            'sobre_mi'         => $request->perfil['sobreMi']          ?? null,
            'educacion'        => $request->perfil['educacion']        ?? null,
            'objetivo'         => $request->objetivos['objetivo']      ?? null,
            'valores'          => $request->objetivos['valores']       ?? null,
            'conocimientos'    => $request->objetivos['conocimientos'] ?? null,
            'idiomas'          => $request->objetivos['idiomas']       ?? null,
            'certificados'     => $request->logros['certificados']     ?? null,
            'habilidades'      => $request->logros['habilidades']      ?? null,
            'logros'           => $request->logros['logros']           ?? null,
            'proyectos_sociales' => $request->logros['proyectos']      ?? null,
            'color_plantilla'  => $request->diseno['color']            ?? '#67000F',
            'fuente'           => $request->diseno['fuente']           ?? 'Montserrat',
            'estado'           => 'activo',
        ];

        if ($cvId) {
            $cv = CurriculumVitae::find($cvId);
            if ($cv) {
                // Eliminar foto física anterior si cambió y existía en public storage
                if ($cv->foto_url && $cv->foto_url !== $fotoUrl) {
                    $oldFotoPath = str_replace('/storage/', '', $cv->foto_url);
                    if (Storage::disk('public')->exists($oldFotoPath)) {
                        Storage::disk('public')->delete($oldFotoPath);
                    }
                }

                // Eliminar archivo físico anterior si existe
                if ($cv->ruta_archivo) {
                    Storage::disk('public')->delete($cv->ruta_archivo);
                }
                $cv->update($data);
            } else {
                $cv = CurriculumVitae::create($data);
            }
        } else {
            $cv = CurriculumVitae::create($data);
        }

        return response()->json([
            'mensaje'        => 'CV guardado correctamente.',
            'cv_id'          => $cv->id,
            'url_publica'    => $urlPublica,
            'nombre_archivo' => $nombreArchivo,
            'titulo_cv'      => $tituloCv,
        ]);
    }

    /**
     * Obtener TODOS los CVs de un usuario.
     */
    public function obtener(Request $request, $usuarioId)
    {
        $cvs = CurriculumVitae::where('usuario_id', $usuarioId)
            ->orderByDesc('created_at')
            ->get();

        if ($cvs->isEmpty()) {
            return response()->json(['mensaje' => 'Este usuario aún no tiene CV.', 'tiene_cv' => false, 'cvs' => []], 200);
        }

        return response()->json([
            'tiene_cv' => true,
            'cvs'      => $cvs,
        ]);
    }

    /**
     * Eliminar un CV específico por su ID de forma segura.
     */
    public function eliminar(Request $request, $cvId)
    {
        $cv = CurriculumVitae::find($cvId);

        if (!$cv) {
            return response()->json(['mensaje' => 'No se encontró el CV.'], 404);
        }

        // Desvincular de postulaciones si la tabla existe
        if (Schema::hasTable('postulaciones')) {
            DB::table('postulaciones')->where('cv_id', $cvId)->update(['cv_id' => null]);
        }

        // Eliminar archivo físico de forma segura (previene fallos en entornos con permisos especiales o OneDrive)
        try {
            if ($cv->ruta_archivo) {
                Storage::disk('public')->delete($cv->ruta_archivo);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("No se pudo eliminar el archivo físico del CV {$cvId}: " . $e->getMessage());
        }

        $cv->delete();

        return response()->json(['mensaje' => 'CV eliminado correctamente.']);
    }
}
