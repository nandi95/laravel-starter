#!/bin/sh
# stop running as soon as any command this executes fails
set -e

role=${CONTAINER_ROLE:-app}

echo "Starting $role container"

wait-for-it "$DB_HOST:${DB_PORT:-3306}" --timeout=30 --strict -- echo "MySQL is up"
wait-for-it "$REDIS_HOST:${REDIS_PORT:-6379}" --timeout=30 --strict -- echo "Redis is up"

if [ "$role" = "api" ]; then
    php artisan optimize
    php artisan migrate --force --isolated
    php artisan db:seed --force

    frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
#    to enable octane use the below and point caddy to the server
#    but for now opache is quite enough
#    php artisan octane:frankenphp
elif [ "$role" = "scheduler" ]; then
    php artisan schedule:work
elif [ "$role" = "queue" ]; then
    php artisan queue:work --tries=3 --queue=high,default
else
    echo "Could not match the container role \"$role\""
    exit 1
fi
