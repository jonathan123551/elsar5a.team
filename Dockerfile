# =========================================================
# Production image: nginx + php-fpm under supervisord.
#
# Replaces the previous `php artisan serve` (single-threaded
# development server) entrypoint, which couldn't gzip, couldn't
# add cache headers, and serialized every request behind one
# PHP process. Everything below is infra-only — no application
# code is changed by this image.
# =========================================================
FROM php:8.2-fpm

# ---------------------------------------------------------
# System dependencies
# ---------------------------------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip \
        nginx \
        supervisor \
        gettext-base \
        libzip-dev \
        libpq-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# ---------------------------------------------------------
# PHP extensions
# ---------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd zip pdo pdo_pgsql opcache

# ---------------------------------------------------------
# Redis extension (optional — used when REDIS_* env is set)
# ---------------------------------------------------------
RUN pecl install redis && docker-php-ext-enable redis

# ---------------------------------------------------------
# Composer
# ---------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------
# Runtime config (nginx vhost template, supervisord, php.ini)
# ---------------------------------------------------------
# Remove the stock nginx default site so our vhost is the only one.
RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/conf.d/default.conf

COPY nginx.conf        /etc/nginx/conf.d/default.conf.template
COPY supervisord.conf  /etc/supervisor/conf.d/supervisord.conf
COPY php.ini           /usr/local/etc/php/conf.d/zz-app.ini

# ---------------------------------------------------------
# App
# ---------------------------------------------------------
WORKDIR /app
COPY . .

# Install PHP deps with the production-grade autoloader. `--no-scripts`
# skips post-install artisan hooks during build (we don't have a runtime
# env yet); they're rerun by docker-entrypoint.sh at boot.
RUN composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --classmap-authoritative \
    && composer clear-cache

# ---------------------------------------------------------
# Permissions
# ---------------------------------------------------------
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# ---------------------------------------------------------
# Entrypoint
# ---------------------------------------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8080
CMD ["/usr/local/bin/docker-entrypoint.sh"]
