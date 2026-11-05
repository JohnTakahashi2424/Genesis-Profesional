<?php

namespace App\Http\Controllers\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Génesis Profesional - API Documentation",
    version: "1.0.0",
    description: "API RESTful oficial de la plataforma Génesis Profesional para el Trabajo de Graduación Modalidad Pasantía (Facultad de Ciencia y Tecnología - Universidad Gerardo Barrios). Desarrollada con Laravel 12 y PostgreSQL.",
    contact: new OA\Contact(
        name: "Equipo de Desarrollo Génesis Profesional",
        email: "soporte@genesisprofesional.ugb.edu.sv"
    )
)]
#[OA\Server(
    url: "/",
    description: "Servidor API Actual (Proxy Netlify / Local)"
)]
#[OA\Server(
    url: "https://genesis-profesional.onrender.com",
    description: "Servidor de Producción (Render)"
)]
#[OA\Server(
    url: "http://localhost:8080",
    description: "Servidor Local de Desarrollo"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Ingrese su token JWT generado en /api/auth/login"
)]
#[OA\Tag(name: "Autenticación", description: "Endpoints de acceso, registro de pasantes y recuperación de contraseña")]
#[OA\Tag(name: "Reportes Mensuales", description: "Gestión completa de reportes mensuales de actividades y evidencias")]
#[OA\Tag(name: "Informe Final", description: "Gestión del informe final con presupuesto dinámico y anexos")]
#[OA\Tag(name: "Notificaciones", description: "Centro de notificaciones y avisos automáticos de pasantía")]
#[OA\Tag(name: "Historial de Progreso", description: "Línea de tiempo y auditoría de eventos de pasantía")]
#[OA\Tag(name: "Currículum Vitae (CV)", description: "Gestión del currículum profesional y exportación")]
#[OA\Tag(name: "Portafolio y Perfil", description: "Perfil del estudiante y portafolio académico")]
#[OA\Tag(name: "Pasantía", description: "Consulta del estado oficial del proceso de pasantía")]
class SwaggerAnnotations
{
    // =========================================================================
    // AUTENTICACIÓN
    // =========================================================================

    #[OA\Post(
        path: "/api/auth/login",
        operationId: "loginUsuario",
        tags: ["Autenticación"],
        summary: "Iniciar sesión y obtener token JWT",
        description: "Valida que el correo pertenezca al dominio institucional @ugb.edu.sv y que la contraseña coincida con el hash Bcrypt.",
        requestBody: new OA\RequestBody(
            required: true,
            description: "Credenciales de acceso",
            content: new OA\JsonContent(
                required: ["correo", "contrasena"],
                properties: [
                    new OA\Property(property: "correo", type: "string", format: "email", example: "usss003024@ugb.edu.sv"),
                    new OA\Property(property: "contrasena", type: "string", format: "password", example: "Password123#")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Autenticación exitosa con Token JWT",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "status", type: "string", example: "success"),
                        new OA\Property(property: "token", type: "string", example: "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."),
                        new OA\Property(property: "usuario", type: "object")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Credenciales incorrectas"),
            new OA\Response(response: 404, description: "Usuario no registrado"),
            new OA\Response(response: 422, description: "Error de validación (dominio no institucional)")
        ]
    )]
    public function docAuthLogin() {}

