<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\InformeFinal;
use App\Models\PresupuestoItem;
use App\Models\Reporte;
use App\Models\Pasante;
use App\Models\User;
use App\Models\HistorialProgreso;
use App\Models\Notificacion;

class InformeFinalController extends Controller
{
    /**
     * Obtener datos iniciales para el formulario del Informe Final.
     * Carga:
     * - Datos del pasante autenticado
     * - Empresa / Institución donde se realiza la pasantía
     * - Lista de reportes mensuales disponibles para adjuntar (con estado Aprobado o registrados)
     * - Columnas y filas por defecto para la tabla de presupuesto dinámico
     *
     * GET /api/informes-finales/formulario-inicial
     */
    public function formularioInicial(Request $request)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        if (!$userId) {
            $userId = User::first()?->id;
        }

        $user = $userId ? User::with('estudiante')->find($userId) : null;
        $pasante = $userId ? Pasante::where('user_id', $userId)->first() : null;

        $nombreCompleto = $user ? trim("{$user->nombres} {$user->apellidos}") : '';
        $institucionSugerida = $pasante->institucion ?? $pasante->empresa ?? '';

        // Obtener los reportes mensuales creados por este pasante
        $reportesQuery = Reporte::with('actividades')->withCount('actividades')->orderBy('created_at', 'asc');
        if ($userId) {
            $reportesQuery->where('user_id', $userId);
        }
        $reportesDisponibles = $reportesQuery->get();

        // Si la base de datos aún no tiene reportes para este usuario, preparar sugerencias
        $reportesFormateados = $reportesDisponibles->map(function ($rep) {
            return [
                'id' => $rep->id,
                'mes' => $rep->mes,
                'periodo' => $rep->periodo,
                'actividades_desarrolladas' => $rep->actividades_desarrolladas,
                'estado' => $rep->estado,
                'aprobado' => strtolower($rep->estado) === 'aprobado',
            ];
        });

        // Configuración por defecto del presupuesto dinámico según la maqueta
        $columnasDefecto = ['Ene.', 'Feb.', 'Mar.', 'Abr.', 'May.', 'Junio', 'Jul.'];
        $itemsDefecto = [
            [
                'gasto' => 'Comida',
                'orden' => 1,
                'montos' => array_fill_keys($columnasDefecto, 0),
                'total' => 0,
            ],
            [
                'gasto' => 'Pasaje',
                'orden' => 2,
                'montos' => array_fill_keys($columnasDefecto, 0),
                'total' => 0,
            ],
        ];

