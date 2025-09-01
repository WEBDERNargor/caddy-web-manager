#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Allow composer to run as root
export COMPOSER_ALLOW_SUPERUSER=1

# Install dependencies if vendor is missing
if [ ! -f "vendor/autoload.php" ]; then
  echo "[entrypoint] Installing PHP dependencies with Composer..."
  if [ -f "composer.lock" ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
  else
    composer install --no-interaction --prefer-dist --optimize-autoloader
  fi
else
  echo "[entrypoint] Vendor already present, skipping composer install."
fi

exec "$@"
