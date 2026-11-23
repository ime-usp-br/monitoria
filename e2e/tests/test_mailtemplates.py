import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p0
def test_admin_lista_modelos_de_email(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")

    expect(page.get_by_role("heading", name="E-mails")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Notificar aluno selecionado")
    expect(page.locator("body")).to_contain_text("Solicitação de certificado")


@pytest.mark.p1
def test_admin_acessa_area_de_disparos(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails")
    expect(page.locator("body")).to_contain_text("E-mails")


@pytest.mark.p1
def test_docente_nao_edita_modelos(page_as):
    page = page_as("docente")
    response = page.goto(f"{BASE_URL}/mailtemplates")
    assert response is not None
    assert response.status == 403
