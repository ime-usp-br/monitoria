import re

from playwright.sync_api import Page

from support import db
from support.manifest import BASE_URL, load_manifest

_MANIFEST = load_manifest()


def restore_identity(codpes: str) -> None:
    """Restaura nome/e-mail do seed após o faker reescrever o usuário no OAuth."""
    codpes = str(codpes)
    user = next((item for item in _MANIFEST["users"].values() if str(item["codpes"]) == codpes), None)
    if user and user.get("email"):
        db.execute(
            "update users set name=%s, email=%s where codpes=%s",
            (user["name"], user["email"], codpes),
        )


def login_as(page: Page, codpes: str) -> None:
    """Executa o fluxo OAuth completo contra o senhaunica-faker."""
    codpes = str(codpes)
    page.goto(f"{BASE_URL}/login", wait_until="domcontentloaded")
    page.wait_for_url(re.compile(r".*wsusuario/oauth/authorize.*"), timeout=20000)

    page.fill("#callback", f"{BASE_URL}/callback")
    page.fill("#loginUsuario", codpes)
    page.get_by_role("button", name="Login").click()

    page.wait_for_url(
        lambda url: url.startswith(BASE_URL) and "/wsusuario/" not in url,
        timeout=30000,
    )
    restore_identity(codpes)
    page.wait_for_load_state("domcontentloaded")
