#!/usr/bin/env bash
# Container entrypoint:
#   1. Render the nginx config with the PORT Render gave us.
#   2. Wait briefly for the database to be reachable.
#   3. Run pending migrations (idempotent, safe on every boot).
#   4. Warm Laravel's caches for production performance.
#   5. Hand off to supervisord which starts php-fpm + nginx.
set -euo pipefail

PORT="${PORT:-8080}"
export PORT

echo "[entrypoint] Rendering nginx config for port ${PORT}"
envsubst '${PORT}' \
    < /etc/nginx/templates/default.conf.template \
    > /etc/nginx/conf.d/default.conf

# Ensure storage / cache dirs always exist with the right perms even if the
# image is run on a fresh persistent disk.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/testing \
         storage/framework/views \
         storage/logs \
         bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Drop any stale cached config baked into the image so this container picks
# up the env vars Render injected at runtime.
php artisan config:clear  >/dev/null 2>&1 || true
php artisan route:clear   >/dev/null 2>&1 || true
php artisan view:clear    >/dev/null 2>&1 || true
php artisan event:clear   >/dev/null 2>&1 || true

# If APP_KEY isn't set we generate an ephemeral one so the container at least
# boots — but warn loudly because it changes on every restart and breaks
# encrypted sessions / cookies. The user should set APP_KEY in Render.
if [ -z "${APP_KEY:-}" ]; then
    echo "[entrypoint] WARNING: APP_KEY is empty. Set APP_KEY in Render env." >&2
    php artisan key:generate --force --show >/tmp/_appkey || true
    if [ -s /tmp/_appkey ]; then
        export APP_KEY="$(cat /tmp/_appkey)"
        echo "[entrypoint] Generated ephemeral APP_KEY (will not persist across restarts)." >&2
    fi
fi

# Run migrations only when DB credentials are present, so a misconfigured boot
# fails loudly with a clear error rather than silently skipping.
if [ -n "${DB_URL:-}" ] || [ -n "${DB_HOST:-}" ]; then
    echo "[entrypoint] Running database migrations"
    php artisan migrate --force --no-interaction
else
    echo "[entrypoint] WARNING: No DB_URL or DB_HOST set — skipping migrations." >&2
fi

# Production cache warm-up. config:cache requires a working DB connection only
# if any service provider hits the DB at boot, so we run it AFTER migrations.
echo "[entrypoint] Caching config / routes / views / events"
php artisan config:cache
php artisan route:cache  || true   # route:cache fails if routes use closures; tolerate it
php artisan view:cache
php artisan event:cache  || true

# Make sure storage is publicly linked (idempotent).
php artisan storage:link || true

echo "[entrypoint] Starting supervisord (nginx + php-fpm) on port ${PORT}"
exec "$@"
