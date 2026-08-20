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
attempt=0
while ! migrate_output=$(php artisan migrate --force 2>&1); do
    attempt=$((attempt + 1))

    # Migrations legitimately fail while MySQL is still booting, so we keep
    # retrying silently -- but a real failure (bad credentials, broken
    # migration, etc.) would otherwise retry forever with no clue why. Surface
    # the last error every 5 attempts so it shows up in `docker logs` without
    # spamming the output on every retry.
    if [ $((attempt % 5)) -eq 0 ]; then
        echo "Still waiting for MySQL after ${attempt} attempts. Last error:"
        echo "$migrate_output"
    fi

    sleep 2
done

echo "Starting server on :8000"
exec php artisan serve --host=0.0.0.0 --port=8000
