#!/bin/sh
set -e

if [ -z "$APP_KEY" ]; then
  echo "APP_KEY not set, generating one..."
  php artisan key:generate --force
fi

echo "Waiting for MySQL..."
until php artisan migrate --force 2>/dev/null; do
  sleep 2
done

echo "Starting server on :8000"
exec php artisan serve --host=0.0.0.0 --port=8000
