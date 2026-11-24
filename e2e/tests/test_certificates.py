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


@pytest.mark.p0
def test_cen_certificate_001_admin_lista_nao_desligados(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/certificates")
    body = page.locator("body").inner_text()
    assert "Aluno Monitor Ativo E2E" in body
    assert "Aluno Sem Historico E2E" not in body


@pytest.mark.p0
def test_cen_certificate_002_aluno_lista_apenas_proprias(page_as):
    page = page_as("aluno_monitor")
    page.goto(f"{BASE_URL}/certificates")
    body = page.locator("body").inner_text()
    assert "MAC0202" in body
    assert "MAC0303" not in body

    page = page_as("aluno_concluido")
    page.goto(f"{BASE_URL}/certificates")
    body = page.locator("body").inner_text()
    assert "MAC0303" in body
    assert "MAC0202" not in body


@pytest.mark.p2
def test_cen_certificate_003_sem_papel_e_sem_historico_negado(page_as):
    page = page_as("servidor_sem_papel")
    assert page.goto(f"{BASE_URL}/certificates").status == 403


@pytest.mark.p1
def test_cen_certificate_004_aluno_sem_monitoria_avisa(page_as):
    page = page_as("aluno_sem_historico")
    page.goto(f"{BASE_URL}/certificates")
    expect(page.locator("body")).to_contain_text("Você não realizou nenhuma monitoria")


@pytest.mark.p0
def test_cen_certificate_005_make_exige_propriedade(page_as, manifest):
    page = page_as("aluno_com_historico")
    response = page.goto(f"{BASE_URL}/certificates/make/{manifest['selections']['ativo']['id']}")
    assert response.status == 403


@pytest.mark.p0
def test_cen_certificate_006_make_concluido_gera_pdf(page_as, manifest):
    page = page_as("admin")
    response = page.request.get(f"{BASE_URL}/certificates/make/{manifest['selections']['concluido']['id']}")
    assert response.status == 200
    assert "pdf" in (response.headers.get("content-type") or "")
    assert response.body().startswith(b"%PDF")
    assert "atestado_160004.pdf" in (response.headers.get("content-disposition") or "")


@pytest.mark.p0
def test_cen_certificate_007_make_ativo_gera_pdf(page_as, manifest):
    page = page_as("admin")
    response = page.request.get(f"{BASE_URL}/certificates/make/{manifest['selections']['ativo']['id']}")
    assert response.status == 200
    assert "pdf" in (response.headers.get("content-type") or "")
    assert response.body().startswith(b"%PDF")
    assert "atestado_160002.pdf" in (response.headers.get("content-disposition") or "")
