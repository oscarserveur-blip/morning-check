#!/bin/bash
set -e

# Only run migrations if the database is reachable
until php artisan migrate:status >/dev/null 2>&1; do
  echo "Waiting for database..."
  sleep 5
done

# Run migrations & seeds
php artisan migrate --force
php artisan db:seed --force

# Start PHP-FPM
exec "$@"
