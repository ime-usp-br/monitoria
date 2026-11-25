#!/usr/bin/env bash
# Restore padronizado de um backup do banco do projeto.
#
# Uso:
#   ./scripts/restore-db.sh backups/monitoria-dev-monitoria-20260101-120000.sql.gz
#   DEPLOY_ENV=prod ./scripts/restore-db.sh --confirm-prod backups/arquivo.sql.gz
#
# A operação é DESTRUTIVA: sobrescreve dados existentes no banco alvo. Exige
# confirmação explícita (interativa ou RESTORE_CONFIRM=yes) e, em produção,
# também --confirm-prod. Não apaga o backup após o restore.

set -Eeuo pipefail
umask 077

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROD_CONFIRM=false
BACKUP_FILE=""

usage() {
    cat >&2 <<'EOF'
Uso: restore-db.sh [--confirm-prod] <arquivo.sql|arquivo.sql.gz>

Variáveis: DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME,
DB_PASSWORD (ou DATABASE_URL), APP_PROJECT, DEPLOY_ENV, RESTORE_CONFIRM=yes.
EOF
    exit 2
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --confirm-prod) PROD_CONFIRM=true; shift ;;
        -h|--help) usage ;;
        -*) echo "Opção desconhecida: $1" >&2; usage ;;
        *) BACKUP_FILE="$1"; shift ;;
    esac
done

[[ -n "$BACKUP_FILE" ]] || usage

APP_PROJECT="${APP_PROJECT:-monitoria}"
DEPLOY_ENV="${DEPLOY_ENV:-dev}"

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
: "${DB_HOST:?restore-db: DB_HOST é obrigatório}"
: "${DB_DATABASE:?restore-db: DB_DATABASE/DB_NAME é obrigatório}"
: "${DB_USERNAME:?restore-db: DB_USERNAME/DB_USER é obrigatório}"
: "${DB_PASSWORD:?restore-db: DB_PASSWORD/DB_PASS é obrigatório}"

case "$DB_CONNECTION" in
    mysql|mariadb) DB_KIND="mysql"; : "${DB_PORT:=3306}" ;;
    pgsql|postgres|postgresql) DB_KIND="pgsql"; : "${DB_PORT:=5432}" ;;
    *)
        echo "ERRO: DB_CONNECTION='$DB_CONNECTION' não suportado." >&2
        exit 1
        ;;
esac

[[ -f "$BACKUP_FILE" ]] || { echo "ERRO: arquivo não encontrado: $BACKUP_FILE" >&2; exit 1; }

case "$BACKUP_FILE" in
    *.sql|*.sql.gz) ;;
    *) echo "ERRO: extensão inválida (esperado .sql ou .sql.gz)." >&2; exit 1 ;;
esac

checksum_file="${BACKUP_FILE}.sha256"
if [[ -f "$checksum_file" ]]; then
    echo "[restore-db] validando checksum..."
    (cd "$(dirname "$BACKUP_FILE")" && sha256sum -c "$(basename "$checksum_file")")
else
    echo "[restore-db] AVISO: checksum não encontrado; seguindo sem validação." >&2
fi

if [[ "$DB_KIND" == "mysql" ]]; then
    command -v mysql >/dev/null 2>&1 || { echo "ERRO: mysql não encontrado." >&2; exit 1; }
else
    command -v psql >/dev/null 2>&1 || { echo "ERRO: psql não encontrado." >&2; exit 1; }
fi

echo "[restore-db] validando conexão com ${DB_HOST}:${DB_PORT}/${DB_DATABASE}..."
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-15}" sh "$SCRIPT_DIR/wait-for-db.sh"

cat >&2 <<EOF

!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
ATENÇÃO: operação DESTRUTIVA.
O conteúdo de '${DB_DATABASE}' em ${DB_HOST} será sobrescrito pelo backup:
  ${BACKUP_FILE}
O arquivo de backup NÃO será apagado.
!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

EOF

if [[ "${RESTORE_CONFIRM:-}" != "yes" ]]; then
    if [[ ! -t 0 ]]; then
        echo "ERRO: confirmação explícita necessária. Use RESTORE_CONFIRM=yes em ambiente não interativo." >&2
        exit 1
    fi
    printf "Digite 'restaurar' para continuar: "
    read -r answer
    [[ "$answer" == "restaurar" ]] || { echo "Cancelado." >&2; exit 1; }
fi

if [[ "$DEPLOY_ENV" == "prod" || "${APP_ENV:-}" == "prod" ]]; then
    if [[ "$PROD_CONFIRM" != "true" ]]; then
        echo "ERRO: restore em produção exige --confirm-prod além da confirmação normal." >&2
        exit 1
    fi
fi

echo "[restore-db] restaurando..."
if [[ "$DB_KIND" == "mysql" ]]; then
    if [[ "$BACKUP_FILE" == *.gz ]]; then
        gunzip -c "$BACKUP_FILE" | MYSQL_PWD="$DB_PASSWORD" mysql \
            --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$DB_DATABASE"
    else
        MYSQL_PWD="$DB_PASSWORD" mysql \
            --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" "$DB_DATABASE" < "$BACKUP_FILE"
    fi
else
    if [[ "$BACKUP_FILE" == *.gz ]]; then
        gunzip -c "$BACKUP_FILE" | PGPASSWORD="$DB_PASSWORD" psql \
            --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" --dbname="$DB_DATABASE" \
            --set ON_ERROR_STOP=on
    else
        PGPASSWORD="$DB_PASSWORD" psql \
            --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" --dbname="$DB_DATABASE" \
            --set ON_ERROR_STOP=on < "$BACKUP_FILE"
    fi
fi

echo "[restore-db] concluído. Backup preservado em ${BACKUP_FILE}."
echo "[restore-db] valide a aplicação (healthcheck e smoke test) e as migrations."
