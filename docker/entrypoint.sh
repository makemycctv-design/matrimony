#!/bin/sh
set -e

cd /var/www/html

# Ensure an app key exists.
if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
    php artisan key:generate --force || true
fi

# Wait briefly for the database, then migrate + optimize.
php artisan migrate --force || true
php artisan storage:link || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
