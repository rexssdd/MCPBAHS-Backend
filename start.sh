#!/usr/bin/env sh
set -e

cd /app

echo "[start] PHP version: $(php -r 'echo PHP_VERSION;')"
echo "[start] Laravel version: $(php artisan --version 2>&1 | head -1)"

# ── Parse DB connection details from DATABASE_URL or individual vars ──────────
# DATABASE_URL format: postgresql://user:pass@host:port/dbname
if [ -n "$DATABASE_URL" ]; then
    PG_HOST=$(echo "$DATABASE_URL" | sed -E 's|.*@([^:/]+).*|\1|')
    PG_PORT=$(echo "$DATABASE_URL" | sed -E 's|.*:([0-9]+)/.*|\1|')
    PG_PORT="${PG_PORT:-5432}"
    PG_USER=$(echo "$DATABASE_URL" | sed -E 's|.*://([^:]+):.*|\1|')
    PG_PASS=$(echo "$DATABASE_URL" | sed -E 's|.*://[^:]+:([^@]+)@.*|\1|')
    PG_DB=$(echo   "$DATABASE_URL" | sed -E 's|.*/([^?]+).*|\1|')
else
    PG_HOST="${DB_HOST:-127.0.0.1}"
    PG_PORT="${DB_PORT:-5432}"
    PG_USER="${DB_USERNAME:-postgres}"
    PG_PASS="${DB_PASSWORD:-}"
    PG_DB="${DB_DATABASE:-railway}"
fi

echo "[start] Connecting to PostgreSQL at ${PG_HOST}:${PG_PORT} db=${PG_DB} user=${PG_USER}"

# ── Wait for PostgreSQL ───────────────────────────────────────────────────────
CONNECTED=0
for i in $(seq 1 30); do
    if php -r "
        try {
            \$pdo = new PDO('pgsql:host=${PG_HOST};port=${PG_PORT};dbname=${PG_DB}', '${PG_USER}', '${PG_PASS}');
            echo 'OK';
        } catch (Exception \$e) {
            fwrite(STDERR, \$e->getMessage() . PHP_EOL);
            exit(1);
        }
    " 2>&1 | grep -q OK; then
        echo "[start] Database is ready."
        CONNECTED=1
        break
    fi
    echo "[start] DB not ready yet, retrying ($i/30)..."
    sleep 2
done

if [ "$CONNECTED" -eq 0 ]; then
    echo "[start] ERROR: Could not connect to PostgreSQL after 30 retries."
    echo "[start] Host=${PG_HOST} Port=${PG_PORT} DB=${PG_DB} User=${PG_USER}"
    exit 1
fi

# ── Key check ─────────────────────────────────────────────────────────────────
if [ -z "$APP_KEY" ]; then
    echo "[start] ERROR: APP_KEY is not set. Set it in Railway Variables."
    exit 1
fi

# ── Migrations ────────────────────────────────────────────────────────────────
echo "[start] Running migrations..."
php artisan migrate --force

# ── Artisan caches ────────────────────────────────────────────────────────────
echo "[start] Caching config..."
php artisan config:cache

echo "[start] Caching routes..."
php artisan route:cache

echo "[start] Caching views..."
php artisan view:cache

echo "[start] Optimising class map..."
php artisan optimize

# ── Storage link ──────────────────────────────────────────────────────────────
echo "[start] Linking storage..."
# Remove a stale real directory if it somehow survived the Docker build step,
# then create the symlink. The Dockerfile already does `rm -rf public/storage`
# but this guard makes the script safe if run outside Docker too.
if [ -d /app/public/storage ] && [ ! -L /app/public/storage ]; then
    rm -rf /app/public/storage
fi
php artisan storage:link --force

echo "[start] Boot complete. Starting Apache..."

# ── Bind to Railway's dynamic port ───────────────────────────────────────────
# Railway assigns a different PORT each deploy and routes its healthcheck
# (/up) to that port specifically. The Dockerfile bakes in a __PORT__
# placeholder (it doesn't know the real port at build time); swap in the
# live value now, right before Apache starts listening.
APP_PORT="${PORT:-80}"
echo "[start] Binding Apache to port ${APP_PORT}..."
sed -i "s/__PORT__/${APP_PORT}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

exec apache2-foreground