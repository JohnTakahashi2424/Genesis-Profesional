# Justificaciones Técnicas de Falsos Positivos — Auditoría Kiuwan SAST (Análisis #15)

Este documento contiene el inventario formal y las justificaciones técnicas para silenciar los **34 falsos positivos** identificados en el análisis estático de Kiuwan sobre el repositorio `Genesis-Profesional`. Cada entrada describe la ubicación exacta, la evidencia sintáctica y el argumento técnico para el equipo de auditoría/seguridad.

---

## 1. Resumen por Regla de Falsos Positivos

| Regla Kiuwan | Prioridad | CWE | Total Hallazgos | Falsos Positivos | Justificación Principal |
|---|:---:|:---:|:---:|:---:|---|
| **R2: Avoid unused local variable (JS/TS)** | Alta | CWE-563 | 26 | **23** | Variables locales utilizadas dentro de interpolaciones de template literals (`` `${...}` ``) no resueltas por el analizador léxico de Kiuwan. |
| **R4: Use of sensitive information into configuration file** | Normal | CWE-200 | 6 | **6** | Metadatos y definiciones de tipos de esquema OpenAPI/Swagger (`"format": "password"`, `"token JWT"`). No son credenciales ni secretos en configuración. |
| **R5: Variable initialization (PHP)** | Normal | CWE-457 | 5 | **5** | Parámetros formales inyectados por el contenedor de Laravel en callbacks/closures de configuración y enrutamiento, no variables locales sin inicializar. |
| **TOTAL** | | | **37** | **34** | *(Los 3 hallazgos restantes de R2 corresponden a código muerto real que se remueve en la etapa de refactorización).* |

---

## 2. Inventario Detallado de Justificaciones para Silenciar en Kiuwan

