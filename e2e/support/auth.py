import re

from playwright.sync_api import Page

from support.manifest import BASE_URL


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
    page.wait_for_load_state("domcontentloaded")
