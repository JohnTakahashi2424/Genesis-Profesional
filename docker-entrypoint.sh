#!/bin/sh
set -e

# Cachear configuración si existe APP_KEY
if [ -n "$APP_KEY" ]; then
    echo "Caching Laravel configuration..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Ejecutar migraciones y seeders automáticos
if [ -n "$DB_URL" ] || [ -n "$DB_HOST" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
    php artisan db:seed --class=EstudianteSeeder --force || true
fi

# Iniciar servidor Laravel en el puerto asignado por Render ($PORT)
PORT=${PORT:-8080}
echo "Starting Laravel server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT

