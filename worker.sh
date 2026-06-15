#!/usr/bin/env sh
# Railway worker process — deploy this as a separate service in Railway.
# Set the start command to: sh worker.sh
set -e
cd /app
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
