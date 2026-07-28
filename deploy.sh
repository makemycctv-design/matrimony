#!/usr/bin/env bash
#
# Zero-downtime-ish deploy helper for cPanel / shared hosting or a VPS.
# Run from the project root after pulling the latest code:
#
#   bash deploy.sh
#
# It installs dependencies, runs migrations, links storage, and rebuilds the
# framework caches. Frontend assets should be built (npm run build) either on
# the server (if Node is available) or locally and committed/uploaded.

set -euo pipefail

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
    echo "==> No .env found — copying .env.example (edit it before continuing!)"
    cp .env.example .env
    php artisan key:generate
fi

echo "==> Building frontend assets (skip if built locally)"
if command -v npm >/dev/null 2>&1; then
    npm ci
    npm run build
else
    echo "    npm not found — ensure public/build was uploaded."
fi

echo "==> Migrating database"
php artisan migrate --force

echo "==> Linking storage"
php artisan storage:link || true

echo "==> Caching config, routes and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

echo "==> Done. Remember to set the scheduler cron and (optionally) a queue worker."
