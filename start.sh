#!/usr/bin/env sh
set -e

cd /app

# ── Parse DB connection details from DATABASE_URL or individual vars ──────────
# DATABASE_URL format: postgresql://user:pass@host:port/dbname
if [ -n "$DATABASE_URL" ]; then
    PG_HOST=$(echo "$DATABASE_URL" | sed -E 's|.*@([^:/]+).*|\1|')
    PG_PORT=$(echo "$DATABASE_URL" | sed -E 's|.*:([0-9]+)/.*|\1|')
    PG_PORT="${PG_PORT:-5432}"

    # Extract individual creds for the PDO probe below
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

# ── Wait for PostgreSQL ───────────────────────────────────────────────────────
echo "[start] Waiting for PostgreSQL at ${PG_HOST}:${PG_PORT}..."
CONNECTED=0
for i in $(seq 1 30); do
    if php -r "new PDO('pgsql:host=${PG_HOST};port=${PG_PORT};dbname=${PG_DB}', '${PG_USER}', '${PG_PASS}');" 2>/dev/null; then
        echo "[start] Database is ready."
        CONNECTED=1
        break
    fi
    echo "[start] DB not ready yet, retrying ($i/30)..."
    sleep 2
done

if [ "$CONNECTED" -eq 0 ]; then
    echo "[start] ERROR: Could not connect to PostgreSQL after 30 retries. Aborting."
    exit 1
fi

# ── Migrations ────────────────────────────────────────────────────────────────
echo "[start] Running migrations..."
php artisan migrate --force

# ── Artisan caches (uses live env vars, not baked-in .env) ───────────────────
echo "[start] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Link storage (idempotent) ─────────────────────────────────────────────────
echo "[start] Linking storage..."
php artisan storage:link --force 2>/dev/null || true

echo "[start] Starting Apache..."
exec apache2-foreground
