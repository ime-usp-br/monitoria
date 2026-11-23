#!/bin/sh
set -e

if [ "${APP_ENV:-}" != "testing" ] || [ "${E2E_MODE:-}" != "true" ] || [ "${DB_DATABASE:-}" != "monitoria_e2e" ]; then
    echo "[e2e-seed] recusando migrate:fresh fora do alvo E2E (APP_ENV=$APP_ENV, E2E_MODE=$E2E_MODE, DB_DATABASE=$DB_DATABASE)." >&2
    exit 1
fi

echo "[e2e-seed] aguardando MariaDB..."
until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done

mkdir -p /e2e-artifacts storage/framework/cache/data storage/framework/sessions storage/framework/views storage/framework/testing storage/logs storage/app/public

echo "[e2e-seed] recriando schema..."
php artisan migrate:fresh --force

echo "[e2e-seed] aplicando fixtures E2E..."
php artisan e2e:seed --manifest=/e2e-artifacts/seed.json

chown -R www-data:www-data storage 2>/dev/null || true
chmod -R u+rwX,g+rwX storage /e2e-artifacts 2>/dev/null || true
echo "[e2e-seed] concluído: $(ls -lh /e2e-artifacts/seed.json | awk '{print $5}')"
