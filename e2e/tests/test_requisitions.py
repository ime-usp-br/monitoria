import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p0
def test_docente_ve_suas_turmas_no_periodo_de_solicitacao(page_as):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/requisitions")

    expect(page.get_by_role("heading", name="Solicitação de Monitores")).to_be_visible()
    expect(page.locator("body")).to_contain_text("MAC0101")


@pytest.mark.p1
def test_aluno_nao_acessa_solicitacoes(page_as):
    page = page_as("aluno_com_historico")
    response = page.goto(f"{BASE_URL}/requisitions")
    assert response is not None
    assert response.status == 403
