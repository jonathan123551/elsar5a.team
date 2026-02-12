FROM php:8.2-cli

# =========================
# System dependencies
# =========================
RUN apt-get update && apt-get install -y \
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
# Redis extension (optional)
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
# Install dependencies
# =========================
RUN composer install --no-dev --optimize-autoloader

# =========================
# FIX PERMISSIONS
# =========================
RUN chmod -R 775 storage bootstrap/cache

# =========================
# 🔥 CLEAR ALL CACHES
# =========================
RUN php artisan config:clear \
 && php artisan cache:clear \
 && php artisan route:clear \
 && php artisan view:clear

# =========================
# Expose Port
# =========================
EXPOSE 8080

# =========================
# Start Laravel
# =========================
CMD php artisan serve --host=0.0.0.0 --port=8080
