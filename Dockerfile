# Production Dockerfile for Render (also works locally with `docker build`).
# Runs nginx + php-fpm under supervisord. Listens on $PORT (Render) or 8080.
#
# Build:   docker build -t elsar5ateam .
# Run:     docker run --rm -p 8080:8080 -e PORT=8080 -e APP_KEY=... -e DB_URL=... elsar5ateam
FROM php:8.2-fpm

# -----------------------------------------------------------------------------
# System packages: nginx (web), supervisor (process manager), and the libs
# required to compile the PHP extensions used by the app.
# -----------------------------------------------------------------------------
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        gettext-base \
        ca-certificates \
        curl \
        git \
        unzip \
        zip \
        libzip-dev \
        libpq-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
 && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------------------------------
# PHP extensions: gd (QR codes / images), pdo_pgsql (Neon), zip / bcmath /
# intl / opcache (perf + compatibility), redis (kept because composer.json
# declares ext-redis as a platform requirement).
# -----------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" \
        gd \
        zip \
        pdo \
        pdo_pgsql \
        bcmath \
        intl \
        opcache \
 && pecl install redis \
 && docker-php-ext-enable redis

# -----------------------------------------------------------------------------
# Composer
# -----------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# -----------------------------------------------------------------------------
# App
# -----------------------------------------------------------------------------
WORKDIR /app

# Copy composer files first so the dependency layer caches across rebuilds.
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# Copy the rest of the app and finish autoload generation now that all files exist.
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
 && php artisan package:discover --ansi \
 && mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# -----------------------------------------------------------------------------
# nginx + php-fpm + supervisord configuration
# -----------------------------------------------------------------------------
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/php-fpm.pool.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
 && rm -f /etc/nginx/sites-enabled/default \
 && mkdir -p /var/log/supervisor /var/run

# Render injects the port via $PORT. Default to 8080 for local runs.
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
