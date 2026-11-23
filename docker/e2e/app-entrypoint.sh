#!/bin/sh
set -e

# Entrypoint da aplicação no stack E2E. O schema é criado pelo serviço `seed`.
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/framework/testing \
         storage/logs \
         storage/app/public \
         storage/app/images \
         bootstrap/cache

if [ -d /opt/e2e-storage/images ]; then
    cp -an /opt/e2e-storage/images/. storage/app/images/ 2>/dev/null || true
fi

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R u+rwX,g+rwX storage bootstrap/cache public 2>/dev/null || true

echo "[e2e-app] aguardando MariaDB..."
until php -r "new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done

php artisan storage:link >/dev/null 2>&1 || true
php artisan config:clear >/dev/null 2>&1 || true
php artisan view:clear >/dev/null 2>&1 || true

echo "[e2e-app] iniciando php-fpm"
exec php-fpm
