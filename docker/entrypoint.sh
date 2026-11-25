#!/bin/sh
# ---------------------------------------------------------------------------
# Entrypoint único da imagem de deploy.
#
# Não existe .env no servidor: todas as variáveis chegam do Portainer (ou do
# shell local) como variáveis de ambiente. O container é idempotente e
# fail-closed para configuração inválida.
#
# CONTAINER_ROLE=web     -> espera o banco, migra, semeia (se permitido),
#                           aquece cache e inicia php-fpm.
# CONTAINER_ROLE=worker  -> espera o banco e inicia a fila. Nunca migra,
#                           nunca semeia e não usa a rede `web`.
# ---------------------------------------------------------------------------
set -eu

ROLE="${CONTAINER_ROLE:-web}"
APP_ENV="${APP_ENV:-local}"
DEPLOY_ENV="${DEPLOY_ENV:?DEPLOY_ENV é obrigatório (dev|homolog|prod)}"
APP_KEY="${APP_KEY:?APP_KEY é obrigatório}"
DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:?DB_HOST é obrigatório e deve ser o domínio do banco externo}"
DB_PASSWORD="${DB_PASSWORD:-${DB_PASS:-}}"
DB_PASSWORD="${DB_PASSWORD:?DB_PASSWORD é obrigatório}"

# Aceita tanto o valor nativo do Laravel (local|production) quanto o valor
# canônico de deploy (dev|homolog|prod), normalizando para o primeiro.
case "$APP_ENV" in
    dev)                         APP_ENV="local" ;;
    homolog|prod)                APP_ENV="production" ;;
    local|production|testing|staging) ;;
    *)
        echo "ERRO: APP_ENV inválido: '$APP_ENV'. Use local|production (ou dev|homolog|prod)." >&2
        exit 1
        ;;
esac
export APP_ENV

case "$DEPLOY_ENV" in
    dev|homolog|prod) ;;
    *)
        echo "ERRO: DEPLOY_ENV inválido: '$DEPLOY_ENV'. Use dev|homolog|prod." >&2
        exit 1
        ;;
esac

if [ "$DEPLOY_ENV" = "dev" ] && [ "$APP_ENV" != "local" ]; then
    echo "ERRO: DEPLOY_ENV=dev exige APP_ENV=local (recebido '$APP_ENV')." >&2
    exit 1
fi

if [ "$DEPLOY_ENV" != "dev" ] && [ "$APP_ENV" = "local" ]; then
    echo "ERRO: DEPLOY_ENV=$DEPLOY_ENV exige APP_ENV=production (recebido '$APP_ENV')." >&2
    exit 1
fi

if [ "$APP_ENV" = "production" ] && [ "${APP_DEBUG:-false}" = "true" ]; then
    echo "ERRO: APP_DEBUG=true não é permitido com APP_ENV=production." >&2
    exit 1
fi

case "$DB_HOST" in
    db|mysql|mariadb|postgres|postgresql|localhost|127.0.0.1|host.docker.internal|app|web|worker)
        echo "ERRO: DB_HOST='$DB_HOST' não é permitido. Use o domínio do banco externo (ex.: db-dev.<dominio>)." >&2
        exit 1
        ;;
esac

# Variável vazia confunde o Laravel (config/database usa DATABASE_URL quando
# presente). Remove quando o contrato usa DB_*.
if [ -z "${DATABASE_URL:-}" ]; then
    unset DATABASE_URL
fi

export TZ="${TZ:-America/Sao_Paulo}"
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"

READY_FILE="storage/framework/.deploy-ready"

echo "[entrypoint] role=${ROLE} deploy_env=${DEPLOY_ENV} app_env=${APP_ENV} db_host=${DB_HOST}"

# Diretórios graváveis (o volume nomeado de storage preserva o dono na primeira
# criação; quando não for possível ajustar, reporta erro claro).
mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    storage/app/public \
    bootstrap/cache

if ! touch storage/logs/.write-test 2>/dev/null; then
    echo "ERRO: storage/logs não é gravável pelo usuário $(id -un). Verifique o volume app-storage." >&2
    exit 1
fi
rm -f storage/logs/.write-test

touch storage/logs/replicado.log 2>/dev/null || true
chmod 640 storage/logs/replicado.log 2>/dev/null || true

# Cria o link público apenas se o build não o tiver criado (normalmente já vem).
if [ ! -e public/storage ] && [ -d public ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

if [ "$ROLE" = "web" ]; then
    # Evita que um worker de um deploy anterior veja um marcador obsoleto.
    rm -f "$READY_FILE"
fi

echo "[entrypoint] aguardando banco externo ${DB_HOST}..."
sh /var/www/scripts/wait-for-db.sh

run_migrations() {
    if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
        echo "[entrypoint] executando migrations..."
        php artisan migrate --force
    else
        echo "[entrypoint] migrations desabilitadas (RUN_MIGRATIONS=${RUN_MIGRATIONS:-false})."
    fi
}

run_seeders() {
    if [ "${RUN_SEEDERS:-false}" != "true" ]; then
        echo "[entrypoint] seeders desabilitados."
        return 0
    fi

    if [ "$DEPLOY_ENV" = "prod" ]; then
        echo "ERRO: RUN_SEEDERS=true é proibido em produção." >&2
        exit 1
    fi

    echo "[entrypoint] executando seeders (${DEPLOY_ENV})..."
    php artisan db:seed --force
}

warm_cache() {
    if [ "${WARM_CACHE:-false}" = "true" ]; then
        echo "[entrypoint] aquecendo caches..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    else
        echo "[entrypoint] cache não aquecido; limpando caches antigos..."
        php artisan optimize:clear >/dev/null 2>&1 || true
    fi
}

wait_for_web_ready() {
    # O worker não roda migrations; ele espera o serviço web concluir a
    # preparação do schema quando RUN_MIGRATIONS=true (volume storage é
    # compartilhado entre app e worker).
    if [ "${RUN_MIGRATIONS:-false}" != "true" ]; then
        return 0
    fi

    timeout="${DEPLOY_READY_TIMEOUT:-180}"
    elapsed=0
    echo "[entrypoint] aguardando o serviço web concluir migrations/seeders..."
    until [ -f "$READY_FILE" ]; do
        if [ "$elapsed" -ge "$timeout" ]; then
            echo "ERRO: o serviço web não sinalizou readiness em ${timeout}s (${READY_FILE})." >&2
            exit 1
        fi
        sleep 2
        elapsed=$((elapsed + 2))
    done
    echo "[entrypoint] serviço web pronto; iniciando worker."
}

case "$ROLE" in
    web)
        run_migrations
        run_seeders
        touch "$READY_FILE"
        warm_cache
        echo "[entrypoint] iniciando php-fpm"
        exec php-fpm -F
        ;;
    worker)
        wait_for_web_ready
        echo "[entrypoint] iniciando worker de fila"
        exec php artisan queue:work --tries=3 --timeout=120 --max-time=3600 --sleep=3
        ;;
    *)
        echo "ERRO: CONTAINER_ROLE inválido: '$ROLE'. Use web|worker." >&2
        exit 1
        ;;
esac
