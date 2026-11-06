# Directrices de Seguridad: Almacenamiento, Contenedor Docker y Servidor Web

Este documento detalla las directrices operativas y de seguridad para el despliegue del proyecto `Genesis-Profesional` en entornos de contenedorización y producción (Render / Docker).

---

## 1. Prohibición de `php artisan storage:link` con Documentos en Disco Público

> [!CAUTION]
> **Prohibido ejecutar `php artisan storage:link` en entornos de producción mientras existan documentos sensibles (CVs, reportes, anexos con datos personales) bajo `storage/app/public`.**

### Justificación Técnica
1. El enlace simbólico creado por `php artisan storage:link` expone la totalidad del árbol `storage/app/public` directamente bajo el web root (`public/storage`).
2. Cualquier servidor web estándar (o el built-in server de PHP) sirve archivos estáticos antes de pasar el control al ciclo de vida de Laravel.
3. Si el enlace simbólico existe, un atacante puede descargar directamente cualquier PDF sensible (`/storage/cvs/1/...pdf`) saltándose cualquier capa de middleware, autenticación JWT o autorización Policy.
4. Por tanto, mientras no se complete la migración a un disco privado (`storage/app/private`), el acceso a los archivos debe ser mediado exclusivamente a través de la API saneada (`SafeFileResolver`), manteniendo `public/storage` aislado en `.dockerignore`.

---

## 2. Diagnóstico del Servidor Web en el Contenedor de Producción

Actualmente, el contenedor de producción configurado en [`Dockerfile`](file:///c:/Users/josec/OneDrive/Escritorio/Develop/Genesis-Profesional/Dockerfile) y [`docker-entrypoint.sh`](file:///c:/Users/josec/OneDrive/Escritorio/Develop/Genesis-Profesional/docker-entrypoint.sh):
- Utiliza la imagen base `php:8.2-cli`.
- **Ciclo de Vida de PHP 8.2:** La versión PHP 8.2 finaliza su soporte oficial de seguridad el **31/12/2026**. Se debe planificar la migración a PHP 8.3 o 8.4 antes de dicha fecha de fin de ciclo.
- Inicia la aplicación mediante:
  ```bash
  exec php artisan serve --host=0.0.0.0 --port=$PORT
  ```
- **Riesgos y Limitaciones del CLI Server en Producción:**
  - `php artisan serve` (servidor embebido de PHP) es **monohilo** y de desarrollo; no fue diseñado para tráfico concurrente ni resistencia ante denegación de servicio (DoS).
  - No gestiona pool de workers, reinicios de procesos zombi ni buffers de salida optimizados.

---

## 3. Propuesta de Arquitectura Industrial: Nginx + PHP-FPM

Para un entorno de producción robusto y de alto rendimiento, se propone estructurar la imagen Docker (o un pod multi-contenedor) con **Nginx + PHP-FPM**:

### Componentes de la Arquitectura
1. **PHP-FPM (`php:8.2-fpm`):**
   - Pool de procesos `www-data` con gestión dinámica (`pm = dynamic`, `pm.max_children = 50`).
   - OpCache pre-cargado para ejecución acelerada del framework.
   - Aislamiento de permisos de ejecución sin privilegios de root.
2. **Nginx como Reverse Proxy y Web Server Frontal:**
   - Terminación TLS / SSL y control de buffers HTTP.
   - Cabeceras globales de seguridad inyectadas en todas las respuestas:
     - `X-Content-Type-Options: nosniff;`
     - `X-Frame-Options: SAMEORIGIN;`
     - `Strict-Transport-Security: max-age=31536000; includeSubDomains;`
   - Bloqueo a nivel de servidor de archivos ocultos y rutas sensibles:
     ```nginx
     location ~ /\.(?!well-known).* {
         deny all;
     }
     location ~* ^/(api/)?storage/(cvs|informes|informes_finales|reportes) {
         deny all;
     }
     ```
   - Delegación de todo el tráfico dinámico a PHP-FPM mediante FastCGI:
     ```nginx
     location ~ \.php$ {
         fastcgi_pass 127.0.0.1:9000;
         fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
         include fastcgi_params;
     }
     ```

