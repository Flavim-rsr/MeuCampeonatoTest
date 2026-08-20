#!/bin/sh
set -e

# `php artisan serve` forwards only a fixed whitelist of variables to the PHP
# built-in server it spawns (ServeCommand::$passthroughVariables), so whatever
# Compose injects into the container -- DB_HOST above all -- is invisible to
# HTTP requests, which then fall back to the .env baked into the image. The
# artisan CLI (migrations) does see them, so the two would disagree about which
# database they are talking to. Sync them into .env before booting.
sync_env() {
    key=$1
    value=$(printenv "$key" || true)

    [ -n "$value" ] || return 0

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >>.env
    fi
}

for key in DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
    sync_env "$key"
done

echo "Waiting for MySQL..."
until php artisan migrate --force 2>/dev/null; do
    sleep 2
done

echo "Starting server on :8000"
exec php artisan serve --host=0.0.0.0 --port=8000
