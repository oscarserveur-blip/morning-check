#!/bin/sh
# Attendre que MySQL soit prêt
until mysql -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" >/dev/null 2>&1; do
  echo "Waiting for MySQL..."
  sleep 2
done

# Lancer migrations
php artisan migrate --force

# Lancer le serveur PHP interne
php -S 0.0.0.0:8000 -t public
