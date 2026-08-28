#!/bin/sh
set -e

# -----------------------------------------------------------------------------
# Laravel Docker Entrypoint — Monitoria
# Resolve problemas de permissão entre root, www-data e host e prepara o .env
# com valores compatíveis com o ambiente Docker.
# -----------------------------------------------------------------------------

LARAVEL_USER="www-data"
LARAVEL_GROUP="www-data"

# Detecta o UID/GID do host usando um arquivo imutável (mantém owner original)
HOST_UID=$(stat -c '%u' composer.json)
HOST_GID=$(stat -c '%g' composer.json)

# Ensure storage directories exist with correct ownership before ANY command runs
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/framework/testing
mkdir -p storage/logs
mkdir -p storage/app/public
mkdir -p bootstrap/cache

# Fix ownership and permissions BEFORE running artisan commands.
chown -R "${LARAVEL_USER}:${LARAVEL_GROUP}" storage bootstrap/cache
chmod -R u+rwx storage bootstrap/cache

# Log do uspdev/replicado: garante que o arquivo exista e pertença ao usuário
# da aplicação (evita "Permission denied" quando web/worker rodam como www-data).
touch storage/logs/replicado.log
chown "${LARAVEL_USER}:${LARAVEL_GROUP}" storage/logs/replicado.log 2>/dev/null || true
chmod u+rw,go+r storage/logs/replicado.log 2>/dev/null || true

# If cache driver is file, ensure existing cache files are not owned by root.
if [ -d "storage/framework/cache/data" ]; then
    find storage/framework/cache/data -user root -exec chown "${LARAVEL_USER}:${LARAVEL_GROUP}" {} + 2>/dev/null || true
fi

# Install dependencies if vendor is missing (run as root to avoid permission issues with composer cache)
if [ ! -d "vendor" ]; then
    echo "Installing PHP dependencies..."
    composer install --no-interaction --optimize-autoloader
fi

# Install node_modules if missing
if [ ! -d "node_modules" ]; then
    echo "Installing Node dependencies..."
    npm install
fi

# Build assets only if they are missing (avoid overwriting pre-compiled files)
if [ ! -f "public/js/app.js" ] || [ ! -f "public/css/app.css" ]; then
    echo "Building assets..."
    npm run dev 2>/dev/null || PATH="$PATH:/var/www/node_modules/.bin" npx mix || true
fi

# Copy .env if it does not exist, ajustando valores para o ambiente Docker
if [ ! -f ".env" ]; then
    echo "Creating .env file from .env.example (docker defaults)..."
    cp .env.example .env
    sed -i 's|^DB_HOST=.*|DB_HOST=mysql|'        .env
    sed -i 's|^DB_PORT=.*|DB_PORT=3306|'          .env
    sed -i 's|^DB_USERNAME=.*|DB_USERNAME=laravel|' .env
    sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=root|'  .env
    sed -i 's|^REDIS_HOST=.*|REDIS_HOST=redis|'   .env
    sed -i 's|^REDIS_PORT=.*|REDIS_PORT=6379|'    .env
    sed -i 's|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|' .env
    sed -i 's|^BASE_PATH=.*|BASE_PATH="/var/www"|' .env
    sed -i 's|^PYTHON_CMD=.*|PYTHON_CMD="/usr/bin/python3"|' .env
fi

# Ensure .env is writable by the Laravel user before any artisan command that modifies it
chown "${HOST_UID}:${LARAVEL_GROUP}" .env 2>/dev/null || true
chmod u+rwx,g+rw .env 2>/dev/null || true

# Generate app key if missing (run as www-data so .env remains writable by php-fpm)
if [ -z "$(grep '^APP_KEY=' .env | cut -d '=' -f2)" ]; then
    echo "Generating application key..."
    gosu "${LARAVEL_USER}" php artisan key:generate
fi

# Wait for MySQL to be ready before running migrations
echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${DB_HOST:-mysql};port=${DB_PORT:-3306}', '${DB_USERNAME:-laravel}', '${DB_PASSWORD:-root}');" 2>/dev/null; do
    sleep 2
done
echo "MySQL ready."

# Run migrations as www-data so any created files are not root-owned
echo "Running migrations..."
gosu "${LARAVEL_USER}" php artisan migrate --force || true

# Ensure public directory is writable by www-data for storage:link
chown "${HOST_UID}:${LARAVEL_GROUP}" public 2>/dev/null || true
chmod u+rwx,g+rwxs public 2>/dev/null || true

# Storage link as www-data
gosu "${LARAVEL_USER}" php artisan storage:link || true

# Clear and rebuild cache as www-data so cache files are owned by www-data, not root.
echo "Clearing caches to ensure correct ownership..."
gosu "${LARAVEL_USER}" php artisan cache:clear || true
gosu "${LARAVEL_USER}" php artisan config:clear || true
gosu "${LARAVEL_USER}" php artisan view:clear || true

# Final permission fix in case any command above created files
chown -R "${LARAVEL_USER}:${LARAVEL_GROUP}" storage bootstrap/cache
chmod -R u+rwx storage bootstrap/cache

# Restore ownership of vendor/node_modules to the host user so the developer
# can edit/delete files without sudo. Also fix .env ownership.
chown -R "${HOST_UID}:${HOST_GID}" vendor node_modules .env 2>/dev/null || true
chmod -R u+rx vendor node_modules 2>/dev/null || true

# Ensure the main project directory is readable/writeable by both host user and www-data.
chown "${HOST_UID}:${LARAVEL_GROUP}" . 2>/dev/null || true
chmod u+rwx,g+rxs . 2>/dev/null || true

# Start php-fpm
exec php-fpm