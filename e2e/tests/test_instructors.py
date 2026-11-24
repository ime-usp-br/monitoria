import pytest
from playwright.sync_api import expect

from support import http
from support.manifest import BASE_URL


@pytest.mark.p1
def test_cen_instructor_001_busca_json_por_codpes(page_as, manifest):
    page = page_as("admin")
    response = http.ajax_get(page, "/instructors", {"codpes": "20001"})
    assert response.status == 200
    assert response.json() == "Docente MAC E2E"


@pytest.mark.p1
def test_cen_instructor_002_busca_json_sem_vinculo_docente(page_as):
    page = page_as("admin")
    response = http.ajax_get(page, "/instructors", {"codpes": "10003"})
    assert response.status == 200
    assert response.json() == ""


@pytest.mark.p0
def test_cen_instructor_003_index_ordena_por_solicitacoes(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/instructors")
    expect(page.get_by_role("heading", name="Docentes")).to_be_visible()

    body = page.locator("body").inner_text()
    assert "Docente MAC E2E" in body
    assert body.index("Docente MAC E2E") < body.index("Docente Sem Turma E2E")


@pytest.mark.p1
def test_cen_instructor_004_view_de_requisicoes(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/instructors/{manifest['instructors']['20001']}/requisitions")
    expect(page.get_by_role("heading", name="Solicitações de Monitores")).to_be_visible()
    assert "MAC0101" in page.locator("body").inner_text()


@pytest.mark.p1
def test_cen_instructor_005_busca_por_codpes(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/instructors/search?codpes=20001")
    body = page.locator("body").inner_text()
    assert "Docente MAC E2E" in body
    assert "Membro Comissao MAC E2E" not in body
