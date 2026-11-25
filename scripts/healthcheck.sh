#!/bin/sh
# Valida o endpoint real de saúde da aplicação.
#
# Uso:
#   sh scripts/healthcheck.sh
#   WEB_PORT=8080 HEALTHCHECK_PATH=/up sh scripts/healthcheck.sh
#
# Pode ser executado no container web ou em qualquer host que consiga alcançar
# a aplicação. Não publica porta e não usa credenciais.

set -eu

WEB_PORT="${WEB_PORT:-8080}"
HEALTHCHECK_PATH="${HEALTHCHECK_PATH:-/up}"
APPSERVER_URL="${HEALTHCHECK_URL:-http://127.0.0.1:${WEB_PORT}${HEALTHCHECK_PATH}}"

if command -v wget >/dev/null 2>&1; then
    if wget -qO- "$APPSERVER_URL" >/dev/null 2>&1; then
        echo "OK: $APPSERVER_URL respondeu."
        exit 0
    fi
elif command -v curl >/dev/null 2>&1; then
    if curl -fsS --max-time 5 "$APPSERVER_URL" >/dev/null; then
        echo "OK: $APPSERVER_URL respondeu."
        exit 0
    fi
else
    echo "ERRO: nem wget nem curl estão disponíveis para o healthcheck." >&2
    exit 2
fi

echo "ERRO: healthcheck falhou em $APPSERVER_URL." >&2
exit 1
