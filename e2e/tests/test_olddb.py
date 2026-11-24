import pytest
from playwright.sync_api import expect

from support import db, http
from support.manifest import BASE_URL, pdf_fixture


def _csv_line(columns: int = 18) -> str:
    values = [
        "160001",  # monitor
        "20001",  # professor
        "MAC0101",  # coddis
        "2031",  # ano
        "1",  # semestre
        "3-4",  # frequencia_meses
        "0",  # voluntario
        "1",  # student_amount
        "1",  # homework_amount
        "",  # secondary_activity
        "8",  # workload
        "",  # workload_reason
        "",  # comments
        "1",  # ease_of_contact
        "1",  # efficiency
        "1",  # reliability
        "1",  # overall
        "",  # comments_ie
    ]
    return ";".join(values[:columns])


@pytest.mark.p0
def test_cen_olddb_001_exige_administrador(page_as, tmp_path):
    arquivo = tmp_path / "importacao.csv"
    arquivo.write_text(_csv_line(18), encoding="utf-8")

    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/olddb").status == 403
    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/olddb/import", files={"file": arquivo})
    assert response.status == 403


@pytest.mark.p1
def test_cen_olddb_002_validacao_do_arquivo(page_as, tmp_path):
    invalido = pdf_fixture("edital.pdf")
    grande = tmp_path / "importacao.csv"
    grande.write_text("x" * 1_100_000, encoding="utf-8")

    page = page_as("admin")
    page.goto(f"{BASE_URL}/olddb")

    before = db.scalar(
        "select count(*) from queue_monitor where name=%s", ("App\\Jobs\\ProcessImportOldDB",)
    )
    response = http.submit(page, "POST", "/olddb/import", files={"file": invalido})
    assert response.status == 302
    assert db.scalar(
        "select count(*) from queue_monitor where name=%s", ("App\\Jobs\\ProcessImportOldDB",)
    ) == before

    page.goto(f"{BASE_URL}/olddb")
    response = http.submit(page, "POST", "/olddb/import", files={"file": grande})
    assert response.status == 302
    assert db.scalar(
        "select count(*) from queue_monitor where name=%s", ("App\\Jobs\\ProcessImportOldDB",)
    ) == before


@pytest.mark.p0
def test_cen_olddb_003_import_despacha_job(page_as, tmp_path):
    arquivo = tmp_path / "importacao.csv"
    arquivo.write_text(_csv_line(18), encoding="utf-8")

    page = page_as("admin")
    page.goto(f"{BASE_URL}/olddb")
    response = http.submit(page, "POST", "/olddb/import", files={"file": arquivo})
    assert response.status == 302

    monitor = page.request.get(f"{BASE_URL}/monitor/getImportOldDBJob").json()
    assert monitor is not None
    assert monitor["name"] == "App\\Jobs\\ProcessImportOldDB"


@pytest.mark.p1
def test_cen_olddb_005_linha_com_colunas_incorretas(page_as, tmp_path):
    arquivo = tmp_path / "importacao.csv"
    arquivo.write_text(_csv_line(17), encoding="utf-8")

    page = page_as("admin")
    page.goto(f"{BASE_URL}/olddb")
    response = http.submit(page, "POST", "/olddb/import", files={"file": arquivo})
    assert response.status == 302

    monitor = page.request.get(f"{BASE_URL}/monitor/getImportOldDBJob").json()
    assert monitor is not None
    assert "failed" in (monitor["data"] or "")
    assert "linhas_com_erros" in (monitor["data"] or "")
    assert '[1]' in (monitor["data"] or "")
