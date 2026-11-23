import pytest
from playwright.sync_api import expect

from support.db import fetch_one
from support.manifest import BASE_URL, pdf_fixture


@pytest.mark.p0
def test_index_lista_periodo_aberto(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms")

    term = manifest["terms"]["open"]
    expect(page.get_by_role("heading", name="Período Letivo")).to_be_visible()
    expect(page.locator("table")).to_contain_text(str(term["year"]))
    expect(page.locator("table")).to_contain_text(term["period"])


@pytest.mark.p0
def test_cria_periodo_fechado_com_edital(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")

    page.fill("#year", "2030")
    page.select_option("#period", "2° Semestre")
    page.select_option("#status", "Fechado")
    page.fill("#max_enrollments", "5")
    page.fill("#started_at", "01/08/2030")
    page.fill("#finished_at", "15/12/2030")
    page.fill("#start_date_evaluations", "01/11/2030")
    page.fill("#end_date_evaluations", "30/11/2030")
    page.fill("#start_date_requisitions", "01/06/2030")
    page.fill("#end_date_requisitions", "30/06/2030")
    page.fill("#start_date_enrollments", "01/07/2030")
    page.fill("#end_date_enrollments", "31/07/2030")
    page.set_input_files("#public_notice_file", str(pdf_fixture("edital.pdf")))
    page.get_by_role("button", name="Cadastrar").click()

    page.wait_for_url(f"{BASE_URL}/schoolterms")
    expect(page.locator("table")).to_contain_text("2030")

    row = fetch_one("select id, status from school_terms where year = %s and period = %s", (2030, "2° Semestre"))
    assert row is not None
    assert row["status"] == "Fechado"


@pytest.mark.p1
def test_bloqueia_segundo_periodo_aberto(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")

    page.fill("#year", "2029")
    page.select_option("#period", "1° Semestre")
    page.select_option("#status", "Aberto")
    page.fill("#max_enrollments", "5")
    page.fill("#started_at", "01/03/2029")
    page.fill("#finished_at", "30/06/2029")
    page.fill("#start_date_evaluations", "01/05/2029")
    page.fill("#end_date_evaluations", "30/05/2029")
    page.fill("#start_date_requisitions", "01/01/2029")
    page.fill("#end_date_requisitions", "31/01/2029")
    page.fill("#start_date_enrollments", "01/02/2029")
    page.fill("#end_date_enrollments", "28/02/2029")
    page.set_input_files("#public_notice_file", str(pdf_fixture("edital.pdf")))
    page.get_by_role("button", name="Cadastrar").click()

    expect(page.locator("body")).to_contain_text("consta com estado em aberto")
