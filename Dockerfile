FROM php:8.2-fpm

# =========================
# System dependencies
# =========================
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    git unzip zip \
    libzip-dev \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

# =========================
# PHP extensions
# =========================
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip pdo pdo_pgsql

# =========================
# Redis extension
# =========================
RUN pecl install redis && docker-php-ext-enable redis

# =========================
# Composer
# =========================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================
# App
# =========================
WORKDIR /app
COPY . .

# =========================
# 🔥 PHP upload limits (FIX connection lost)
# =========================
COPY php.ini /usr/local/etc/php/conf.d/uploads.ini

# =========================
# Install dependencies
# =========================
RUN composer install --no-dev --optimize-autoloader

# =========================
# 🔥 FIX PERMISSIONS
# =========================
RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# =========================
# Nginx config
# =========================
RUN rm -f /etc/nginx/sites-enabled/default
COPY nginx.conf /etc/nginx/conf.d/default.conf

# =========================
# Supervisor config
# =========================
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-n"]
