import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.manifest import BASE_URL


def _class_payload(manifest, *, codtur="20990001", coddis="MAC0999", nomdis="Turma E2E"):
    return {
        "periodoId": str(manifest["terms"]["open"]["id"]),
        "department_id": str(manifest["departments"]["MAC"]),
        "codtur": codtur,
        "coddis": coddis,
        "nomdis": nomdis,
        "tiptur": "Teoria",
        "dtainitur": "01/03/2026",
        "dtafimtur": "30/06/2026",
        "instrutores[0][codpes]": "20001",
        "horarios[0][diasmnocp]": "seg",
        "horarios[0][horent]": "08:00",
        "horarios[0][horsai]": "10:00",
    }


@pytest.mark.p0
def test_cen_schoolclass_001_store_cria_turma_instrutores_horarios(page_as, manifest, temp):
    payload = _class_payload(manifest)
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/schoolclasses", payload)
    http.goto_response(page, response)

    row = db.fetch_one("select id from school_classes where codtur=%s and coddis=%s", (payload["codtur"], payload["coddis"]))
    assert row is not None
    temp.append(("delete from school_classes where id=%s", (row["id"],)))
    assert db.scalar(
        "select count(*) from instructor_school_class where school_class_id=%s", (row["id"],)
    ) == 1
    assert db.scalar(
        "select count(*) from class_schedule_school_class where school_class_id=%s", (row["id"],)
    ) == 1


@pytest.mark.p1
def test_cen_schoolclass_002_duplicada_sem_instrutores_anexa(page_as, manifest, temp):
    class_id = state.add_class(
        manifest, temp, coddis="MAC0998", codtur="20990002", instructor_codpes=(), requisition=False
    )
    payload = _class_payload(manifest, codtur="20990002", coddis="MAC0998")
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/schoolclasses", payload)
    http.goto_response(page, response)

    assert db.scalar("select count(*) from school_classes where coddis='MAC0998'") == 1
    assert db.scalar(
        "select count(*) from instructor_school_class where school_class_id=%s", (class_id,)
    ) == 1


@pytest.mark.p1
def test_cen_schoolclass_003_duplicada_com_instrutores_exibe_aviso(page_as, manifest):
    codtur = manifest["classes"]["mac_a"]["codtur"]
    coddis = manifest["classes"]["mac_a"]["coddis"]
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/schoolclasses", _class_payload(manifest, codtur=codtur, coddis=coddis))
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Já existe uma turma cadastrada")


@pytest.mark.p0
def test_cen_schoolclass_004_validacoes_do_formulario(page_as, manifest):
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "POST",
        "/schoolclasses",
        {
            "periodoId": str(manifest["terms"]["open"]["id"]),
            "department_id": "x",
            "codtur": "abc",
            "dtainitur": "31/02/2026",
            "dtafimtur": "01/01/2026",
            "instrutores[0][codpes]": "abc",
            "horarios[0][diasmnocp]": "xyz",
            "horarios[0][horent]": "25:00",
            "horarios[0][horsai]": "10:00",
        },
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "coddis field is required" in body
    assert "nomdis field is required" in body
    assert "tiptur field is required" in body
    assert "codtur must be a number" in body
    assert "instrutores.0.codpes must be a number" in body
    assert "selected horarios.0.diasmnocp is invalid" in body


@pytest.mark.p0
def test_cen_schoolclass_005_update_reanexa_instrutores_e_horarios(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0997", codtur="20990005")
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/schoolclasses/{class_id}",
        {
            "dtainitur": "01/03/2026",
            "dtafimtur": "30/06/2026",
            "instrutores[0][codpes]": "20005",
            "horarios[0][diasmnocp]": "ter",
            "horarios[0][horent]": "14:00",
            "horarios[0][horsai]": "16:00",
        },
    )
    http.goto_response(page, response)

    instructor_id = db.scalar(
        "select instructor_id from instructor_school_class where school_class_id=%s", (class_id,)
    )
    assert instructor_id == manifest["instructors"]["20005"]
    assert db.scalar(
        "select count(*) from class_schedule_school_class where school_class_id=%s", (class_id,)
    ) == 1


