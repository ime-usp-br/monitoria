import re

import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.manifest import BASE_URL, pdf_fixture

DATE_FIELDS = (
    "started_at",
    "finished_at",
    "start_date_evaluations",
    "end_date_evaluations",
    "start_date_requisitions",
    "end_date_requisitions",
    "start_date_enrollments",
    "end_date_enrollments",
)


def _term_form_data(page):
    data = {field: page.input_value(f"#{field}") for field in DATE_FIELDS}
    data["year"] = page.input_value("#year")
    data["max_enrollments"] = page.input_value("#max_enrollments")
    data["period"] = page.input_value("#period")
    data["status"] = page.input_value("#status")
    return data


@pytest.mark.p0
def test_index_lista_periodo_aberto(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms")

    term = manifest["terms"]["open"]
    expect(page.get_by_role("heading", name="Período Letivo")).to_be_visible()
    expect(page.locator("table")).to_contain_text(str(term["year"]))
    expect(page.locator("table")).to_contain_text(term["period"])


@pytest.mark.p0
def test_cria_periodo_fechado_com_edital(page_as, manifest):
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

    row = db.fetch_one("select id, status from school_terms where year = %s and period = %s", (2030, "2° Semestre"))
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


@pytest.mark.p0
def test_cen_schoolterm_003_inicio_depois_do_fim_rejeitado(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")
    response = http.submit(
        page,
        "POST",
        "/schoolterms",
        {
            "year": "2032",
            "period": "1° Semestre",
            "status": "Fechado",
            "max_enrollments": "5",
            "started_at": "01/07/2032",
            "finished_at": "01/03/2032",
            "start_date_evaluations": "01/05/2032",
            "end_date_evaluations": "30/05/2032",
            "start_date_requisitions": "01/07/2032",
            "end_date_requisitions": "01/01/2032",
            "start_date_enrollments": "01/07/2032",
            "end_date_enrollments": "01/01/2032",
        },
        files={"public_notice": pdf_fixture("edital.pdf")},
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "must be a date before" in body
    assert db.fetch_one("select id from school_terms where year = %s", (2032,)) is None


@pytest.mark.p0
def test_cen_schoolterm_005_validacoes_do_formulario(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")
    response = http.submit(
        page,
        "POST",
        "/schoolterms",
        {
            "year": "abc",
            "period": "3° Semestre",
            "status": "Indefinido",
            "max_enrollments": "0",
            "started_at": "32/13/2033",
            "finished_at": "31/02/2033",
            "start_date_evaluations": "01/05/2033",
            "end_date_evaluations": "30/05/2033",
            "start_date_requisitions": "01/01/2033",
            "end_date_requisitions": "31/01/2033",
            "start_date_enrollments": "01/02/2033",
            "end_date_enrollments": "28/02/2033",
        },
        files={"public_notice": pdf_fixture("edital.pdf")},
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "year must be a number" in body
    assert "selected period is invalid" in body
    assert "selected status is invalid" in body
    assert "max enrollments must be greater than 0" in body
    assert db.fetch_one("select id from school_terms where year = 2033") is None
    # Encerra o período de criação sem criar registros residuais.


@pytest.mark.p0
def test_cen_schoolterm_006_edital_armazenado_sob_pasta_do_periodo(page_as, temp):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")
    response = http.submit(
        page,
        "POST",
        "/schoolterms",
        {
            "year": "2034",
            "period": "2° Semestre",
            "status": "Fechado",
            "max_enrollments": "5",
            "started_at": "01/08/2034",
            "finished_at": "15/12/2034",
            "start_date_evaluations": "01/11/2034",
            "end_date_evaluations": "30/11/2034",
            "start_date_requisitions": "01/06/2034",
            "end_date_requisitions": "30/06/2034",
            "start_date_enrollments": "01/07/2034",
            "end_date_enrollments": "31/07/2034",
        },
        files={"public_notice": pdf_fixture("edital.pdf")},
    )
    http.goto_response(page, response)
    row = db.fetch_one("select id, public_notice_file_path from school_terms where year = %s", (2034,))
    assert row is not None
    temp.append(("delete from school_terms where id=%s", (row["id"],)))
    assert row["public_notice_file_path"].startswith("20342/")


@pytest.mark.p1
def test_cen_schoolterm_007_edital_nao_pdf_rejeitado(page_as, tmp_path):
    arquivo = tmp_path / "edital.txt"
    arquivo.write_text("não é pdf", encoding="utf-8")

    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")
    response = http.submit(
        page,
        "POST",
        "/schoolterms",
        {
            "year": "2035",
            "period": "1° Semestre",
            "status": "Fechado",
            "max_enrollments": "5",
            "started_at": "01/03/2035",
            "finished_at": "30/06/2035",
            "start_date_evaluations": "01/05/2035",
            "end_date_evaluations": "30/05/2035",
            "start_date_requisitions": "01/01/2035",
            "end_date_requisitions": "31/01/2035",
            "start_date_enrollments": "01/02/2035",
            "end_date_enrollments": "28/02/2035",
        },
        files={"public_notice": arquivo},
    )
    http.goto_response(page, response)
    assert "public notice must be a file of type: application/pdf" in page.locator("body").inner_text()
    assert db.fetch_one("select id from school_terms where year = 2035") is None


@pytest.mark.p1
def test_cen_schoolterm_008_edital_acima_de_1000kb_rejeitado(page_as, tmp_path):
    arquivo = tmp_path / "edital_grande.pdf"
    arquivo.write_bytes(b"%PDF-1.4\n" + b"0" * 1_100_000)

    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/create")
    response = http.submit(
        page,
        "POST",
        "/schoolterms",
        {
            "year": "2036",
            "period": "1° Semestre",
            "status": "Fechado",
            "max_enrollments": "5",
            "started_at": "01/03/2036",
            "finished_at": "30/06/2036",
            "start_date_evaluations": "01/05/2036",
            "end_date_evaluations": "30/05/2036",
            "start_date_requisitions": "01/01/2036",
            "end_date_requisitions": "31/01/2036",
            "start_date_enrollments": "01/02/2036",
            "end_date_enrollments": "28/02/2036",
        },
        files={"public_notice": arquivo},
    )
    http.goto_response(page, response)
    assert "public notice must not be greater than 1000 kilobytes" in page.locator("body").inner_text()
    assert db.fetch_one("select id from school_terms where year = 2036") is None


@pytest.mark.p0
def test_cen_schoolterm_009_atualizar_para_aberto_com_outro_aberto_bloqueado(page_as, temp):
    term_id = state.add_term(temp, year=2037, period="1° Semestre", status="Fechado")

    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/{term_id}/edit")
    data = _term_form_data(page)
    data["status"] = "Aberto"
    response = http.submit(page, "PATCH", f"/schoolterms/{term_id}", data)
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("consta com estado em aberto")
    assert db.scalar("select status from school_terms where id=%s", (term_id,)) == "Fechado"


@pytest.mark.p0
def test_cen_schoolterm_010_atualizar_proprio_periodo_aberto_permitido(page_as, manifest):
    term = manifest["terms"]["open"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/{term['id']}/edit")
    data = _term_form_data(page)
    data["status"] = "Aberto"
    response = http.submit(page, "PATCH", f"/schoolterms/{term['id']}", data, follow=True)
    assert response.status == 200
    assert db.scalar("select status from school_terms where id=%s", (term["id"],)) == "Aberto"


@pytest.mark.p1
def test_cen_schoolterm_012_substituir_edital_na_atualizacao(page_as, temp):
    term_id = state.add_term(temp, year=2038, period="2° Semestre", status="Fechado")
    before = db.scalar("select public_notice_file_path from school_terms where id=%s", (term_id,))

    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms/{term_id}/edit")
    data = _term_form_data(page)
    response = http.submit(
        page, "PATCH", f"/schoolterms/{term_id}", data, files={"public_notice": pdf_fixture("edital.pdf")}
    )
    http.goto_response(page, response)
    after = db.scalar("select public_notice_file_path from school_terms where id=%s", (term_id,))
    assert after != before
    assert after.startswith("20382/")


@pytest.mark.p0
def test_cen_schoolterm_014_baixar_edital_com_caminho_valido(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms")

    term = manifest["terms"]["open"]
    row = (
        page.locator("table tr")
        .filter(has_text=term["period"])
        .filter(has_text=str(term["year"]))
    )
    with page.expect_download() as download_info:
        row.get_by_role("button", name="Download").click()
    download = download_info.value
    assert download.suggested_filename == "edital_monitoria.pdf"


@pytest.mark.p1
def test_cen_schoolterm_015_baixar_edital_com_caminho_inexistente_falha(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms")
    response = http.submit(page, "POST", "/schoolterms/download", {"path": "99999/inexistente.pdf"})
    http.goto_response(page, response)
    assert "O arquivo não foi encontrado no servidor" in page.locator("body").inner_text()


@pytest.mark.p1
def test_cen_schoolterm_017_index_ordena_periodos_decrescente(page_as, temp):
    state.add_term(temp, year=2040, period="1° Semestre")
    state.add_term(temp, year=2039, period="2° Semestre")

    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolterms")
    years = [
        int(re.sub(r"\D", "", text) or 0)
        for text in page.locator("table tr td:first-child").all_inner_texts()
    ]
    assert years == sorted(years, reverse=True)


@pytest.mark.p2
def test_cen_schoolterm_018_sem_permissao_negado(page_as, manifest):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/schoolterms/create").status == 403
    term = manifest["terms"]["open"]
    assert page.goto(f"{BASE_URL}/schoolterms/{term['id']}/edit").status == 403
    page.goto(BASE_URL)
    response = http.submit(page, "DELETE", f"/schoolterms/{term['id']}")
    assert response.status == 403
