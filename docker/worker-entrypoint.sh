#!/bin/sh
set -eu

LARAVEL_USER="www-data"
LARAVEL_GROUP="www-data"

env_value() {
    grep -E "^${1}=" .env | tail -n 1 | cut -d= -f2- | sed 's/^"//; s/"$//'
}

[ -f .env ] || { echo "ERRO: .env não encontrado." >&2; exit 1; }
[ -f vendor/autoload.php ] || { echo "ERRO: vendor/autoload.php ausente." >&2; exit 1; }

mkdir -p storage/logs
touch storage/logs/replicado.log
chown "${LARAVEL_USER}:${LARAVEL_GROUP}" storage/logs/replicado.log
chmod 640 storage/logs/replicado.log

DB_HOST_VALUE="$(env_value DB_HOST)"
DB_PORT_VALUE="$(env_value DB_PORT)"
DB_USERNAME_VALUE="$(env_value DB_USERNAME)"
DB_PASSWORD_VALUE="$(env_value DB_PASSWORD)"

echo "[worker] Aguardando o banco de dados..."
until DB_HOST="$DB_HOST_VALUE" DB_PORT="$DB_PORT_VALUE" DB_USERNAME="$DB_USERNAME_VALUE" DB_PASSWORD="$DB_PASSWORD_VALUE" \
    php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' 2>/dev/null; do
    sleep 2
done

exec setpriv --reuid="${LARAVEL_USER}" --regid="${LARAVEL_GROUP}" --init-groups \
    php artisan queue:work --tries=3 --timeout=120 --max-time=3600 --sleep=3
