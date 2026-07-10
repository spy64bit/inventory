#!/usr/bin/env bash
set -e

cd /var/www/html

# Ensure runtime directories exist (mounted volumes may start empty)
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    /run/nginx

chown -R www-data:www-data storage bootstrap/cache

# Symlink public/storage -> storage/app/public (ignore if it already exists)
php artisan storage:link --force 2>/dev/null || true

# Cache config, routes, views and events for production performance
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# Run migrations on boot (safe to skip by setting RUN_MIGRATIONS=false)
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
