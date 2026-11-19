#!/bin/sh
set -e

# Validar que APP_KEY este configurada como variable de entorno en Render
if [ -z "$APP_KEY" ]; then
    echo "ERROR FATAL: APP_KEY no esta configurada como variable de entorno." >&2
    echo "Configurala en Render: Dashboard -> Genesis-Profesional -> Environment -> APP_KEY" >&2
    exit 1
fi

# Validar JWT_SECRET si se usa autenticacion JWT
if [ -z "$JWT_SECRET" ]; then
    echo "ADVERTENCIA: JWT_SECRET no esta configurada. Si usas tokens JWT, configura esta variable en Render." >&2
fi

# Asignar DATABASE_URL a DB_URL si está presente en Render
if [ -n "$DATABASE_URL" ] && [ -z "$DB_URL" ]; then
    export DB_URL="$DATABASE_URL"
fi

if [ -z "$DB_CONNECTION" ]; then
    export DB_CONNECTION=pgsql
fi

# Cachear configuración de Laravel
echo "Caching Laravel configuration..."
php artisan config:clear || true
php artisan route:clear || true

# Ejecutar migraciones automáticas si hay conexión configurada
if [ -n "$DB_URL" ] || [ -n "$DATABASE_URL" ] || [ -n "$DB_HOST" ]; then
    echo "Running database migrations..."
    if ! php artisan migrate --force; then
        echo "ADVERTENCIA: Las migraciones fallaron - revisar logs de Render." >&2
    fi
fi

# Iniciar servidor Laravel en el puerto asignado por Render ($PORT)
php artisan l5-swagger:generate || true
PORT=${PORT:-8080}
echo "Starting Laravel server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

