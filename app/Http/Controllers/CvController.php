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

        $urlPublica = "/api/storage/{$ruta}";

        // Buscar si el usuario ya tiene un CV registrado por cvId o por su usuario_id
        $cv = null;
        if ($cvId) {
            $cv = CurriculumVitae::find($cvId);
        }
        if (!$cv) {
            $cv = CurriculumVitae::where('usuario_id', $usuarioId)->orderByDesc('id')->first();
        }

        // Procesar foto de perfil
        $fotoInput = $request->perfil['fotoUrl'] ?? $request->perfil['foto_url'] ?? null;
        $fotoUrl = null;

        if ($fotoInput && !empty($fotoInput)) {
            if (preg_match('#^data:image/(\w+);base64,#i', $fotoInput, $matches)) {
                $ext = strtolower($matches[1]);
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $ext = 'png';
                }
                $fotoDecoded = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $fotoInput));
                $nombreFoto = 'foto_' . time() . '.' . $ext;
                $carpetaFoto = "cv_fotos/{$usuarioId}";
                $rutaFoto = "{$carpetaFoto}/{$nombreFoto}";
                
                Storage::disk('public')->put($rutaFoto, $fotoDecoded);

                $publicPath = public_path("storage/{$rutaFoto}");
                if (!file_exists(dirname($publicPath))) {
                    @mkdir(dirname($publicPath), 0777, true);
                }
                @file_put_contents($publicPath, $fotoDecoded);

                $fotoUrl = "/api/storage/{$rutaFoto}";
            } else {
                $fotoUrl = str_starts_with($fotoInput, '/storage/') ? ('/api' . $fotoInput) : $fotoInput;
            }
        } else if ($cv && $cv->foto_url) {
            $fotoUrl = str_starts_with($cv->foto_url, '/storage/') ? ('/api' . $cv->foto_url) : $cv->foto_url;
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

        if ($cv) {
            // Eliminar foto física anterior si cambió y existía en public storage
            if ($cv->foto_url && $cv->foto_url !== $fotoUrl && str_contains($cv->foto_url, '/storage/')) {
                $oldFotoPath = str_replace('/storage/', '', $cv->foto_url);
                if (Storage::disk('public')->exists($oldFotoPath)) {
                    Storage::disk('public')->delete($oldFotoPath);
                }
            }

            // Eliminar archivo PDF anterior si existe
            if ($cv->ruta_archivo && Storage::disk('public')->exists($cv->ruta_archivo)) {
                Storage::disk('public')->delete($cv->ruta_archivo);
            }

            $cv->update($data);
        } else {
            // Crear el registro único de CV para este usuario
            $cv = CurriculumVitae::create($data);
        }

        // Mantener únicamente este registro para el usuario en la base de datos (elimina posibles duplicados antiguos)
        CurriculumVitae::where('usuario_id', $usuarioId)
            ->where('id', '!=', $cv->id)
            ->delete();

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

        if ($cvs->isEmpty() && $request->has('correo')) {
            $correo = $request->query('correo');
            $user = DB::table('usuarios')->where('correo', $correo)->first();
            if (!$user && Schema::hasTable('users')) {
                $user = DB::table('users')->where('email', $correo)->first();
            }
            if ($user) {
                $cvs = CurriculumVitae::where('usuario_id', $user->id)
                    ->orderByDesc('created_at')
                    ->get();
            }
        }

        if ($cvs->isEmpty()) {
            return response()->json(['mensaje' => 'Este usuario aún no tiene CV.', 'tiene_cv' => false, 'cvs' => []], 200);
        }

        $cvs->transform(function ($item) {
            if ($item->url_publica) {
                if (str_starts_with($item->url_publica, '/storage/')) {
                    $item->url_publica = '/api' . $item->url_publica;
                } elseif (str_starts_with($item->url_publica, 'storage/')) {
                    $item->url_publica = '/api/' . $item->url_publica;
                }
            }
            if ($item->foto_url) {
                if (str_starts_with($item->foto_url, '/storage/')) {
                    $item->foto_url = '/api' . $item->foto_url;
                } elseif (str_starts_with($item->foto_url, 'storage/')) {
                    $item->foto_url = '/api/' . $item->foto_url;
                }
            }
            return $item;
        });

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