@pytest.mark.p1
def test_cen_schoolclass_006_update_omite_campos_imutaveis(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0996", codtur="20990006")
    original = db.fetch_one("select codtur, coddis, nomdis from school_classes where id=%s", (class_id,))
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/schoolclasses/{class_id}",
        {
            "dtainitur": "01/03/2026",
            "dtafimtur": "30/06/2026",
            "instrutores[0][codpes]": "20001",
        },
    )
    assert response.status in (302, 200)
    assert db.fetch_one("select codtur, coddis, nomdis from school_classes where id=%s", (class_id,)) == original


@pytest.mark.p0
def test_cen_schoolclass_007_destroy_exclui_turma_e_pivos(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0995", codtur="20990007")
    page = page_as("admin")
    page.goto(BASE_URL)
    response = http.submit(page, "DELETE", f"/schoolclasses/{class_id}")
    assert response.status == 302
    assert db.fetch_one("select id from school_classes where id=%s", (class_id,)) is None
    assert db.scalar(
        "select count(*) from instructor_school_class where school_class_id=%s", (class_id,)
    ) == 0


@pytest.mark.p0
def test_cen_schoolclass_010_docente_ve_apenas_proprias_turmas(page_as, manifest):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/schoolclasses")
    body = page.locator("body").inner_text()
    assert "MAC0101" in body
    assert "MAC0202" in body
    assert "MAE0101" not in body
    assert "MAT0101" not in body


@pytest.mark.p0
def test_cen_schoolclass_011_membro_comissao_ve_apenas_departamento(page_as):
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/schoolclasses")
    body = page.locator("body").inner_text()
    assert "MAC0101" in body
    assert "MAE0101" not in body
    assert "MAT0101" not in body


@pytest.mark.p0
def test_cen_schoolclass_012_secretaria_e_admin_veem_todas(page_as):
    for role in ("admin", "secretaria"):
        page = page_as(role)
        page.goto(f"{BASE_URL}/schoolclasses")
        body = page.locator("body").inner_text()
        assert "MAC0101" in body
        assert "MAE0101" in body
        assert "MAT0101" in body


@pytest.mark.p1
def test_cen_schoolclass_013_index_seleciona_periodo_aberto(page_as, manifest):
    term = manifest["terms"]["open"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolclasses")
    expect(page.locator("body")).to_contain_text(f"{term['period']} de {term['year']}")


@pytest.mark.p1
def test_cen_schoolclass_014_busca_por_coddis(page_as, manifest):
    page = page_as("admin")
    page.goto(
        f"{BASE_URL}/schoolclasses/search?coddis=MAC0202&periodoId={manifest['terms']['open']['id']}"
    )
    body = page.locator("body").inner_text()
    assert "MAC0202" in body
    assert "MAE0101" not in body


@pytest.mark.p1
def test_cen_schoolclass_015_views_de_inscricoes_e_monitores(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolclasses/{manifest['classes']['mac_a']['id']}/enrollments")
    expect(page.get_by_role("heading", name="Alunos Inscritos")).to_be_visible()

    page.goto(f"{BASE_URL}/schoolclasses/{manifest['classes']['mac_monitor']['id']}/electedTutors")
    expect(page.get_by_role("heading", name="Monitores")).to_be_visible()


@pytest.mark.p1
def test_cen_schoolclass_016_formulario_popula_departamentos(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/schoolclasses/create?periodoId={manifest['terms']['open']['id']}")
    department = page.locator("#department_id")
    expect(department).to_be_visible()
    options = department.locator("option").all_inner_texts()
    assert any("Ciência da Computação" in option for option in options)
    assert len(options) >= 5
