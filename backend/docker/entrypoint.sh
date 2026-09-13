#!/bin/bash
set -e

# 1. Bind Apache to Render dynamic $PORT if specified
PORT="${PORT:-80}"
echo "[Smart Adama] Configuring Apache to listen on port ${PORT}..."
sed -i "s/Listen [0-9]*/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# 2. Ensure runtime storage directories exist
mkdir -p /var/www/html/storage/framework/cache/data
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache

# 3. Ensure proper permissions for web server
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 4. Safe production optimizations if APP_KEY is configured
if [ -n "$APP_KEY" ]; then
    echo "[Smart Adama] Discovering packages and caching configurations..."
    php artisan package:discover --ansi || true
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
else
    echo "[Smart Adama] WARNING: APP_KEY is not set. Skipping config:cache."
fi

# 5. Database safety check
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "[Smart Adama] RUN_MIGRATIONS is set to true. Running safe non-destructive migrations..."
    php artisan migrate --force || true
else
    echo "[Smart Adama] Skipping automatic database migrations (database already managed)."
fi

echo "[Smart Adama] Starting Apache in foreground on port ${PORT}..."
exec "$@"
