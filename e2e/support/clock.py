"""Relógio determinístico dos testes E2E.

O seed, a aplicação e os testes usam a mesma data congelada via
``E2E_FROZEN_NOW`` para que cenários de mês/período não dependam do dia da
execução.
"""

from __future__ import annotations

import os
from datetime import datetime
from zoneinfo import ZoneInfo

FROZEN_NOW = os.environ.get("E2E_FROZEN_NOW", "").strip()
TIMEZONE = ZoneInfo(os.environ.get("TZ", "America/Sao_Paulo"))


def now() -> datetime:
    if not FROZEN_NOW:
        return datetime.now()

    parsed = datetime.fromisoformat(FROZEN_NOW.replace("Z", "+00:00"))

    if parsed.tzinfo is None:
        return parsed

    return parsed.astimezone(TIMEZONE).replace(tzinfo=None)
