#!/bin/sh
set -e

echo "[entrypoint] Waiting for database..."
until mysql -h"${DB_HOST:-db}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD}" "${DB_DATABASE}" -e "SELECT 1" >/dev/null 2>&1; do
  sleep 1
done
echo "[entrypoint] Database is ready."

echo "[entrypoint] Running migrations..."
php artisan migrate --force

echo "[entrypoint] Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] Starting PHP-FPM..."
exec php-fpm
