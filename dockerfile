FROM php:8.2-cli

# Paquetes del sistema y extensiones PHP
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev \
 && docker-php-ext-install zip pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Directorio de trabajo
WORKDIR /var/www

# Copia de archivos
COPY . .

# Permisos
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Dependencias PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Railway inyecta PORT; por compatibilidad local usa 8080 por defecto
ENV PORT=8080

# Exponer (opcional, Railway detecta PORT igualmente)
EXPOSE 8080

# Arranque: preparar app y servir
# - no usamos "config:cache" en build; aquí sí porque ya hay env vars
CMD bash -lc '\
  php artisan key:generate --force || true && \
  php artisan migrate --force || true && \
  php artisan storage:link || true && \
  php artisan config:clear && php artisan config:cache && \
  php artisan route:cache && php artisan view:cache && \
  php artisan serve --host=0.0.0.0 --port=${PORT:-8080} \
'