| # | Archivo:Línea | Regla Kiuwan | CWE | Evidencia en Código | Justificación Técnica para Silenciar en Kiuwan |
|---|---|---|:---:|---|---|
| **FP-01** | `src/components/HistorialModulo.vue:354` | Avoid unused local variable | CWE-563 | `const dia = ...` | Variable utilizada en la interpolación `` `${dia}/${mes}/${anio} ${horasStr}:${minutos}:${segundos} ${ampm}` `` (línea 364). Falso positivo por parser de template literal. |
| **FP-02** | `src/components/HistorialModulo.vue:355` | Avoid unused local variable | CWE-563 | `const mes = ...` | Variable utilizada en la interpolación `` `${dia}/${mes}/${anio} ${horasStr}:${minutos}:${segundos} ${ampm}` `` (línea 364). Falso positivo por parser de template literal. |
| **FP-03** | `src/components/HistorialModulo.vue:356` | Avoid unused local variable | CWE-563 | `const anio = ...` | Variable utilizada en la interpolación `` `${dia}/${mes}/${anio} ${horasStr}:${minutos}:${segundos} ${ampm}` `` (línea 364). Falso positivo por parser de template literal. |
| **FP-04** | `src/components/HistorialModulo.vue:360` | Avoid unused local variable | CWE-563 | `const minutos = ...` | Variable utilizada en la interpolación de formateo horario en línea 364. Falso positivo de análisis estático. |
| **FP-05** | `src/components/HistorialModulo.vue:361` | Avoid unused local variable | CWE-563 | `const segundos = ...` | Variable utilizada en la interpolación de formateo horario en línea 364. Falso positivo de análisis estático. |
| **FP-06** | `src/components/HistorialModulo.vue:362` | Avoid unused local variable | CWE-563 | `const ampm = ...` | Variable utilizada en la interpolación de formateo horario en línea 364. Falso positivo de análisis estático. |
| **FP-07** | `src/components/NotificacionesModulo.vue:40` | Avoid unused local variable | CWE-563 | `const horasStr = ...` | Variable utilizada en la interpolación de hora formateada en template literal (línea 43). Falso positivo por interpolación ES6. |
| **FP-08** | `src/components/NotificacionesModulo.vue:41` | Avoid unused local variable | CWE-563 | `const minutosStr = ...` | Variable utilizada en la interpolación de hora formateada en template literal (línea 43). Falso positivo por interpolación ES6. |
| **FP-09** | `src/components/NotificacionesModulo.vue:42` | Avoid unused local variable | CWE-563 | `const ampm = ...` | Variable utilizada en la interpolación de hora formateada en template literal (línea 43). Falso positivo por interpolación ES6. |
| **FP-10** | `src/components/NotificacionesModulo.vue:44` | Avoid unused local variable | CWE-563 | `const diaStr = ...` | Variable utilizada en la interpolación de fecha formateada en template literal (línea 47). Falso positivo por interpolación ES6. |
| **FP-11** | `src/components/NotificacionesModulo.vue:45` | Avoid unused local variable | CWE-563 | `const mesStr = ...` | Variable utilizada en la interpolación de fecha formateada en template literal (línea 47). Falso positivo por interpolación ES6. |
| **FP-12** | `src/components/NotificacionesModulo.vue:46` | Avoid unused local variable | CWE-563 | `const anioStr = ...` | Variable utilizada en la interpolación de fecha formateada en template literal (línea 47). Falso positivo por interpolación ES6. |
| **FP-13** | `src/components/NotificacionesModulo.vue:49` | Avoid unused local variable | CWE-563 | `const n = ...` | Variable utilizada en la expresión de cálculo de tiempo transcurrido en línea 50. Falso positivo de alcance léxico. |
| **FP-14** | `src/components/NotificacionesModulo.vue:273` | Avoid unused local variable | CWE-563 | `const primerMes = ...` | Variable evaluada y referenciada en la construcción dinámica del rango de periodos (líneas 275-276). Falso positivo. |
| **FP-15** | `src/components/NotificacionesModulo.vue:274` | Avoid unused local variable | CWE-563 | `const ultimoMes = ...` | Variable evaluada y referenciada en la construcción dinámica del rango de periodos (líneas 275-276). Falso positivo. |
| **FP-16** | `src/components/RecuperarPasswordModal.vue:82` | Avoid unused local variable | CWE-563 | `const minutos = ...` | Variable utilizada en la interpolación del contador regresivo `` `${minutos}:${segundos}` `` (línea 84). Falso positivo de template literal. |
| **FP-17** | `src/components/RecuperarPasswordModal.vue:83` | Avoid unused local variable | CWE-563 | `const segundos = ...` | Variable utilizada en la interpolación del contador regresivo `` `${minutos}:${segundos}` `` (línea 84). Falso positivo de template literal. |
| **FP-18** | `src/components/RecuperarPasswordModal.vue:195` | Avoid unused local variable | CWE-563 | `const mensaje = ...` | Variable asignada como mensaje de feedback y referenciada en el log/estado reactivo de notificación. Falso positivo. |
| **FP-19** | `src/components/InformeFinalDetalle.vue:66` | Avoid unused local variable | CWE-563 | `const dia = ...` | Variable utilizada en la interpolación de fecha formateada `` `${dia} de ${mes}...` `` (línea 69). Falso positivo de template literal. |
| **FP-20** | `src/components/InformeFinalDetalle.vue:67` | Avoid unused local variable | CWE-563 | `const mes = ...` | Variable utilizada en la interpolación de fecha formateada `` `${dia} de ${mes}...` `` (línea 69). Falso positivo de template literal. |
| **FP-21** | `src/components/PasantePanel.vue:40` | Avoid unused local variable | CWE-563 | `const n = ...` | Variable utilizada para normalización de nombre de usuario en la interpolación reactiva de saludo (línea 42). Falso positivo. |
| **FP-22** | `src/components/PasantePanel.vue:41` | Avoid unused local variable | CWE-563 | `const a = ...` | Variable utilizada para normalización de apellido de usuario en la interpolación reactiva de saludo (línea 42). Falso positivo. |
| **FP-23** | `src/components/PerfilModulo.vue:81` | Avoid unused local variable | CWE-563 | `const userId = ...` | Variable utilizada en la llamada a la API de carga de perfil y parámetros de consulta en línea 83. Falso positivo de alcance. |
| **FP-24** | `public/api-docs.json:32` | Sensitive info into config file | CWE-200 | `"summary": "Iniciar sesión y obtener token JWT"` | Texto descriptivo de la operación de login en especificación OpenAPI 3.0. No expone tokens ni credenciales secretas. |
| **FP-25** | `public/api-docs.json:53` | Sensitive info into config file | CWE-200 | `"format": "password"` | Metadato estándar de OpenAPI / JSON Schema para indicar que el campo del formulario debe enmascararse en la UI. No contiene contraseñas. |
| **FP-26** | `public/api-docs.json:117` | Sensitive info into config file | CWE-200 | `"password_confirmation"` | Nombre de propiedad en la lista `required` del esquema Swagger para validación de entrada. No almacena valores confidenciales. |
| **FP-27** | `storage/api-docs/api-docs.json:32` | Sensitive info into config file | CWE-200 | `"summary": "Iniciar sesión y obtener token JWT"` | Idéntico a FP-24 en la copia de almacenamiento compilada por `l5-swagger`. Texto de resumen OpenAPI, no secreto. |
| **FP-28** | `storage/api-docs/api-docs.json:53` | Sensitive info into config file | CWE-200 | `"format": "password"` | Idéntico a FP-25 en la copia de almacenamiento compilada por `l5-swagger`. Especificador de tipo OpenAPI, no contraseña. |
| **FP-29** | `storage/api-docs/api-docs.json:117` | Sensitive info into config file | CWE-200 | `"password_confirmation"` | Idéntico a FP-26 en la copia de almacenamiento compilada por `l5-swagger`. Definición de esquema para confirmación de contraseña, no dato real. |
| **FP-30** | `bootstrap/app.php:14` | Variable initialization | CWE-457 | `function (Middleware $middleware)` | Parámetro formal de closure de configuración de middleware inyectado por el framework Laravel 12. No requiere inicialización previa. |
| **FP-31** | `bootstrap/app.php:22` | Variable initialization | CWE-457 | `function (Exceptions $exceptions)` | Parámetro formal de closure de configuración de excepciones inyectado por el framework Laravel 12. No requiere inicialización previa. |
| **FP-32** | `routes/api.php:117` | Variable initialization | CWE-457 | `Route::get('/storage/{path}', function ($path)` | Parámetro de ruta URL inyectado automáticamente por el enrutador de Laravel al resolver el callback. No es variable no inicializada. |
| **FP-33** | `routes/web.php:66` | Variable initialization | CWE-457 | `Route::get('/assets/{file}', function ($file)` | Parámetro de ruta URL inyectado automáticamente por el enrutador de Laravel al resolver el callback de assets. |
| **FP-34** | `routes/web.php:86` | Variable initialization | CWE-457 | `Route::get('/storage/{path}', function ($path)` | Parámetro de ruta URL inyectado automáticamente por el enrutador de Laravel al resolver el callback de storage. |

