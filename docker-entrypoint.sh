#!/bin/sh
set -e

# Asegurar APP_KEY para evitar error 500
if [ -z "$APP_KEY" ]; then
    echo "Configuring fallback APP_KEY..."
    export APP_KEY="base64:75j3gP3U+O0sV/mXwT5Q6lR98qE4K2Z1+dC9aB3vF8w="
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
    php artisan migrate --force || true
fi

# Iniciar servidor Laravel en el puerto asignado por Render ($PORT)
PORT=${PORT:-8080}
echo "Starting Laravel server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

