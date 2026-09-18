#!/usr/bin/env bash
set -euo pipefail

# Jalankan dari folder source: domains/domain-anda.tld/amanpoll
# Build frontend sebaiknya sudah dibuat di lokal/CI sehingga public/build ikut ter-upload.

if command -v composer2 >/dev/null 2>&1; then
  COMPOSER_BIN="composer2"
else
  COMPOSER_BIN="composer"
fi

$COMPOSER_BIN install --no-dev --prefer-dist --no-interaction --optimize-autoloader
php artisan down --retry=15 || true
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up

echo "Deploy Amanpoll selesai."
