import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p0
def test_admin_login_exibe_menu_administrativo(page_as):
    page = page_as("admin")
    expect(page.get_by_role("menuitem", name="Períodos Letivos")).to_be_visible()
    expect(page.get_by_role("menuitem", name="Usuários")).to_be_visible()


@pytest.mark.p0
def test_aluno_login_exibe_menu_de_inscricao(page_as):
    page = page_as("aluno_com_historico")
    expect(page.get_by_role("link", name="Fazer Inscrição")).to_be_visible()
    expect(page.get_by_role("link", name="Auto Avaliação")).to_be_visible()


@pytest.mark.p1
def test_servidor_sem_papel_recebe_403(page_as):
    page = page_as("servidor_sem_papel")
    response = page.goto(f"{BASE_URL}/schoolterms")
    assert response is not None
    assert response.status == 403
