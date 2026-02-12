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
# Fix permissions
# =========================
RUN chmod -R 775 storage bootstrap/cache

# =========================
# Expose port
# =========================
EXPOSE 8080

# =========================
# Start Laravel
# =========================
CMD php artisan serve --host=0.0.0.0 --port=8080
