import json
import os
from pathlib import Path

BASE_URL = os.environ.get("E2E_BASE_URL", "http://web").rstrip("/")
MANIFEST_PATH = Path(os.environ.get("E2E_MANIFEST", "/e2e-artifacts/seed.json"))

FIXTURES_DIR = Path(__file__).resolve().parent.parent / "fixtures"


def load_manifest() -> dict:
    if not MANIFEST_PATH.exists():
        raise RuntimeError(f"Manifesto E2E não encontrado em {MANIFEST_PATH}")
    return json.loads(MANIFEST_PATH.read_text(encoding="utf-8"))


def pdf_fixture(name: str = "edital.pdf") -> Path:
    return FIXTURES_DIR / "pdf" / name
