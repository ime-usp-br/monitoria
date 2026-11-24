"""Cliente mínimo da API do Mailpit usado nos cenários de e-mail."""

from __future__ import annotations

import json
import os
import urllib.request

BASE = os.environ.get("E2E_MAILPIT_URL", "http://mailpit:8025").rstrip("/")


def _request(method: str, path: str, body: bytes | None = None):
    req = urllib.request.Request(
        BASE + path,
        data=body,
        method=method,
        headers={"Content-Type": "application/json", "Accept": "application/json"},
    )
    with urllib.request.urlopen(req, timeout=10) as response:
        raw = response.read()
        if not raw:
            return None
        try:
            return json.loads(raw)
        except json.JSONDecodeError:
            return raw.decode("utf-8", "replace")


def clear() -> None:
    try:
        _request("DELETE", "/api/v1/messages")
    except urllib.error.HTTPError:
        pass


def messages() -> list[dict]:
    payload = _request("GET", "/api/v1/messages?limit=200")
    return payload.get("messages", []) if payload else []


def message(message_id: str) -> dict:
    return _request("GET", f"/api/v1/message/{message_id}")


def wait_for(predicate, timeout: float = 15.0, interval: float = 0.25):
    import time

    deadline = time.monotonic() + timeout
    last = []
    while time.monotonic() < deadline:
        last = messages()
        for item in last:
            if predicate(item):
                return item
        time.sleep(interval)
    raise AssertionError(f"Nenhum e-mail correspondeu ao filtro. Recebidos: {last}")


def by_subject(fragment: str, timeout: float = 15.0) -> dict:
    return wait_for(lambda item: fragment.lower() in (item.get("Subject") or "").lower(), timeout)


def recipients(message_id: str) -> list[str]:
    detail = message(message_id)
    result = []
    for key in ("To", "Cc", "Bcc"):
        for address in detail.get(key) or []:
            result.append(address.get("Address", ""))
    return [item for item in result if item]
