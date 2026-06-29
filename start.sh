#!/usr/bin/env sh
set -eu

cd /app

echo "[start] PHP version: $(php -r 'echo PHP_VERSION;')"
echo "[start] Laravel version: $(php artisan --version)"

# Ensure required directories exist
mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/app/private/reports \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

chmod -R 775 storage bootstrap/cache || true

# -------------------------------
# Database connection
# -------------------------------

if [ -n "${DATABASE_URL:-}" ]; then
    export DB_CONNECTION=pgsql
    export DB_URL="$DATABASE_URL"

    PG_HOST=$(echo "$DATABASE_URL" | sed -E 's|.*@([^:/]+).*|\1|')
    PG_PORT=$(echo "$DATABASE_URL" | sed -E 's|.*:([0-9]+)/.*|\1|')
    PG_PORT=${PG_PORT:-5432}
    PG_USER=$(echo "$DATABASE_URL" | sed -E 's|.*://([^:]+):.*|\1|')
    PG_PASS=$(echo "$DATABASE_URL" | sed -E 's|.*://[^:]+:([^@]+)@.*|\1|')
    PG_DB=$(echo "$DATABASE_URL" | sed -E 's|.*/([^?]+).*|\1|')
else
    PG_HOST="${DB_HOST}"
    PG_PORT="${DB_PORT:-5432}"
    PG_USER="${DB_USERNAME}"
    PG_PASS="${DB_PASSWORD}"
    PG_DB="${DB_DATABASE}"
fi

echo "[start] Connecting to PostgreSQL at ${PG_HOST}:${PG_PORT}"

# -------------------------------
# Wait for PostgreSQL
# -------------------------------

for i in $(seq 1 30); do
    php -r "
        try {
            new PDO(
                'pgsql:host=${PG_HOST};port=${PG_PORT};dbname=${PG_DB};sslmode=require',
                '${PG_USER}',
                '${PG_PASS}'
            );
            exit(0);
        } catch (Throwable \$e) {
            fwrite(STDERR,\$e->getMessage().PHP_EOL);
            exit(1);
        }
    " && break

    echo "[start] Waiting for PostgreSQL... ($i/30)"
    sleep 2

    if [ "$i" = "30" ]; then
        echo "[start] ERROR: Database connection failed."
        exit 1
    fi
done

echo "[start] Database connected."

# -------------------------------
# APP_KEY
# -------------------------------

if [ -z "${APP_KEY:-}" ]; then
    echo "[start] APP_KEY is missing."
    exit 1
fi

# -------------------------------
# Laravel
# -------------------------------

echo "[start] Clearing caches..."

php artisan optimize:clear || true

echo "[start] Running migrations..."

php artisan migrate --force

echo "[start] Creating storage link..."

rm -rf public/storage

php artisan storage:link || true

echo "[start] Caching configuration..."

php artisan config:cache

echo "[start] Caching views..."

php artisan view:cache

echo "[start] Caching routes..."

php artisan route:cache || true

echo "[start] Optimizing..."

php artisan optimize

# -------------------------------
# Apache
# -------------------------------

APP_PORT="${PORT:-80}"

echo "[start] Listening on port ${APP_PORT}"

sed -i "s/__PORT__/${APP_PORT}/g" \
    /etc/apache2/ports.conf \
    /etc/apache2/sites-available/000-default.conf

exec apache2-foreground