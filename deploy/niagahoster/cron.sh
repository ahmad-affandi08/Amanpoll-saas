#!/bin/sh

# Edit dua nilai ini sebelum dipakai.
PHP_BIN="/usr/bin/php"
APP_DIR="/home/u123456789/domains/domain-anda.tld/amanpoll"

cd "$APP_DIR" || exit 1
exec "$PHP_BIN" artisan schedule:run
