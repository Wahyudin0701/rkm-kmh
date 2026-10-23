#!/bin/sh
set -e

echo "Starting RKM Laravel Application Container..."

# Wait for MySQL to be ready if DB_CONNECTION is mysql
if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for MySQL database at $DB_HOST:$DB_PORT..."
    while ! nc -z "$DB_HOST" "$DB_PORT"; do
        sleep 1
    done
    echo "MySQL database is reachable!"
fi

# Ensure storage directories exist and have proper permissions
rm -f /var/www/public/hot
mkdir -p /var/www/storage/framework/cache/data \
         /var/www/storage/framework/sessions \
         /var/www/storage/framework/views \
         /var/www/storage/logs \
         /var/www/bootstrap/cache

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Run database migrations
echo "Executing database migrations..."
php artisan migrate --force

# Seed database if explicitly requested
if [ "$RUN_SEEDER" = "true" ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

# Ensure symbolic link for storage
php artisan storage:link || true

# Production optimization caches
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration, routes, and views for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
else
    echo "Clearing caches for non-production environment..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

echo "RKM Laravel is ready! Starting PHP-FPM..."
exec "$@"
