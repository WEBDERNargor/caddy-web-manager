#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/var/www/html"
cd "$APP_DIR"

# Allow composer to run as root
export COMPOSER_ALLOW_SUPERUSER=1

# Load .env to get SQLITE_PATH (if present)
set +x
if [ -f ".env" ]; then
  set -a
  # shellcheck disable=SC1091
  . ./.env || true
  set +a
fi

# Prepare SQLite path
SQLITE_PATH_C="${SQLITE_PATH:-storage/database.sqlite}"
SQLITE_DIR_C="$(dirname "$SQLITE_PATH_C")"

# Ensure directory exists and is writable
mkdir -p "$SQLITE_DIR_C" || true
chown -R www-data:www-data "$SQLITE_DIR_C" || true
chmod -R 775 "$SQLITE_DIR_C" || true

# Ensure file exists with correct perms
if [ ! -f "$SQLITE_PATH_C" ]; then
  touch "$SQLITE_PATH_C" || true
fi
chown www-data:www-data "$SQLITE_PATH_C" || true
chmod 664 "$SQLITE_PATH_C" || true

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
