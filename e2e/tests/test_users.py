import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p1
def test_admin_lista_usuarios(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/users")

    expect(page.get_by_role("heading", name="Usuários")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Administrador")
    expect(page.locator("body")).to_contain_text("160002")


@pytest.mark.p2
def test_secretaria_acessa_usuarios(page_as):
    page = page_as("secretaria")
    response = page.goto(f"{BASE_URL}/users")
    assert response is not None
    assert response.status == 200
    expect(page.get_by_role("heading", name="Usuários")).to_be_visible()
