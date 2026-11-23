#!/bin/bash
set -euo pipefail

# Gera e aplica o schema/fixtures mínimos do Replicado no ASE falso.
# As datas/turmas acompanham o período letivo usado pelo seed da aplicação.

YEAR="$(date +%Y)"
MONTH="$(date +%m)"
if [ "$MONTH" -le 6 ]; then
    PERIOD="1"
    CLOSED_YEAR="$((YEAR - 1))"
    CLOSED_PERIOD="2"
else
    PERIOD="2"
    CLOSED_YEAR="$YEAR"
    CLOSED_PERIOD="1"
fi

export YEAR PERIOD CLOSED_YEAR CLOSED_PERIOD

sed \
    -e "s/__YEAR__/${YEAR}/g" \
    -e "s/__PERIOD__/${PERIOD}/g" \
    -e "s/__CLOSED_YEAR__/${CLOSED_YEAR}/g" \
    -e "s/__CLOSED_PERIOD__/${CLOSED_PERIOD}/g" \
    /fixtures/replicado.sql.tmpl > /tmp/replicado.sql

set +u
source /opt/sybase/sap/SYBASE.sh
set -u

echo "[replicado-init] aguardando ASE..."
for i in $(seq 1 60); do
    if isql -Usa -PSybase123456789 -SSYBASE -b -Q "select 1" >/dev/null 2>&1; then
        echo "[replicado-init] ASE pronto."
        break
    fi
    if [ "$i" -eq 60 ]; then
        echo "[replicado-init] ASE não respondeu a tempo." >&2
        exit 1
    fi
    sleep 2
done

echo "[replicado-init] aplicando schema e fixtures..."
isql -Usa -PSybase123456789 -SSYBASE -b -i /tmp/replicado.sql

echo "[replicado-init] verificando fixtures..."
isql -Usa -PSybase123456789 -SSYBASE -b -Q "select count(*) from replicado.dbo.PESSOA"

echo "[replicado-init] concluído."