        return response()->json([
            'exito' => true,
            'data' => [
                'user_id' => $user?->id,
                'pasante_id' => $pasante?->id,
                'nombre_pasante' => $nombreCompleto,
                'institucion' => $institucionSugerida,
                'reportes_disponibles' => $reportesFormateados,
                'presupuesto_columnas_sugeridas' => $columnasDefecto,
                'presupuesto_items_sugeridos' => $itemsDefecto,
            ]
        ]);
    }

    /**
     * Listar los informes finales registrados.
     * Soporta filtros de búsqueda, estado y usuario.
     *
     * GET /api/informes-finales
     */
    public function index(Request $request)
    {
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $jwtUser->id ?? $request->header('X-User-Id') ?? $request->query('user_id');

        $query = InformeFinal::with(['reportes', 'presupuestoItems'])
            ->withCount('reportes')
            ->orderByDesc('created_at');

        // Filtrar por usuario si no es supervisor o administrador global
        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->rol === 'estudiante') {
                $query->where('user_id', $userId);
            } elseif ($request->filled('user_id')) {
                $query->where('user_id', $request->query('user_id'));
            }
        }

        // Filtro de búsqueda por institución, pasante o presentación
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre_pasante', 'ilike', "%{$search}%")
                  ->orWhere('institucion', 'ilike', "%{$search}%")
                  ->orWhere('presentacion', 'ilike', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->filled('estado') && $request->query('estado') !== 'Todos') {
            $query->where('estado', $request->query('estado'));
        }

        $informes = $query->get();

        return response()->json([
            'exito' => true,
            'total' => $informes->count(),
            'data' => $informes,
        ]);
    }

    /**
     * Obtener un informe final específico con todas sus relaciones cargadas.
     *
     * GET /api/informes-finales/{id}
     */
    public function show($id)
    {
        $informe = InformeFinal::with([
            'user',
            'pasante',
            'reportes.actividades',
            'presupuestoItems',
        ])->find($id);

        if (!$informe) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Informe final no encontrado.',
            ], 404);
        }

        return response()->json([
            'exito' => true,
            'data' => $informe,
        ]);
    }

    /**
     * Crear y guardar un nuevo Informe Final.
     * Almacena:
     * - Datos descriptivos generales
     * - Relación N:M con los reportes mensuales seleccionados
     * - Presupuesto dinámico (columnas de meses y filas de gastos)
     * - Archivos anexos adjuntos
     * - Generación de notificación y registro en historial de progreso
     *
     * POST /api/informes-finales
     */
    public function store(Request $request)
    {
        $this->normalizarDatosRequest($request);

        $validator = Validator::make($request->all(), [
            'nombre_pasante'          => 'nullable|string|max:150',
            'institucion'             => 'required|string|max:200',
            'presentacion'            => 'required|string',
            'generalidades'           => 'required|string',
            'procedimiento_pasantia'  => 'required|string',
            'descripcion_empresa'     => 'required|string',
            'descripcion_pasantia'    => 'required|string',
            'estado'                  => 'nullable|string|in:Borrador,Enviado,En revisión,Aprobado,Observado',
            'reportes_ids'            => 'nullable|array',
            'reportes_ids.*'          => 'integer|exists:reportes,id',
            'presupuesto_columnas'    => 'nullable|array',
            'presupuesto_columnas.*'  => 'string|max:50',
            'presupuesto_items'       => 'nullable|array',
            'presupuesto_items.*.gasto'  => 'required|string|max:100',
            'presupuesto_items.*.orden'  => 'nullable|integer',
            'presupuesto_items.*.montos' => 'nullable|array',
            'anexos'                  => 'nullable',
            'user_id'                 => 'nullable|integer|exists:users,id',
            'pasante_id'              => 'nullable|integer|exists:pasantes,id',
        ], [
            'institucion.required'            => 'La institución donde se realizó la pasantía es obligatoria.',
            'presentacion.required'           => 'El campo presentación es obligatorio.',
            'generalidades.required'          => 'El campo generalidades es obligatorio.',
            'procedimiento_pasantia.required' => 'El procedimiento para el proceso de la pasantía es obligatorio.',
            'descripcion_empresa.required'    => 'La descripción de la institución o empresa es obligatoria.',
            'descripcion_pasantia.required'   => 'La descripción de la pasantía es obligatoria.',
            'reportes_ids.*.exists'           => 'Uno de los reportes mensuales seleccionados no existe en el sistema.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Por favor verifique los datos del formulario.',
                'errores' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Resolver usuario y pasante
        $jwtUser = $request->attributes->get('jwt_user');
        $userId = $validated['user_id'] ?? ($jwtUser->id ?? $request->header('X-User-Id'));
        if (!$userId) {
            $userId = User::first()?->id;
        }

        $pasanteId = $validated['pasante_id'] ?? null;
        if (!$pasanteId && $userId) {
            $pasante = Pasante::where('user_id', $userId)->first();
            $pasanteId = $pasante?->id;
        }

        // Si no se proporcionó el nombre del pasante, resolverlo desde el User
        $nombrePasante = $validated['nombre_pasante'] ?? null;
        if (!$nombrePasante && $userId) {
            $u = User::find($userId);
            $nombrePasante = $u ? trim("{$u->nombres} {$u->apellidos}") : 'Pasante';
        }

        // Procesar archivos anexos (soporta multipart y URLs previas)
        $anexosGuardados = $this->procesarAnexos($request);

        // Columnas de presupuesto por defecto si no se enviaron
        $columnasPresupuesto = $validated['presupuesto_columnas'] ?? ['Ene.', 'Feb.', 'Mar.', 'Abr.', 'May.', 'Junio', 'Jul.'];

        return DB::transaction(function () use ($validated, $userId, $pasanteId, $nombrePasante, $anexosGuardados, $columnasPresupuesto) {
            // 1. Crear el informe final principal
            $informe = InformeFinal::create([
                'user_id'                 => $userId,
                'pasante_id'              => $pasanteId,
                'nombre_pasante'          => $nombrePasante,
                'institucion'             => $validated['institucion'],
                'presentacion'            => $validated['presentacion'],
                'generalidades'           => $validated['generalidades'],
                'procedimiento_pasantia'  => $validated['procedimiento_pasantia'],
                'descripcion_empresa'     => $validated['descripcion_empresa'],
                'descripcion_pasantia'    => $validated['descripcion_pasantia'],
                'anexos'                  => $anexosGuardados,
                'presupuesto_columnas'    => $columnasPresupuesto,
                'presupuesto_total'       => 0,
                'estado'                  => $validated['estado'] ?? 'Enviado',
            ]);

            // 2. Asociar reportes mensuales seleccionados vía tabla pivote
            if (!empty($validated['reportes_ids'])) {
                $informe->reportes()->sync($validated['reportes_ids']);
            }

            // 3. Crear filas dinámicas del presupuesto
            $totalGeneralPresupuesto = 0;
            if (!empty($validated['presupuesto_items'])) {
                foreach ($validated['presupuesto_items'] as $index => $itemData) {
                    $montos = $itemData['montos'] ?? [];
                    // Asegurar que todos los montos sean numéricos
                    $montosLimpios = [];
                    $totalFila = 0;
                    foreach ($montos as $col => $val) {
                        $valNum = (float)$val;
                        $montosLimpios[$col] = $valNum;
                        $totalFila += $valNum;
                    }

                    $itemCreado = $informe->presupuestoItems()->create([
                        'gasto'  => $itemData['gasto'],
                        'orden'  => $itemData['orden'] ?? ($index + 1),
                        'montos' => $montosLimpios,
                        'total'  => $totalFila,
                    ]);

                    $totalGeneralPresupuesto += $totalFila;
                }
            }

            // Actualizar total consolidado del presupuesto
            $informe->update(['presupuesto_total' => $totalGeneralPresupuesto]);

            // 4. Registrar en el Historial de Progreso
            try {
                $esEnvio = ($informe->estado === 'Enviado');
                $tituloProgreso = $esEnvio ? 'Envío de Informe Final' : 'Creación de Informe Final';
                HistorialProgreso::registrar(
                    userId: (int)$userId,
                    categoria: 'reportes',
                    titulo: $tituloProgreso,
                    descripcion: "Se ha registrado el {$tituloProgreso} para la institución {$informe->institucion}.",
                    accionTexto: 'Ver informe final',
                    accionUrl: '/reportes/informe-final/' . $informe->id,
                    progreso: $esEnvio ? 90 : 70
                );

                // 5. Crear notificación automática en el sistema
                Notificacion::crearNotificacion(
                    userId: (int)$userId,
                    titulo: 'Informe final enviado',
                    mensaje: "Tu informe final para {$informe->institucion} ha sido enviado de manera exitosa y se encuentra en revisión.",
                    tipo: 'reporte',
                    origenTipo: 'sistema',
                    remitenteNombre: 'Notificaciones',
                    enlace: '/reportes'
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo registrar notificación/historial para informe final: ' . $e->getMessage());
            }

            return response()->json([
                'exito' => true,
                'mensaje' => 'Informe final registrado exitosamente.',
                'data' => $informe->load(['reportes', 'presupuestoItems']),
            ], 201);
        });
    }

    /**
     * Actualizar un informe final existente.
     *
     * PUT /api/informes-finales/{id}
     */
    public function update(Request $request, $id)
    {
        $informe = InformeFinal::with(['presupuestoItems', 'reportes'])->find($id);

        if (!$informe) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Informe final no encontrado.',
            ], 404);
        }

        $this->normalizarDatosRequest($request);

        $validator = Validator::make($request->all(), [
            'nombre_pasante'          => 'nullable|string|max:150',
            'institucion'             => 'sometimes|required|string|max:200',
            'presentacion'            => 'sometimes|required|string',
            'generalidades'           => 'sometimes|required|string',
            'procedimiento_pasantia'  => 'sometimes|required|string',
            'descripcion_empresa'     => 'sometimes|required|string',
            'descripcion_pasantia'    => 'sometimes|required|string',
            'estado'                  => 'nullable|string|in:Borrador,Enviado,En revisión,Aprobado,Observado',
            'reportes_ids'            => 'nullable|array',
            'reportes_ids.*'          => 'integer|exists:reportes,id',
            'presupuesto_columnas'    => 'nullable|array',
            'presupuesto_columnas.*'  => 'string|max:50',
            'presupuesto_items'       => 'nullable|array',
            'presupuesto_items.*.gasto'  => 'required_with:presupuesto_items|string|max:100',
            'presupuesto_items.*.orden'  => 'nullable|integer',
            'presupuesto_items.*.montos' => 'nullable|array',
            'anexos'                  => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Error de validación al actualizar el informe.',
                'errores' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Procesar archivos anexos mezclando existentes y nuevos
        $anexosActuales = $informe->anexos ?? [];
        $anexosNuevos = $this->procesarAnexos($request, $anexosActuales);

        return DB::transaction(function () use ($informe, $validated, $anexosNuevos) {
            // Actualizar campos principales
            $camposActualizables = [
                'nombre_pasante',
                'institucion',
                'presentacion',
                'generalidades',
                'procedimiento_pasantia',
                'descripcion_empresa',
                'descripcion_pasantia',
                'estado',
                'presupuesto_columnas',
            ];

            foreach ($camposActualizables as $campo) {
                if (array_key_exists($campo, $validated)) {
                    $informe->{$campo} = $validated[$campo];
                }
            }

            $informe->anexos = $anexosNuevos;

            // Sincronizar reportes adjuntos si se enviaron
            if (array_key_exists('reportes_ids', $validated)) {
                $informe->reportes()->sync($validated['reportes_ids'] ?? []);
            }

            // Sincronizar items del presupuesto
            if (array_key_exists('presupuesto_items', $validated)) {
                // Eliminar items previos y recrear para garantizar coherencia
                $informe->presupuestoItems()->delete();

                $totalGeneral = 0;
                foreach ($validated['presupuesto_items'] as $index => $itemData) {
                    $montos = $itemData['montos'] ?? [];
                    $montosLimpios = [];
                    $totalFila = 0;
                    foreach ($montos as $col => $val) {
                        $valNum = (float)$val;
                        $montosLimpios[$col] = $valNum;
                        $totalFila += $valNum;
                    }

                    $informe->presupuestoItems()->create([
                        'gasto'  => $itemData['gasto'],
                        'orden'  => $itemData['orden'] ?? ($index + 1),
                        'montos' => $montosLimpios,
                        'total'  => $totalFila,
                    ]);

                    $totalGeneral += $totalFila;
                }

                $informe->presupuesto_total = $totalGeneral;
            }

            $informe->save();

            return response()->json([
                'exito' => true,
                'mensaje' => 'Informe final actualizado correctamente.',
                'data' => $informe->load(['reportes', 'presupuestoItems']),
            ]);
        });
    }

    /**
     * Eliminar un informe final y sus anexos almacenados en disco.
     *
     * DELETE /api/informes-finales/{id}
     */
    public function destroy($id)
    {
        $informe = InformeFinal::find($id);

        if (!$informe) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Informe final no encontrado.',
            ], 404);
        }

        // Eliminar archivos físicos de anexos en el disco público si existen
        if (!empty($informe->anexos) && is_array($informe->anexos)) {
            foreach ($informe->anexos as $anexo) {
                if (is_string($anexo) && str_starts_with($anexo, '/api/storage/')) {
                    $storagePath = str_replace('/api/storage/', '', $anexo);
                    Storage::disk('public')->delete($storagePath);
                }
            }
        }

        $informe->delete();

        return response()->json([
            'exito' => true,
            'mensaje' => 'Informe final eliminado correctamente.',
        ]);
    }

    /**
     * Cambiar el estado del informe final (para uso de supervisores/administración).
     * Estados: 'En revisión', 'Aprobado', 'Observado'.
     *
     * PATCH /api/informes-finales/{id}/estado
     */
    public function cambiarEstado(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'estado'        => 'required|string|in:Borrador,Enviado,En revisión,Aprobado,Observado',
            'observaciones' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Estado no válido.',
                'errores' => $validator->errors(),
            ], 422);
        }

        $informe = InformeFinal::find($id);

        if (!$informe) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Informe final no encontrado.',
            ], 404);
        }

        $nuevoEstado = $request->input('estado');
        $informe->estado = $nuevoEstado;
        $informe->save();

        // Notificar al estudiante y registrar en historial
        try {
            $esObservado = ($nuevoEstado === 'Observado');
            $esAprobado = ($nuevoEstado === 'Aprobado');

            $tituloNotif = $esObservado
                ? 'Observaciones en Informe Final'
                : ($esAprobado ? 'Informe Final Aprobado' : 'Estado de Informe Final Actualizado');

            $mensajeNotif = $esObservado
                ? ($request->input('observaciones') ?: "Se han realizado observaciones a tu informe final de {$informe->institucion}. Por favor revisa y corrige los puntos indicados.")
                : "Tu informe final para {$informe->institucion} ha sido marcado como {$nuevoEstado}.";

            Notificacion::crearNotificacion(
                userId: (int)$informe->user_id,
                titulo: $tituloNotif,
                mensaje: $mensajeNotif,
                tipo: $esObservado ? 'observacion' : 'reporte',
                origenTipo: $esObservado ? 'usuario' : 'sistema',
                remitenteNombre: $esObservado ? 'Jenny Flores' : 'Notificaciones',
                enlace: '/reportes'
            );

            HistorialProgreso::registrar(
                userId: (int)$informe->user_id,
                categoria: 'reportes',
                titulo: $tituloNotif,
                descripcion: $mensajeNotif,
                accionTexto: 'Ver informe final',
                accionUrl: '/reportes',
                progreso: $esAprobado ? 100 : 85
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo generar notificación de cambio de estado de informe final: ' . $e->getMessage());
        }

        return response()->json([
            'exito' => true,
            'mensaje' => "Estado del informe final actualizado a {$nuevoEstado}.",
            'data' => $informe,
        ]);
    }

    /**
     * Normalizar arrays que puedan venir serializados como string JSON en FormData.
     */
    private function normalizarDatosRequest(Request $request): void
    {
        // Normalizar reportes_ids
        if ($request->has('reportes_ids') && is_string($request->reportes_ids)) {
            $decoded = json_decode($request->reportes_ids, true);
            if (is_array($decoded)) {
                $request->merge(['reportes_ids' => $decoded]);
            }
        }

        // Normalizar presupuesto_columnas
        if ($request->has('presupuesto_columnas') && is_string($request->presupuesto_columnas)) {
            $decoded = json_decode($request->presupuesto_columnas, true);
            if (is_array($decoded)) {
                $request->merge(['presupuesto_columnas' => $decoded]);
            }
        }

        // Normalizar presupuesto_items
        if ($request->has('presupuesto_items') && is_string($request->presupuesto_items)) {
            $decoded = json_decode($request->presupuesto_items, true);
            if (is_array($decoded)) {
                $request->merge(['presupuesto_items' => $decoded]);
            }
        }

        // Normalizar anexos si vienen como JSON string
        if ($request->has('anexos') && is_string($request->anexos) && !str_starts_with($request->anexos, 'data:')) {
            $decoded = json_decode($request->anexos, true);
            if (is_array($decoded)) {
                $request->merge(['anexos' => $decoded]);
            }
        }
    }

    /**
     * Procesar y almacenar archivos anexos de manera segura en el almacenamiento público.
     * Soporta archivos multipart (`$request->file('archivos')`), base64 o URLs existentes.
     */
    private function procesarAnexos(Request $request, array $existentes = []): array
    {
        $urls = $existentes;

        // 1. Archivos subidos vía multipart ('archivos' o 'anexos_archivos')
        $archivosSubidos = [];
        if ($request->hasFile('archivos')) {
            $archivosSubidos = is_array($request->file('archivos')) ? $request->file('archivos') : [$request->file('archivos')];
        } elseif ($request->hasFile('anexos')) {
            $archivosSubidos = is_array($request->file('anexos')) ? $request->file('anexos') : [$request->file('anexos')];
        }

        foreach ($archivosSubidos as $archivo) {
            if ($archivo && $archivo->isValid()) {
                $extension = strtolower($archivo->getClientOriginalExtension());
                // Validar extensiones seguras
                $permitidas = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'webp', 'xlsx', 'xls', 'zip'];
                if (in_array($extension, $permitidas)) {
                    $path = $archivo->store('informes_finales/anexos', 'public');
                    $urls[] = [
                        'nombre' => $archivo->getClientOriginalName(),
                        'url'    => "/api/storage/{$path}",
                        'tipo'   => $extension,
                        'tamano' => $archivo->getSize(),
                    ];
                }
            }
        }

        // 2. Anexos enviados como array de objetos o URLs directas
        if ($request->has('anexos') && is_array($request->anexos)) {
            foreach ($request->anexos as $index => $item) {
                // Base64
                if (is_string($item) && preg_match('#^data:(image|application)/(\w+);base64,#i', $item, $matches)) {
                    $ext = strtolower($matches[2]);
                    if ($ext === 'vnd.openxmlformats-officedocument.wordprocessingml.document') $ext = 'docx';
                    if ($ext === 'msword') $ext = 'doc';

                    $decoded = base64_decode(preg_replace('#^data:[^;]+;base64,#i', '', $item));
                    $filename = 'anexo_' . time() . "_{$index}." . $ext;
                    $path = "informes_finales/anexos/{$filename}";
                    Storage::disk('public')->put($path, $decoded);
                    $urls[] = [
                        'nombre' => $filename,
                        'url'    => "/api/storage/{$path}",
                        'tipo'   => $ext,
                        'tamano' => strlen($decoded),
                    ];
                } elseif (is_array($item) && isset($item['url'])) {
                    // Ya formateado como objeto de anexo
                    $urls[] = $item;
                } elseif (is_string($item) && !empty($item)) {
                    $urls[] = [
                        'nombre' => basename($item),
                        'url'    => $item,
                        'tipo'   => pathinfo($item, PATHINFO_EXTENSION) ?: 'archivo',
                    ];
                }
            }
        }

        // Eliminar posibles duplicados por URL
        $unicos = [];
        $urlsFinales = [];
        foreach ($urls as $u) {
            $clave = is_array($u) ? ($u['url'] ?? json_encode($u)) : (string)$u;
            if (!isset($unicos[$clave])) {
                $unicos[$clave] = true;
                $urlsFinales[] = $u;
            }
        }

        return $urlsFinales;
    }
}

