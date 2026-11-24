#!/usr/bin/env bash
set -euo pipefail

matches="$(grep -RInE '<(script|link)[^>]*(src|href)="(https?:)?//' resources/views || true)"

if [ -n "$matches" ]; then
    echo "Assets externos encontrados em Blade; empacote-os via Vite:" >&2
    echo "$matches" >&2
    exit 1
fi

echo "Nenhum asset externo em resources/views."
