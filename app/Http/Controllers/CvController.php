<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Models\CurriculumVitae;
use App\Http\Requests\GuardarCvRequest;
use App\Services\CvStorageService;

class CvController extends Controller
{
    /**
     * Guardar un NUEVO CV para un usuario (permite múltiples CVs por usuario).
     */
    public function guardar(GuardarCvRequest $request, CvStorageService $storageService)
    {
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

        // Delegar decodificación y almacenamiento del PDF
        $pdfInfo = $storageService->guardarPdf(
            $usuarioId,
            $request->pdf_base64,
            $request->titulo_cv,
            $usuario->nombres ?? 'Usuario'
        );

        $tituloCv      = $pdfInfo['titulo_cv'];
        $nombreArchivo = $pdfInfo['nombre_archivo'];
        $ruta          = $pdfInfo['ruta_archivo'];
        $urlPublica    = $pdfInfo['url_publica'];

        // Buscar si el usuario ya tiene un CV registrado por cvId o por su usuario_id
        $cv = null;
        if ($cvId) {
            $cv = CurriculumVitae::find($cvId);
        }
        if (!$cv) {
            $cv = CurriculumVitae::where('usuario_id', $usuarioId)->orderByDesc('id')->first();
        }

        // Procesar foto de perfil mediante servicio
        $fotoInput = $request->perfil['fotoUrl'] ?? $request->perfil['foto_url'] ?? null;
        $fotoUrl = $storageService->procesarFoto($usuarioId, $fotoInput, $cv?->foto_url);

        if ($usuarioId && $fotoUrl) {
            $storageService->sincronizarFotoUsuario($usuarioId, $fotoUrl);
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
            // Eliminar foto fÃ­sica anterior si cambiÃ³ y existÃ­a en public storage
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
            // Crear el registro Ãºnico de CV para este usuario
            $cv = CurriculumVitae::create($data);
        }

        // Mantener Ãºnicamente este registro para el usuario en la base de datos (elimina posibles duplicados antiguos)
        CurriculumVitae::where('usuario_id', $usuarioId)
            ->where('id', '!=', $cv->id)
            ->delete();

        // Guardar o actualizar el portafolio si se envió desde el formulario del CV
        $linkPortafolio = $request->perfil['link_portafolio'] 
            ?? $request->perfil['linkPortafolio'] 
            ?? $request->link_portafolio 
            ?? null;

        if ($linkPortafolio !== null) {
            try {
                \App\Models\Portafolio::updateOrCreate(
                    ['usuario_id' => $usuarioId],
                    ['link_portafolio' => !empty(trim($linkPortafolio)) ? trim($linkPortafolio) : null]
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('No se pudo guardar el portafolio desde CV: ' . $e->getMessage());
            }
        }

                // Registrar en el Historial de Progreso del estudiante
        try {
            $esNuevo = !$cvId;
            \App\Models\HistorialProgreso::registrar(
                userId: (int)$usuarioId,
                categoria: 'cv',
                titulo: $esNuevo ? 'Creación de cv' : 'Actualización de cv',
                descripcion: $esNuevo ? 'Creación y registro de nuevo curriculum vitae' : 'Actualización de datos personales en el cv',
                accionTexto: 'Ver perfil',
                accionUrl: '/cv',
                progreso: 15
            );

            if (!empty($fotoInput) && !empty($fotoUrl)) {
                \App\Models\HistorialProgreso::registrar(
                    userId: (int)$usuarioId,
                    categoria: 'perfil',
                    titulo: 'Actualización de fotografía',
                    descripcion: 'actualización de fotografía en el perfil',
                    accionTexto: 'Ver perfil',
                    accionUrl: '/perfil',
                    progreso: 18
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('No se pudo registrar historial de CV: ' . $e->getMessage());
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

        $portafolio = \App\Models\Portafolio::where('usuario_id', $usuarioId)->first();
        $linkPortafolio = $portafolio ? $portafolio->link_portafolio : null;

        if ($cvs->isEmpty()) {
            return response()->json([
                'mensaje'         => 'Este usuario aÃºn no tiene CV.',
                'tiene_cv'        => false,
                'cvs'             => [],
                'link_portafolio' => $linkPortafolio,
            ], 200);
        }

        $cvs->transform(function ($item) use ($linkPortafolio) {
            $item->link_portafolio = $linkPortafolio;
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
            'tiene_cv'        => true,
            'cvs'             => $cvs,
            'link_portafolio' => $linkPortafolio,
        ]);
    }

    /**
     * Eliminar un CV especÃ­fico por su ID de forma segura.
     */
    public function eliminar(Request $request, $cvId)
    {
        $cv = CurriculumVitae::find($cvId);

        if (!$cv) {
            return response()->json(['mensaje' => 'No se encontrÃ³ el CV.'], 404);
        }

        // Desvincular de postulaciones si la tabla existe
        if (Schema::hasTable('postulaciones')) {
            DB::table('postulaciones')->where('cv_id', $cvId)->update(['cv_id' => null]);
        }

        // Eliminar archivo fÃ­sico de forma segura (previene fallos en entornos con permisos especiales o OneDrive)
        try {
            if ($cv->ruta_archivo) {
                Storage::disk('public')->delete($cv->ruta_archivo);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("No se pudo eliminar el archivo fÃ­sico del CV {$cvId}: " . $e->getMessage());
        }

        $cv->delete();

        return response()->json(['mensaje' => 'CV eliminado correctamente.']);
    }
}
