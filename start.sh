#!/usr/bin/env sh
set -e

cd /app

# Parse DB host/port from DATABASE_URL or fall back to individual vars
# DATABASE_URL format: postgresql://user:pass@host:port/dbname
if [ -n "$DATABASE_URL" ]; then
    DB_HOST_PARSED=$(echo "$DATABASE_URL" | sed -E 's|.*@([^:/]+).*|\1|')
    DB_PORT_PARSED=$(echo "$DATABASE_URL" | sed -E 's|.*:([0-9]+)/.*|\1|')
    PG_HOST="${DB_HOST_PARSED}"
    PG_PORT="${DB_PORT_PARSED:-5432}"
else
    PG_HOST="${DB_HOST:-127.0.0.1}"
    PG_PORT="${DB_PORT:-5432}"
fi

echo "[start] Waiting for PostgreSQL at ${PG_HOST}:${PG_PORT}..."
for i in $(seq 1 30); do
    if php -r "new PDO('pgsql:host=${PG_HOST};port=${PG_PORT};dbname=${DB_DATABASE:-railway}', '${DB_USERNAME:-postgres}', '${DB_PASSWORD:-}');" 2>/dev/null; then
        echo "[start] Database is ready."
        break
    fi
    echo "[start] DB not ready yet, retrying ($i/30)..."
    sleep 1
done

echo "[start] Running migrations..."
php artisan migrate --force

echo "[start] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[start] Starting Apache..."
exec apache2-foreground
