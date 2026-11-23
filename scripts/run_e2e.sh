#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

COMPOSE=(docker compose -p monitoria_e2e -f docker-compose.e2e.yml)
KEEP_STACK="${E2E_KEEP_STACK:-0}"

cleanup() {
    if [[ "$KEEP_STACK" == "1" ]]; then
        echo "[run_e2e] E2E_KEEP_STACK=1: stack mantida para depuração."
        return
    fi
    "${COMPOSE[@]}" down -v --remove-orphans >/dev/null 2>&1 || true
}

trap cleanup EXIT

echo "[run_e2e] limpando execução anterior..."
"${COMPOSE[@]}" down -v --remove-orphans >/dev/null 2>&1 || true

echo "[run_e2e] subindo db, senhaunica-faker, mailpit, app, web e seed..."
"${COMPOSE[@]}" up -d --build --wait db senhaunica mailpit app web seed

echo "[run_e2e] executando pytest..."
"${COMPOSE[@]}" run --rm e2e pytest -q tests -c pytest.ini "$@"
