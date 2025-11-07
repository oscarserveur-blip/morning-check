#!/bin/bash
echo "Waiting for MySQL to be ready..."
for i in {1..30}; do
  if php -r 'try { $pdo = new PDO("mysql:host=db;port=3306;dbname=check_du_matin", "laravel", "laravel"); $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); exit(0); } catch (Exception $e) { exit(1); }' 2>/dev/null; then
    echo "MySQL is ready!"
    exit 0
  fi
  echo "MySQL is not ready yet. Waiting... ($i/30)"
  sleep 2
done
echo "MySQL did not become ready in time!"
exit 1

