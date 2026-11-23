import base64

import pytest
from playwright.sync_api import expect

from support import db, http
from support.manifest import BASE_URL


@pytest.mark.p0
def test_cen_report_001_index_exige_permissao(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/reports").status == 403


@pytest.mark.p1
def test_cen_report_002_index_lista_periodos(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/reports")
    expect(page.get_by_role("heading", name="Relatório")).to_be_visible()
    options = page.locator("#periodoId option").all_inner_texts()
    assert any(str(manifest["terms"]["open"]["year"]) in option for option in options)
    assert any(manifest["terms"]["open"]["period"] in option for option in options)


@pytest.mark.p0
def test_cen_report_003_make_gera_pdf(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/reports")
    response = http.submit(
        page, "POST", "/reports/make", {"periodoId": str(manifest["terms"]["open"]["id"])}
    )
    assert response.status == 200
    assert "pdf" in (response.headers.get("content-type") or "")
    assert response.body().startswith(b"%PDF")


@pytest.mark.p1
def test_cen_report_004_make_sem_permissao(page_as, manifest):
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page, "POST", "/reports/make", {"periodoId": str(manifest["terms"]["open"]["id"])}
    )
    assert response.status == 403


@pytest.mark.p0
def test_cen_report_005_external_valida_token_e_parametros(anonymous_page, manifest):
    term = manifest["terms"]["open"]

    response = anonymous_page.request.get(
        f"{BASE_URL}/reports/external", params={"token": "errado", "ano": term["year"], "periodo": "2"}
    )
    assert response.json()["status"] is False
    assert response.json()["message"] == "Token incorreto."

    response = anonymous_page.request.get(
        f"{BASE_URL}/reports/external", params={"token": "e2e-report-token"}
    )
    assert response.json()["message"] == "Não foi encontrado o ano."

    response = anonymous_page.request.get(
        f"{BASE_URL}/reports/external", params={"token": "e2e-report-token", "ano": term["year"]}
    )
    assert response.json()["message"] == "Não foi encontrado o periodo."


@pytest.mark.p0
def test_cen_report_006_external_sem_sessao_gera_relatorio(anonymous_page, manifest):
    term = manifest["terms"]["open"]
    response = anonymous_page.request.get(
        f"{BASE_URL}/reports/external",
        params={"token": "e2e-report-token", "ano": term["year"], "periodo": "2"},
        timeout=120000,
    )
    payload = response.json()
    assert payload["message"] == "Relatório gerado com sucesso."
    assert base64.b64decode(payload["report"]).startswith(b"%PDF")


@pytest.mark.p1
def test_cen_report_007_external_periodo_inexistente(anonymous_page):
    response = anonymous_page.request.get(
        f"{BASE_URL}/reports/external",
        params={"token": "e2e-report-token", "ano": "1900", "periodo": "1"},
    )
    payload = response.json()
    assert payload["status"] is False
    assert "não foi encontrado um semestre" in payload["message"].lower()
