"""Submissões HTTP autenticadas pela sessão do navegador.

Os cenários de regra (validações, guardas, escopo por papel) não têm uma tela
própria para cada micro-interação. Em vez de reimplementar o formulário inteiro
no Playwright — o que duplicaria a regra e ficaria frágil — o helper envia a
requisição real pela mesma sessão OAuth do navegador, com o token CSRF da
página, e depois abre a URL de destino para validar o flash/erro renderizado.

Isso mantém o teste híbrido: a pré-condição vem do seed/banco, a ação passa
pela pilha HTTP real (middleware, request, controller, banco) e a asserção
principal é feita no navegador.
"""

from __future__ import annotations

from pathlib import Path
from typing import Any, Mapping

from playwright.sync_api import Page

from support.manifest import BASE_URL


def csrf_token(page: Page) -> str:
    token = page.locator('meta[name="csrf-token"]').get_attribute("content")
    if not token:
        raise RuntimeError("Token CSRF não encontrado na página")
    return token


def _absolute(path: str) -> str:
    if path.startswith("http://") or path.startswith("https://"):
        return path
    if not path.startswith("/"):
        path = "/" + path
    return BASE_URL + path


def _multipart_value(value: Any):
    if isinstance(value, Path):
        return {
            "name": value.name,
            "mimeType": "application/pdf",
            "buffer": value.read_bytes(),
        }
    return value


def submit(
    page: Page,
    method: str,
    path: str,
    data: Mapping[str, Any] | None = None,
    files: Mapping[str, Path] | None = None,
    follow: bool = False,
):
    """Executa uma ação HTTP na sessão atual.

    ``files`` envia multipart; ``data`` pode conter listas que são repetidas
    como campos ``nome[]``. Retorna a resposta do Playwright. Quando
    ``follow=False`` o redirect não é seguido e o flash permanece na sessão
    para ser consumido por ``goto_response``.
    """
    method = method.upper()
    payload: dict[str, Any] = dict(data or {})
    payload["_token"] = csrf_token(page)

    has_files = bool(files)
    if method != "GET":
        payload["_method"] = method

    max_redirects = 20 if follow else 0
    if has_files:
        multipart: dict[str, Any] = {}
        for key, value in payload.items():
            if isinstance(value, (list, tuple)):
                multipart[key] = [str(item) for item in value]
            else:
                multipart[key] = str(value)
        for key, value in (files or {}).items():
            multipart[key] = _multipart_value(Path(value))
        return page.request.post(
            _absolute(path), multipart=multipart, max_redirects=max_redirects
        )

    return page.request.post(
        _absolute(path), form=payload, max_redirects=max_redirects
    )


def location_of(response) -> str:
    location = response.headers.get("location")
    if not location:
        raise AssertionError(
            f"Esperava redirect com Location, recebeu {response.status}"
        )
    return location


def goto_response(page: Page, response):
    """Abre o destino do redirect para consumir e renderizar o flash."""
    return page.goto(location_of(response), wait_until="domcontentloaded")


def follow_redirect(page: Page, response) -> None:
    target = location_of(response)
    page.goto(target, wait_until="domcontentloaded")


def expect_status(response, status: int) -> None:
    assert response.status == status, (
        f"Esperava HTTP {status}, recebeu {response.status}: "
        f"{response.text()[:500]}"
    )


def get_json(page: Page, path: str, params: Mapping[str, Any] | None = None):
    return page.request.get(_absolute(path), params=dict(params or {}))


def ajax_get(page: Page, path: str, params: Mapping[str, Any] | None = None):
    return page.request.get(
        _absolute(path),
        params=dict(params or {}),
        headers={"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
    )


def ajax_post(page: Page, path: str, data: Mapping[str, Any] | None = None):
    payload = dict(data or {})
    payload["_token"] = csrf_token(page)
    return page.request.post(
        _absolute(path),
        form=payload,
        headers={"Accept": "application/json", "X-Requested-With": "XMLHttpRequest"},
    )


def unique(prefix: str = "E2E") -> str:
    """Sufixo curto e único para dados criados por um teste."""
    import uuid

    return f"{prefix}-{uuid.uuid4().hex[:10]}"


def get(page: Page, path: str, params: Mapping[str, Any] | None = None):
    """GET autenticado que devolve a resposta sem seguir redirects."""
    return page.request.get(
        _absolute(path), params=dict(params or {}), max_redirects=0
    )
