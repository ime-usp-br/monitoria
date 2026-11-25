#!/bin/sh
# Espera o banco externo por domínio, com retry e timeout.
#
# Lê DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME e DB_PASSWORD.
# Não imprime senha nem connection string. Funciona no container (PHP/PDO) e
# em hosts que tenham PHP com a extensão do driver correspondente.

set -eu

DB_CONNECTION="${DB_CONNECTION:-mysql}"
DB_HOST="${DB_HOST:?wait-for-db: DB_HOST é obrigatório}"
DB_DATABASE="${DB_DATABASE:-${DB_NAME:-}}"
DB_USERNAME="${DB_USERNAME:-${DB_USER:-}}"
DB_PASSWORD="${DB_PASSWORD:-${DB_PASS:-}}"
DB_DATABASE="${DB_DATABASE:?wait-for-db: DB_DATABASE/DB_NAME é obrigatório}"
DB_USERNAME="${DB_USERNAME:?wait-for-db: DB_USERNAME/DB_USER é obrigatório}"
DB_PASSWORD="${DB_PASSWORD:?wait-for-db: DB_PASSWORD/DB_PASS é obrigatório}"
DB_WAIT_TIMEOUT="${DB_WAIT_TIMEOUT:-120}"
DB_WAIT_INTERVAL="${DB_WAIT_INTERVAL:-2}"

case "$DB_HOST" in
    db|mysql|mariadb|postgres|postgresql|localhost|127.0.0.1|host.docker.internal|app|web|worker)
        echo "ERRO: DB_HOST='$DB_HOST' não é um domínio externo válido." >&2
        exit 1
        ;;
esac

case "$DB_CONNECTION" in
    mysql|mariadb) PDO_DRIVER="mysql" ;;
    pgsql|postgres|postgresql) PDO_DRIVER="pgsql" ;;
    *)
        echo "ERRO: DB_CONNECTION='$DB_CONNECTION' não suportado (use mysql/mariadb ou pgsql/postgresql)." >&2
        exit 1
        ;;
esac

if [ -z "${DB_PORT:-}" ]; then
    case "$PDO_DRIVER" in
        pgsql) DB_PORT=5432 ;;
        *) DB_PORT=3306 ;;
    esac
fi

export PDO_DRIVER DB_PORT

echo "[wait-for-db] conectando em ${DB_HOST}:${DB_PORT}/${DB_DATABASE} como ${DB_USERNAME} (timeout ${DB_WAIT_TIMEOUT}s)"

elapsed=0
while :; do
    if php -r '
        $driver = getenv("PDO_DRIVER");
        $dsn = $driver . ":host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE");
        $pdo = new PDO(
            $dsn,
            getenv("DB_USERNAME"),
            getenv("DB_PASSWORD"),
            [PDO::ATTR_TIMEOUT => 3, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $pdo->query("SELECT 1");
    ' >/dev/null 2>&1; then
        echo "[wait-for-db] banco disponível."
        exit 0
    fi

    if [ "$elapsed" -ge "$DB_WAIT_TIMEOUT" ]; then
        echo "ERRO: banco ${DB_HOST}:${DB_PORT}/${DB_DATABASE} não respondeu após ${DB_WAIT_TIMEOUT}s." >&2
        echo "Verifique DNS, firewall, credenciais e se o banco central do ambiente foi criado pela infraestrutura." >&2
        exit 1
    fi

    echo "[wait-for-db] tentando novamente em ${DB_WAIT_INTERVAL}s (${elapsed}s/${DB_WAIT_TIMEOUT}s)..."
    sleep "$DB_WAIT_INTERVAL"
    elapsed=$((elapsed + DB_WAIT_INTERVAL))
done
