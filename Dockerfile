FROM php:8.4-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    nodejs \
    npm \
    icu-dev

RUN docker-php-ext-install pdo pdo_pgsql zip gd intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Crear estructura de directorios para caché
RUN mkdir -p storage/app \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Establecer permisos correctos
RUN chmod -R 777 storage bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache

RUN rm -f public/storage

RUN composer install --optimize-autoloader --no-dev

RUN npm install

RUN npm run build

EXPOSE 8081

# Modificar script de inicio para asegurar los directorios antes de cada comando
RUN echo '#!/bin/sh' > /var/www/html/start.sh && \
    echo 'mkdir -p storage/app storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache' >> /var/www/html/start.sh && \
    echo 'chmod -R 777 storage bootstrap/cache' >> /var/www/html/start.sh && \
    echo 'php artisan storage:link' >> /var/www/html/start.sh && \
    echo 'php artisan optimize:clear' >> /var/www/html/start.sh && \
    echo 'php artisan config:clear' >> /var/www/html/start.sh && \
    echo 'php artisan migrate --force' >> /var/www/html/start.sh && \
    echo 'php artisan db:seed --force' >> /var/www/html/start.sh && \
    echo 'php artisan shield:generate --all' >> /var/www/html/start.sh && \
    echo 'php artisan shield:super-admin --user=1' >> /var/www/html/start.sh && \
    echo 'php artisan serve --host=0.0.0.0 --port=8081' >> /var/www/html/start.sh

RUN chmod +x /var/www/html/start.sh

CMD ["/var/www/html/start.sh"]