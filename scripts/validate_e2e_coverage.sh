#!/usr/bin/env bash
# Valida a matriz E2E: 244 CEN-*, todos implemented, e cada nodeid executável.
set -euo pipefail

root="$(cd "$(dirname "$0")/.." && pwd)"
coverage="$root/e2e/coverage.yaml"
errors=0

if [[ ! -f "$coverage" ]]; then
  echo "coverage.yaml não encontrado: $coverage" >&2
  exit 1
fi

total="$(grep -c '^  - cen: CEN-' "$coverage" || true)"
legacy="$(grep -c '^  - cen: CEN-UI-LEGACY-' "$coverage" || true)"
core=$((total - legacy))
implemented="$(grep -c '^    status: implemented$' "$coverage" || true)"
planned="$(grep -c '^    status: planned$' "$coverage" || true)"

echo "CENs: $core; UI legada: $legacy; implemented: $implemented; planned: $planned"
if [[ "$core" -ne 244 ]]; then
  echo "ERRO: esperados 244 CENs, encontrados $core" >&2
  errors=$((errors + 1))
fi
if [[ "$legacy" -ne 10 ]]; then
  echo "ERRO: esperados 10 CEN-UI-LEGACY, encontrados $legacy" >&2
  errors=$((errors + 1))
fi
if [[ "$planned" -ne 0 ]]; then
  echo "ERRO: há $planned cenário(s) ainda como planned" >&2
  errors=$((errors + 1))
fi

pairs="$(mktemp)"
trap 'rm -f "$pairs"' EXIT

awk '
  /^  - cen: / { cen=$3 }
  /^    nodeid: "/ {
    line=$0
    sub(/^    nodeid: "/, "", line)
    sub(/"$/, "", line)
    print cen "\t" line
  }
' "$coverage" > "$pairs"

while IFS=$'\t' read -r cen nodeid; do
  id="${nodeid%\[chromium\]}"
  file="${id%%::*}"
  test="${id#*::}"

  if [[ ! -f "$root/$file" && ! -f "$root/e2e/$file" ]]; then
    echo "ERRO: $cen aponta para arquivo inexistente: $file" >&2
    errors=$((errors + 1))
    continue
  fi

  if [[ "$file" == tests/Feature/* ]]; then
    path="$root/$file"
    needle="function $test"
  else
    path="$root/e2e/$file"
    needle="def $test"
  fi

  if ! grep -Fq "$needle" "$path"; then
    echo "ERRO: $cen aponta para teste inexistente: $file::$test" >&2
    errors=$((errors + 1))
  fi
done < "$pairs"

if [[ "$errors" -ne 0 ]]; then
  echo "FALHA: $errors problema(s) de rastreabilidade" >&2
  exit 1
fi

echo "OK: 244 CENs com nodeids executáveis."
