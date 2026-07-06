#!/bin/sh
set -e

# -----------------------------------------------------------------------------
# Worker Entrypoint — Monitoria
# Aguarda o app terminar de instalar dependências (vendor/autoload.php)
# e o MySQL ficar pronto antes de iniciar o queue:work.
# ---------------------------------------------------------------------------

LARAVEL_USER="www-data"
LARAVEL_GROUP="www-data"

# Aguarda vendor/autoload.php existir (app pode ainda estar no composer install)
echo "[worker] Aguardando vendor/autoload.php..."
until [ -f "vendor/autoload.php" ]; do
    sleep 2
done
echo "[worker] vendor encontrado."

# Aguarda MySQL estar acessivel antes de iniciar o worker.
echo "[worker] Aguardando MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306}', '${DB_USERNAME:-laravel}', '${DB_PASSWORD:-root}');" 2>/dev/null; do
    sleep 2
done
echo "[worker] MySQL pronto. Iniciando queue:listen..."

# queue:listen rele o codigo a cada job, util em desenvolvimento.
exec gosu "${LARAVEL_USER}" php artisan queue:listen --tries=3 --sleep=3