    #[OA\Post(
        path: "/api/auth/registro",
        operationId: "registroEstudiante",
        tags: ["Autenticación"],
        summary: "Registrar nuevo usuario estudiante",
        description: "Comprueba que el correo institucional y el código de estudiante existan previamente en el padrón UGB antes de crear el usuario.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombres", "apellidos", "correo_institucional", "codigo_estudiante", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "nombres", type: "string", example: "Dallana Lucia"),
                    new OA\Property(property: "apellidos", type: "string", example: "Campos Garcia"),
                    new OA\Property(property: "correo_institucional", type: "string", format: "email", example: "usss003024@ugb.edu.sv"),
                    new OA\Property(property: "codigo_estudiante", type: "string", example: "USSS003024"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "ClaveSegura2026#"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "ClaveSegura2026#")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Usuario registrado exitosamente"),
            new OA\Response(response: 422, description: "Validación fallida o no existe en padrón de estudiantes UGB")
        ]
    )]
    public function docAuthRegistro() {}

    #[OA\Post(
        path: "/api/auth/enviar-codigo",
        operationId: "enviarCodigoRecuperacion",
        tags: ["Autenticación"],
        summary: "Enviar código de 6 dígitos para recuperación de contraseña",
        description: "Envía un código de recuperación vía API REST Brevo (puerto HTTP seguro) para cambiar la contraseña.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["correo"],
                properties: [
                    new OA\Property(property: "correo", type: "string", format: "email", example: "usss003024@ugb.edu.sv")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Código de recuperación enviado exitosamente"),
            new OA\Response(response: 404, description: "Correo institucional no encontrado"),
            new OA\Response(response: 422, description: "Formato de correo no válido")
        ]
    )]
    public function docAuthEnviarCodigo() {}

    #[OA\Post(
        path: "/api/auth/verificar-codigo",
        operationId: "verificarCodigoRecuperacion",
        tags: ["Autenticación"],
        summary: "Verificar código de 6 dígitos",
        description: "Comprueba que el código de 6 dígitos ingresado coincida y no haya expirado.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["correo", "codigo"],
                properties: [
                    new OA\Property(property: "correo", type: "string", format: "email", example: "usss003024@ugb.edu.sv"),
                    new OA\Property(property: "codigo", type: "string", example: "123456")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Código válido"),
            new OA\Response(response: 400, description: "Código incorrecto o expirado")
        ]
    )]
    public function docAuthVerificarCodigo() {}

    #[OA\Post(
        path: "/api/auth/recuperar",
        operationId: "restablecerContrasena",
        tags: ["Autenticación"],
        summary: "Restablecer contraseña con código validado",
        description: "Actualiza la contraseña del usuario encriptándola con Bcrypt y regresa inmediatamente para login.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["correo", "codigo", "contrasena", "contrasena_confirmation"],
                properties: [
                    new OA\Property(property: "correo", type: "string", format: "email", example: "usss003024@ugb.edu.sv"),
                    new OA\Property(property: "codigo", type: "string", example: "123456"),
                    new OA\Property(property: "contrasena", type: "string", format: "password", example: "NuevaPassword2026#"),
                    new OA\Property(property: "contrasena_confirmation", type: "string", format: "password", example: "NuevaPassword2026#")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Contraseña actualizada exitosamente"),
            new OA\Response(response: 422, description: "Validación de contraseñas fallida")
        ]
    )]
    public function docAuthRecuperar() {}

    #[OA\Post(
        path: "/api/auth/logout",
        operationId: "cerrarSesion",
        tags: ["Autenticación"],
        security: [["bearerAuth" => []]],
        summary: "Cerrar sesión del usuario",
        responses: [
            new OA\Response(response: 200, description: "Sesión cerrada correctamente")
        ]
    )]
    public function docAuthLogout() {}

    // =========================================================================
    // REPORTES MENSUALES
    // =========================================================================

    #[OA\Get(
        path: "/api/reportes",
        operationId: "listarReportes",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Listar reportes mensuales del pasante",
        description: "Devuelve los reportes registrados con conteo de actividades y soporte para filtros por búsqueda y estado.",
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, description: "Buscar por mes o área", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "estado", in: "query", required: false, description: "Filtrar por estado", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "mes", in: "query", required: false, description: "Filtrar por mes", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de reportes obtenida exitosamente"),
            new OA\Response(response: 401, description: "No autorizado")
        ]
    )]
    public function docReportesIndex() {}

    #[OA\Get(
        path: "/api/reportes/formulario-inicial",
        operationId: "obtenerFormularioInicialReporte",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Obtener datos sugeridos para nuevo reporte mensual",
        responses: [
            new OA\Response(response: 200, description: "Datos de pasante y mes sugerido obtenidos")
        ]
    )]
    public function docReportesFormularioInicial() {}

    #[OA\Get(
        path: "/api/reportes/{id}",
        operationId: "obtenerDetalleReporte",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Ver detalle completo de un reporte mensual",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del reporte", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Detalle del reporte y lista de actividades"),
            new OA\Response(response: 404, description: "Reporte no encontrado")
        ]
    )]
    public function docReportesShow() {}

    #[OA\Post(
        path: "/api/reportes",
        operationId: "crearReporteMensual",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Crear un nuevo reporte mensual",
        description: "Registra un reporte mensual con sus actividades realizadas y evidencias fotográficas.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["area_pasantia", "nombre_pasante", "mes", "fecha_inicio", "fecha_fin", "objetivo", "actividades"],
                properties: [
                    new OA\Property(property: "area_pasantia", type: "string", example: "Desarrollo Backend"),
                    new OA\Property(property: "nombre_pasante", type: "string", example: "Juanito Rodriguez"),
                    new OA\Property(property: "mes", type: "string", example: "Julio"),
                    new OA\Property(property: "fecha_inicio", type: "string", format: "date", example: "2026-07-01"),
                    new OA\Property(property: "fecha_fin", type: "string", format: "date", example: "2026-07-31"),
                    new OA\Property(property: "objetivo", type: "string", example: "Implementación de endpoints y documentación OpenAPI"),
                    new OA\Property(
                        property: "actividades",
                        type: "array",
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: "numero", type: "integer", example: 1),
                                new OA\Property(property: "actividad", type: "string", example: "Configuración de Swagger en Laravel"),
                                new OA\Property(property: "fecha", type: "string", format: "date", example: "2026-07-04"),
                                new OA\Property(property: "resultado_aprendizaje", type: "string", example: "Dominio de especificación OpenAPI 3.0")
                            ]
                        )
                    ),
                    new OA\Property(property: "evidencias", type: "array", items: new OA\Items(type: "string"))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Reporte mensual creado exitosamente"),
            new OA\Response(response: 422, description: "Error de validación (campos requeridos faltantes)"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function docReportesStore() {}

    #[OA\Put(
        path: "/api/reportes/{id}",
        operationId: "actualizarReporteMensual",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Actualizar un reporte mensual existente",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del reporte", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: "object")),
        responses: [
            new OA\Response(response: 200, description: "Reporte actualizado exitosamente"),
            new OA\Response(response: 404, description: "Reporte no encontrado"),
            new OA\Response(response: 422, description: "Validación fallida")
        ]
    )]
    public function docReportesUpdate() {}

    #[OA\Delete(
        path: "/api/reportes/{id}",
        operationId: "eliminarReporteMensual",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Eliminar un reporte mensual",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del reporte", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Reporte eliminado exitosamente"),
            new OA\Response(response: 404, description: "Reporte no encontrado")
        ]
    )]
    public function docReportesDestroy() {}

    #[OA\Patch(
        path: "/api/reportes/{id}/estado",
        operationId: "cambiarEstadoReporte",
        tags: ["Reportes Mensuales"],
        security: [["bearerAuth" => []]],
        summary: "Cambiar estado de revisión de un reporte",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del reporte", schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["estado"],
                properties: [
                    new OA\Property(property: "estado", type: "string", enum: ["En revisión", "Aprobado", "Rechazado"], example: "Aprobado")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Estado actualizado exitosamente")
        ]
    )]
    public function docReportesCambiarEstado() {}

    // =========================================================================
    // INFORME FINAL
    // =========================================================================

    #[OA\Get(
        path: "/api/informes-finales",
        operationId: "listarInformesFinales",
        tags: ["Informe Final"],
        security: [["bearerAuth" => []]],
        summary: "Listar informes finales registrados",
        responses: [
            new OA\Response(response: 200, description: "Lista de informes finales obtenida exitosamente")
        ]
    )]
    public function docInformeFinalIndex() {}

    #[OA\Get(
        path: "/api/informes-finales/{id}",
        operationId: "obtenerDetalleInformeFinal",
        tags: ["Informe Final"],
        security: [["bearerAuth" => []]],
        summary: "Obtener detalle completo de un informe final",
        description: "Devuelve narrativa, presupuesto matricial y reportes adjuntos.",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del informe final", schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Detalle del informe final con presupuesto y reportes"),
            new OA\Response(response: 404, description: "Informe final no encontrado")
        ]
    )]
    public function docInformeFinalShow() {}

    #[OA\Post(
        path: "/api/informes-finales",
        operationId: "crearInformeFinal",
        tags: ["Informe Final"],
        security: [["bearerAuth" => []]],
        summary: "Crear y enviar el informe final de pasantía",
        description: "Registra el informe final integrando datos de empresa, procedimiento, descripción y presupuesto matricial.",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["nombre_pasante", "institucion", "presentacion", "generalidades", "procedimiento_pasantia", "descripcion_empresa", "descripcion_pasantia"],
                properties: [
                    new OA\Property(property: "nombre_pasante", type: "string", example: "Juanito Rodriguez"),
                    new OA\Property(property: "institucion", type: "string", example: "Empresas CASS"),
                    new OA\Property(property: "presentacion", type: "string", example: "Presentación del informe final de pasantía..."),
                    new OA\Property(property: "generalidades", type: "string", example: "Generalidades de la institución..."),
                    new OA\Property(property: "procedimiento_pasantia", type: "string", example: "Procedimiento metodológico aplicado..."),
                    new OA\Property(property: "descripcion_empresa", type: "string", example: "Empresa de desarrollo de soluciones tecnológicas..."),
                    new OA\Property(property: "descripcion_pasantia", type: "string", example: "Desarrollo de módulos e integraciones backend..."),
                    new OA\Property(property: "presupuesto_total", type: "string", example: "$44.00")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Informe final registrado exitosamente"),
            new OA\Response(response: 422, description: "Faltan campos obligatorios en el informe final")
        ]
    )]
    public function docInformeFinalStore() {}

    // =========================================================================
    // NOTIFICACIONES
    // =========================================================================

    #[OA\Get(
        path: "/api/notificaciones/contador",
        operationId: "conteoNotificacionesNoLeidas",
        tags: ["Notificaciones"],
        security: [["bearerAuth" => []]],
        summary: "Obtener conteo de notificaciones no leídas",
        responses: [
            new OA\Response(
                response: 200,
                description: "Conteo de notificaciones no leídas",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "exito", type: "boolean", example: true),
                        new OA\Property(property: "no_leidas_count", type: "integer", example: 2)
                    ]
                )
            )
        ]
    )]
    public function docNotificacionesContador() {}

    #[OA\Get(
        path: "/api/notificaciones",
        operationId: "listarNotificaciones",
        tags: ["Notificaciones"],
        security: [["bearerAuth" => []]],
        summary: "Listar notificaciones del usuario",
        parameters: [
            new OA\Parameter(name: "estado", in: "query", required: false, description: "'todos' | 'no_leidos' | 'leidos'", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "orden", in: "query", required: false, description: "'recientes' | 'antiguos'", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "tipo", in: "query", required: false, description: "Filtrar por tipo de notificación", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de notificaciones recibidas")
        ]
    )]
    public function docNotificacionesIndex() {}

    #[OA\Patch(
        path: "/api/notificaciones/{id}/leer",
        operationId: "marcarNotificacionLeida",
        tags: ["Notificaciones"],
        security: [["bearerAuth" => []]],
        summary: "Marcar notificación individual como leída",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Notificación marcada como leída")
        ]
    )]
    public function docNotificacionesMarcarLeida() {}

    #[OA\Post(
        path: "/api/notificaciones/marcar-todas-leidas",
        operationId: "marcarTodasNotificacionesLeidas",
        tags: ["Notificaciones"],
        security: [["bearerAuth" => []]],
        summary: "Marcar todas las notificaciones del usuario como leídas",
        responses: [
            new OA\Response(response: 200, description: "Todas las notificaciones marcadas como leídas")
        ]
    )]
    public function docNotificacionesMarcarTodas() {}

    #[OA\Delete(
        path: "/api/notificaciones/{id}",
        operationId: "eliminarNotificacion",
        tags: ["Notificaciones"],
        security: [["bearerAuth" => []]],
        summary: "Eliminar una notificación",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Notificación eliminada")
        ]
    )]
    public function docNotificacionesDestroy() {}

    // =========================================================================
    // HISTORIAL DE PROGRESO
    // =========================================================================

    #[OA\Get(
        path: "/api/historial-progreso",
        operationId: "obtenerHistorialProgreso",
        tags: ["Historial de Progreso"],
        security: [["bearerAuth" => []]],
        summary: "Obtener línea de tiempo y auditoría de eventos de pasantía",
        responses: [
            new OA\Response(response: 200, description: "Línea de tiempo de eventos registrada en base de datos")
        ]
    )]
    public function docHistorialProgresoIndex() {}

    // =========================================================================
    // PASANTÍA
    // =========================================================================

    #[OA\Get(
        path: "/api/pasante/estado",
        operationId: "obtenerEstadoPasante",
        tags: ["Pasantía"],
        summary: "Consulta pública del estado oficial de pasantía",
        responses: [
            new OA\Response(response: 200, description: "Estado oficial de pasantía (horas, porcentaje, fase)")
        ]
    )]
    public function docPasanteEstado() {}

    // =========================================================================
    // PORTAFOLIO
    // =========================================================================

    #[OA\Get(
        path: "/api/portafolio",
        operationId: "obtenerPortafolios",
        tags: ["Portafolio y Perfil"],
        security: [["bearerAuth" => []]],
        summary: "Listar enlaces de portafolio del pasante autenticado",
        responses: [
            new OA\Response(response: 200, description: "Lista de enlaces de portafolio"),
            new OA\Response(response: 401, description: "No autenticado")
        ]
    )]
    public function docPortafolioIndex() {}

    #[OA\Post(
        path: "/api/portafolio",
        operationId: "crearPortafolio",
        tags: ["Portafolio y Perfil"],
        security: [["bearerAuth" => []]],
        summary: "Añadir nuevo enlace de portafolio",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["titulo", "link"],
                properties: [
                    new OA\Property(property: "titulo", type: "string", example: "My portafolio"),
                    new OA\Property(property: "link", type: "string", example: "http://portafolio.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Enlace de portafolio creado exitosamente"),
            new OA\Response(response: 422, description: "Error de validación")
        ]
    )]
    public function docPortafolioStore() {}

    #[OA\Put(
        path: "/api/portafolio/{id}",
        operationId: "actualizarPortafolio",
        tags: ["Portafolio y Perfil"],
        security: [["bearerAuth" => []]],
        summary: "Actualizar un enlace de portafolio existente",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del enlace de portafolio", schema: new OA\Schema(type: "integer", example: 1))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["titulo", "link"],
                properties: [
                    new OA\Property(property: "titulo", type: "string", example: "My portafolio actualizado"),
                    new OA\Property(property: "link", type: "string", example: "https://mi-portafolio.com")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Enlace actualizado exitosamente"),
            new OA\Response(response: 404, description: "Enlace no encontrado")
        ]
    )]
    public function docPortafolioUpdate() {}

    #[OA\Delete(
        path: "/api/portafolio/{id}",
        operationId: "eliminarPortafolio",
        tags: ["Portafolio y Perfil"],
        security: [["bearerAuth" => []]],
        summary: "Eliminar un enlace de portafolio",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "ID del enlace de portafolio", schema: new OA\Schema(type: "integer", example: 1))
        ],
        responses: [
            new OA\Response(response: 200, description: "Enlace eliminado exitosamente"),
            new OA\Response(response: 404, description: "Enlace no encontrado")
        ]
    )]
    public function docPortafolioDestroy() {}
}
