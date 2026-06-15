#!/usr/bin/env sh
set -e

cd /app

echo "[start] Waiting for database..."
# Retry DB connection up to 30 times (30s) before giving up
for i in $(seq 1 30); do
    php artisan db:monitor --databases=pgsql --max=1 > /dev/null 2>&1 && break
    echo "[start] DB not ready yet, retrying ($i/30)..."
    sleep 1
done

echo "[start] Running migrations..."
php artisan migrate --force

echo "[start] Caching config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[start] Starting Apache..."
exec apache2-foreground
