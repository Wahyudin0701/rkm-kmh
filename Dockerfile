# =========================================================================
# STAGE 1: Frontend Asset Compilation via Vite & Node.js
# =========================================================================
FROM node:22-alpine AS frontend-builder

WORKDIR /app

# Copy dependency manifests
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm ci || npm install

# Copy source assets needed for compilation
COPY vite.config.js ./
COPY resources/ ./resources/

# Compile production assets into /app/public/build
RUN npm run build

# =========================================================================
# STAGE 2: PHP 8.2-FPM Alpine Production Runtime
# =========================================================================
FROM php:8.2-fpm-alpine AS app

# Set working directory
WORKDIR /var/www

# Install system dependencies & build tools
RUN apk add --no-cache \
    curl \
    netcat-openbsd \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    mysql-client

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy PHP & OPcache custom configs
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy application source code
COPY . /var/www

# Copy compiled frontend assets from Stage 1 (Overrides public/build)
COPY --from=frontend-builder /app/public/build /var/www/public/build

# Install Composer production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set correct permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Expose FastCGI port
EXPOSE 9000

# Set entrypoint
ENTRYPOINT ["docker-entrypoint.sh"]

# Start PHP-FPM
CMD ["php-fpm"]
