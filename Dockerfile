FROM php:8.2-cli

# Instalar dependencias del sistema y extensiones de PHP necesarias
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Aumentar límites de PHP para payloads grandes (CV PDF base64)
RUN echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_max_filesize = 50M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

# Copiar primero archivos de definición de dependencias para optimizar la caché de capas
COPY composer.json composer.lock ./

# Instalar dependencias de PHP con Composer antes de copiar el código fuente completo
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copiar archivos del proyecto de forma controlada
COPY . .

# Permisos para storage y cache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# Copiar script de inicio
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www-data

EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
