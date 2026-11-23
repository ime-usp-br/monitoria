import pytest
from playwright.sync_api import expect

from support import db, http
from support.manifest import BASE_URL, pdf_fixture


@pytest.mark.p0
def test_cen_schoolrecord_001_create_exige_aluno_e_periodo(page_as):
    docente = page_as("docente")
    assert docente.goto(f"{BASE_URL}/schoolRecords/create").status == 403

    aluno = page_as("aluno_pos")
    response = aluno.goto(f"{BASE_URL}/schoolRecords/create")
    assert response.status == 200
    expect(aluno.get_by_role("heading", name="Upload do histórico escolar")).to_be_visible()


@pytest.mark.p0
def test_cen_schoolrecord_002_store_persiste_e_redireciona(page_as, manifest, temp):
    page = page_as("aluno_pos")
    page.goto(BASE_URL)
    response = http.submit(
        page, "POST", "/schoolRecords", files={"file": pdf_fixture("edital.pdf")}
    )
    http.goto_response(page, response)
    assert page.url.startswith(f"{BASE_URL}/enrollments")

    row = db.fetch_one(
        "select id, file_path from school_records where student_id=%s and schoolterm_id=%s",
        (manifest["students"]["800001"], manifest["terms"]["open"]["id"]),
    )
    assert row is not None
    temp.append(("delete from school_records where id=%s", (row["id"],)))
    assert row["file_path"].startswith(
        str(manifest["terms"]["open"]["year"]) + manifest["terms"]["open"]["period"][0]
    )


@pytest.mark.p0
def test_cen_schoolrecord_003_validacao_arquivo_pdf(page_as, tmp_path):
    txt = tmp_path / "historico.txt"
    txt.write_text("não é pdf", encoding="utf-8")
    grande = tmp_path / "historico_grande.pdf"
    grande.write_bytes(b"%PDF-1.4\n" + b"0" * 1_100_000)

    page = page_as("aluno_pos")
    page.goto(BASE_URL)

    response = http.submit(page, "POST", "/schoolRecords", {})
    http.goto_response(page, response)
    assert "file field is required" in page.locator("body").inner_text()

    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/schoolRecords", files={"file": txt})
    http.goto_response(page, response)
    assert "file must be a file of type: application/pdf" in page.locator("body").inner_text()

    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/schoolRecords", files={"file": grande})
    http.goto_response(page, response)
    assert "file must not be greater than 1000 kilobytes" in page.locator("body").inner_text()


@pytest.mark.p0
def test_cen_schoolrecord_004_update_substitui_arquivo(page_as, manifest, temp):
    row = db.fetch_one(
        "select id, file_path from school_records where student_id=%s and schoolterm_id=%s",
        (manifest["students"]["160001"], manifest["terms"]["open"]["id"]),
    )
    assert row is not None
    temp.append(
        ("update school_records set file_path=%s where id=%s", (row["file_path"], row["id"]))
    )

    page = page_as("aluno_com_historico")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/schoolRecords/{row['id']}",
        files={"file": pdf_fixture("edital.pdf")},
    )
    http.goto_response(page, response)

    updated = db.fetch_one("select file_path from school_records where id=%s", (row["id"],))
    assert updated["file_path"] != row["file_path"]
    assert db.scalar(
        "select count(*) from school_records where student_id=%s and schoolterm_id=%s",
        (manifest["students"]["160001"], manifest["terms"]["open"]["id"]),
    ) == 1


@pytest.mark.p0
def test_cen_schoolrecord_005_download_valida_storage(page_as, manifest):
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(
        page, "POST", "/schoolrecords/download", {"path": manifest["files"]["historico"]}
    )
    assert response.status == 200
    assert "pdf" in (response.headers.get("content-type") or "")
    assert response.body().startswith(b"%PDF")

    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/schoolrecords/download", {"path": "99999/nao.pdf"})
    http.goto_response(page, response)
    assert "O arquivo não foi encontrado no servidor" in page.locator("body").inner_text()
