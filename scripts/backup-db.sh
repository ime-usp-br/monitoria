#!/usr/bin/env bash
# Backup padronizado do banco do projeto (externo, acessado por domínio).
#
# Lê DB_CONNECTION/DB_HOST/DB_PORT/DB_DATABASE/DB_USERNAME/DB_PASSWORD ou
# DATABASE_URL. Nunca imprime senha. Pode rodar no host ou no container app.
#
# Saída: ${BACKUP_DIR}/<projeto>-<env>-<db>-YYYYmmdd-HHMMSS.sql.gz
#        + .sha256
#
# Uso:
#   ./scripts/backup-db.sh
#   BACKUP_DIR=storage/backups BACKUP_RETENTION_DAYS=14 ./scripts/backup-db.sh

set -Eeuo pipefail
umask 077

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

APP_PROJECT="${APP_PROJECT:-monitoria}"
DEPLOY_ENV="${DEPLOY_ENV:-dev}"
BACKUP_DIR="${BACKUP_DIR:-backups}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-14}"

parse_database_url() {
    local url="${1:?DATABASE_URL vazia}"
    local rest creds hostpart hostport db proto

    proto="${url%%://*}"
    rest="${url#*://}"
    creds="${rest%%@*}"
    hostpart="${rest#*@}"
    db="${hostpart#*/}"
    db="${db%%\?*}"
    hostport="${hostpart%%/*}"

    DB_CONNECTION="${DB_CONNECTION:-$proto}"
    DB_USERNAME="${DB_USERNAME:-${creds%%:*}}"
    DB_PASSWORD="${DB_PASSWORD:-${creds#*:}}"
    DB_HOST="${DB_HOST:-${hostport%%:*}}"
    DB_DATABASE="${DB_DATABASE:-$db}"

    if [[ "$hostport" == *:* ]]; then
        DB_PORT="${DB_PORT:-${hostport##*:}}"
    fi
}

if [[ -z "${DB_HOST:-}" && -n "${DATABASE_URL:-}" ]]; then
    parse_database_url "$DATABASE_URL"
fi

DB_DATABASE="${DB_DATABASE:-${DB_NAME:-}}"
DB_USERNAME="${DB_USERNAME:-${DB_USER:-}}"
DB_PASSWORD="${DB_PASSWORD:-${DB_PASS:-}}"

: "${DB_CONNECTION:=mysql}"
: "${DB_HOST:?backup-db: DB_HOST é obrigatório}"
: "${DB_DATABASE:?backup-db: DB_DATABASE/DB_NAME é obrigatório}"
: "${DB_USERNAME:?backup-db: DB_USERNAME/DB_USER é obrigatório}"
: "${DB_PASSWORD:?backup-db: DB_PASSWORD/DB_PASS é obrigatório}"

case "$DB_HOST" in
    db|mysql|mariadb|postgres|postgresql|localhost|127.0.0.1|host.docker.internal|app|web|worker)
        echo "ERRO: DB_HOST='$DB_HOST' não é um domínio externo válido." >&2
        exit 1
        ;;
esac

case "$DB_CONNECTION" in
    mysql|mariadb) DB_KIND="mysql"; : "${DB_PORT:=3306}" ;;
    pgsql|postgres|postgresql) DB_KIND="pgsql"; : "${DB_PORT:=5432}" ;;
    *)
        echo "ERRO: DB_CONNECTION='$DB_CONNECTION' não suportado (mysql/mariadb ou pgsql/postgresql)." >&2
        exit 1
        ;;
esac

if ! command -v gzip >/dev/null 2>&1; then
    echo "ERRO: gzip não encontrado." >&2
    exit 1
fi
if ! command -v sha256sum >/dev/null 2>&1; then
    echo "ERRO: sha256sum não encontrado." >&2
    exit 1
fi

if [[ "$DB_KIND" == "mysql" ]]; then
    command -v mysqldump >/dev/null 2>&1 || { echo "ERRO: mysqldump não encontrado." >&2; exit 1; }
else
    command -v pg_dump >/dev/null 2>&1 || { echo "ERRO: pg_dump não encontrado." >&2; exit 1; }
fi

echo "[backup-db] validando conexão com ${DB_HOST}:${DB_PORT}/${DB_DATABASE}..."
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-15}" sh "$SCRIPT_DIR/wait-for-db.sh"

mkdir -p "$BACKUP_DIR"
if [[ ! -w "$BACKUP_DIR" ]]; then
    echo "ERRO: BACKUP_DIR='$BACKUP_DIR' não é gravável." >&2
    exit 1
fi

timestamp="$(date +%Y%m%d-%H%M%S)"
safe_db="${DB_DATABASE//\//_}"
base="${APP_PROJECT}-${DEPLOY_ENV}-${safe_db}-${timestamp}"
out="${BACKUP_DIR}/${base}.sql.gz"
tmp="${out}.part"

cleanup() {
    [[ -f "$tmp" ]] && rm -f "$tmp"
}
trap cleanup EXIT

echo "[backup-db] gerando ${out}"
if [[ "$DB_KIND" == "mysql" ]]; then
    MYSQL_PWD="$DB_PASSWORD" mysqldump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USERNAME" \
        --single-transaction \
        --quick \
        --routines \
        --events \
        --triggers \
        --no-tablespaces \
        --default-character-set=utf8mb4 \
        "$DB_DATABASE" | gzip -9 > "$tmp"
else
    PGPASSWORD="$DB_PASSWORD" pg_dump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --username="$DB_USERNAME" \
        --dbname="$DB_DATABASE" \
        --no-owner \
        --no-privileges \
        --format=plain | gzip -9 > "$tmp"
fi

if [[ ! -s "$tmp" ]]; then
    echo "ERRO: backup vazio; verifique permissões e o banco." >&2
    exit 1
fi

mv "$tmp" "$out"
trap - EXIT

(
    cd "$BACKUP_DIR"
    sha256sum "$(basename "$out")" > "$(basename "$out").sha256"
)

echo "[backup-db] OK: ${out}"
echo "[backup-db] checksum: ${out}.sha256"

if [[ "$BACKUP_RETENTION_DAYS" =~ ^[0-9]+$ ]] && (( BACKUP_RETENTION_DAYS > 0 )); then
    echo "[backup-db] aplicando retenção de ${BACKUP_RETENTION_DAYS} dias..."
    find "$BACKUP_DIR" -maxdepth 1 -type f \
        \( -name "${APP_PROJECT}-${DEPLOY_ENV}-${safe_db}-*.sql.gz" -o -name "${APP_PROJECT}-${DEPLOY_ENV}-${safe_db}-*.sql.gz.sha256" \) \
        -mtime +"$BACKUP_RETENTION_DAYS" -delete
fi

echo "[backup-db] concluído."
