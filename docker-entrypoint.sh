#!/bin/sh
# Entrypoint for the production container.
#
# Responsibilities (all infra-only — no app code is modified):
#   1. Honor Railway's dynamic $PORT by templating it into the nginx vhost.
#   2. Warm Laravel's framework caches (config / route / view / events)
#      using runtime env vars. These caches don't change application
#      behavior, they just precompile what the framework would otherwise
#      build on every request.
#   3. Hand off to supervisord, which runs php-fpm + nginx in foreground.
set -e

: "${PORT:=8080}"
export PORT

# Render /etc/nginx/conf.d/default.conf from the template committed in the
# repo. envsubst is restricted to $PORT so nginx variables ($uri, etc.)
# survive untouched.
envsubst '${PORT}' \
    < /etc/nginx/conf.d/default.conf.template \
    > /etc/nginx/conf.d/default.conf

# Warm Laravel framework caches. Failures here are non-fatal — if e.g.
# APP_KEY hasn't been set yet we'd rather boot up serving uncached
# requests than crash-loop the container.
cd /app
php artisan config:cache 2>/dev/null || true
php artisan route:cache  2>/dev/null || true
php artisan view:cache   2>/dev/null || true
php artisan event:cache  2>/dev/null || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
