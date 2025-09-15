FROM php:8.2-cli

# Paquetes y extensiones
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpq-dev \
 && docker-php-ext-install zip pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Permisos de Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Dependencias PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Opcional: OPcache para mejor performance
# RUN docker-php-ext-install opcache

ENV PORT=10000
EXPOSE 10000

# Arranque: preparar app y servir (sin cachear en build)
CMD bash -lc '\
  php artisan key:generate --force || true && \
  php artisan storage:link || true && \
  php artisan migrate --force || true && \
  php artisan config:clear && php artisan config:cache && \
  php artisan route:cache && php artisan view:cache && \
  php artisan serve --host=0.0.0.0 --port=${PORT:-10000} \
'
