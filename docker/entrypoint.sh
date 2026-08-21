#!/bin/sh
set -e
cd /app

echo "[entrypoint] waiting for database and running migrations..."
tries=0
until php artisan migrate --force --no-interaction; do
    tries=$((tries + 1))
    if [ "$tries" -ge 20 ]; then
        echo "[entrypoint] migrations still failing after $tries attempts, giving up" >&2
        exit 1
    fi
    echo "[entrypoint] database not ready (attempt $tries), retrying in 3s..."
    sleep 3
done

echo "[entrypoint] warming Laravel caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] starting supervisord (php-fpm, nginx, scheduler)"
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
