import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p1
def test_admin_acessa_tela_de_relatorios(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/reports")

    expect(page.locator("body")).to_contain_text("Relatório")
    expect(page.locator("body")).to_contain_text(str(manifest["terms"]["open"]["year"]))


@pytest.mark.p2
def test_aluno_nao_gera_relatorio(page_as):
    page = page_as("aluno_com_historico")
    response = page.goto(f"{BASE_URL}/reports")
    assert response is not None
    assert response.status == 403
