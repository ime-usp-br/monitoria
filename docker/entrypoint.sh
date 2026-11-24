#!/bin/sh
set -eu

LARAVEL_USER="www-data"
LARAVEL_GROUP="www-data"
ALLOW_DEV_ENV="${ALLOW_DEV_ENV:-false}"

env_value() {
    grep -E "^${1}=" .env | tail -n 1 | cut -d= -f2- | sed 's/^"//; s/"$//'
}

if [ ! -f .env ]; then
    echo "ERRO: .env não encontrado. O container não cria .env em produção." >&2
    exit 1
fi

if [ "$ALLOW_DEV_ENV" != "true" ]; then
    APP_ENV_VALUE="$(env_value APP_ENV)"
    APP_DEBUG_VALUE="$(env_value APP_DEBUG)"
    APP_KEY_VALUE="$(env_value APP_KEY)"
    APP_URL_VALUE="$(env_value APP_URL)"
    DB_PASSWORD_VALUE="$(env_value DB_PASSWORD)"

    [ "$APP_ENV_VALUE" = "production" ] || { echo "ERRO: APP_ENV deve ser production." >&2; exit 1; }
    [ "$APP_DEBUG_VALUE" = "false" ] || { echo "ERRO: APP_DEBUG deve ser false." >&2; exit 1; }
    [ -n "$APP_KEY_VALUE" ] || { echo "ERRO: APP_KEY não configurada." >&2; exit 1; }
    [ -n "$DB_PASSWORD_VALUE" ] && [ "$DB_PASSWORD_VALUE" != "root" ] || { echo "ERRO: DB_PASSWORD deve ser definida e diferente de root." >&2; exit 1; }

    case "$APP_URL_VALUE" in
        https://*) ;;
        *) echo "ERRO: APP_URL deve usar https." >&2; exit 1 ;;
    esac
fi

[ -d vendor ] || { echo "ERRO: vendor ausente. Rode composer install --no-dev antes do deploy." >&2; exit 1; }
[ -f public/build/manifest.json ] || { echo "ERRO: assets não compilados. Rode npm ci && npm run build antes do deploy." >&2; exit 1; }

mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         bootstrap/cache

chown -R "${LARAVEL_USER}:${LARAVEL_GROUP}" storage bootstrap/cache
chmod -R u+rwX storage bootstrap/cache

touch storage/logs/replicado.log
chown "${LARAVEL_USER}:${LARAVEL_GROUP}" storage/logs/replicado.log
chmod 640 storage/logs/replicado.log

if [ ! -e public/storage ]; then
    setpriv --reuid="${LARAVEL_USER}" --regid="${LARAVEL_GROUP}" --init-groups php artisan storage:link || true
fi

DB_HOST_VALUE="$(env_value DB_HOST)"
DB_PORT_VALUE="$(env_value DB_PORT)"
DB_USERNAME_VALUE="$(env_value DB_USERNAME)"
DB_PASSWORD_VALUE="$(env_value DB_PASSWORD)"

echo "Aguardando o banco de dados..."
until DB_HOST="$DB_HOST_VALUE" DB_PORT="$DB_PORT_VALUE" DB_USERNAME="$DB_USERNAME_VALUE" DB_PASSWORD="$DB_PASSWORD_VALUE" \
    php -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' 2>/dev/null; do
    sleep 2
done

echo "Executando migrations..."
setpriv --reuid="${LARAVEL_USER}" --regid="${LARAVEL_GROUP}" --init-groups php artisan migrate --force

exec php-fpm
