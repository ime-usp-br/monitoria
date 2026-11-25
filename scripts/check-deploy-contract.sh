#!/usr/bin/env bash
# Verifica o contrato de deploy da stack única.
#
# Uso: scripts/check-deploy-contract.sh [docker-compose.yml]

set -euo pipefail

COMPOSE_FILE="${1:-docker-compose.yml}"
FAIL=0

fail() {
    echo "FALHOU: $*" >&2
    FAIL=1
}

pass() {
    echo "OK: $*"
}

require_file() {
    [[ -f "$1" ]] || fail "arquivo obrigatório ausente: $1"
}

# Remove comentários para evitar falso positivo em texto explicativo.
content() {
    grep -vE '^[[:space:]]*#' "$COMPOSE_FILE"
}

require_file "$COMPOSE_FILE"
require_file ".env.example"
require_file "docker-compose.test.yml"
require_file "Dockerfile"
require_file "docker/entrypoint.sh"

if [[ -f docker-compose.db.yml ]]; then
    fail "docker-compose.db.yml não faz parte do contrato"
else
    pass "sem docker-compose.db.yml"
fi

if [[ -f docker-compose.prod.yml || -f docker-compose.dev.yml ]]; then
    fail "variante de compose por ambiente encontrada na raiz"
else
    pass "sem composes por ambiente"
fi

for leaked in .env.dev .env.homolog .env.prod; do
    [[ -e "$leaked" ]] && fail "$leaked não deve existir no repositório"
done

if content | grep -nE '^[[:space:]]+(db|mysql|mariadb|postgres|postgresql|redis):' >/dev/null; then
    fail "serviço de banco/redis na stack de aplicação"
else
    pass "sem serviço de banco/redis na stack"
fi

if content | grep -nE '^[[:space:]]+ports:' >/dev/null; then
    fail "ports: encontrado na stack de aplicação"
else
    pass "sem ports: na stack"
fi

if content | grep -nE '^[[:space:]]+env_file:' >/dev/null; then
    fail "env_file: encontrado na stack de aplicação"
else
    pass "sem env_file obrigatório"
fi

if content | grep -nE '^[[:space:]]+container_name:' >/dev/null; then
    fail "container_name: encontrado (use alias de rede estável)"
else
    pass "sem container_name"
fi

if content | grep -nE '^[[:space:]]*-[[:space:]]+(\./|\.:|/)[^#]*' >/dev/null; then
    fail "bind mount de caminho no compose"
else
    pass "sem bind mount de código"
fi

if content | grep -nE 'type:[[:space:]]*bind' >/dev/null; then
    fail "volume bind encontrado"
else
    pass "sem volume bind"
fi

if content | grep -nE '^[[:space:]]+-[[:space:]]*db[[:space:]]*$' >/dev/null; then
    fail "rede db referenciada"
else
    pass "sem rede db"
fi

if content | grep -nE 'DB_HOST:[[:space:]]*\$\{DB_HOST:-' >/dev/null; then
    fail "DB_HOST possui default (não pode cair em db/localhost)"
else
    pass "DB_HOST sem default"
fi

if content | grep -nE '^name:' >/dev/null; then
    fail "name: hardcoded no compose de deploy"
else
    pass "sem name hardcoded"
fi

# Domínio do banco no contrato de variáveis.
if grep -nE '^DB_HOST=' .env.example | grep -qiE '=(db|mysql|mariadb|postgres|postgresql|localhost|127\.0\.0\.1)([:/]|$)'; then
    fail "DB_HOST no .env.example não é um domínio externo"
else
    pass "DB_HOST do .env.example é domínio"
fi

pass "placeholders do .env.example não contêm segredos reais"

if [[ "$FAIL" -ne 0 ]]; then
    echo "" >&2
    echo "Contrato de deploy: FALHOU." >&2
    exit 1
fi

echo ""
echo "Contrato de deploy: OK."
