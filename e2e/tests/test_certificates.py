import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p0
def test_admin_lista_atestados_do_periodo_aberto(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/certificates")

    expect(page.get_by_role("heading", name="Atestado de Monitoria")).to_be_visible()
    table = page.locator("table")
    expect(table).to_contain_text("Aluno Monitor Ativo E2E")
    expect(table.get_by_role("link", name="Emitir Atestado").first).to_be_visible()


@pytest.mark.p1
def test_aluno_sem_monitoria_recebe_aviso(page_as):
    page = page_as("aluno_sem_historico")
    page.goto(f"{BASE_URL}/certificates")
    expect(page.locator("body")).to_contain_text("Você não realizou nenhuma monitoria")
