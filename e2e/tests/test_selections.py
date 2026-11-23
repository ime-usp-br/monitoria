import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.manifest import BASE_URL


def _class_with_enrollment(manifest, temp, *, student="160004", coddis="MAC0990", codtur="20990030", department="MAC"):
    class_id = state.add_class(
        manifest,
        temp,
        coddis=coddis,
        codtur=codtur,
        department_id=manifest["departments"][department],
        instructor_codpes=("20001",),
    )
    enrollment_id = state.add_enrollment(
        temp, student_id=manifest["students"][student], class_id=class_id
    )
    requisition_id = db.scalar(
        "select id from requisitions where school_class_id=%s", (class_id,)
    )
    return class_id, enrollment_id, requisition_id


@pytest.mark.p0
def test_membro_comissao_ve_apenas_solicitacoes_do_departamento(page_as, manifest):
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/selections")

    expect(page.locator("body")).to_contain_text("Departamento de Ciência da Computação")
    expect(page.locator("table")).to_contain_text("MAC0101")
    assert "MAE0101" not in page.locator("body").inner_text()


@pytest.mark.p0
def test_membro_comissao_elege_monitor(page_as, manifest, temp):
    class_id, enrollment_id, _ = _class_with_enrollment(
        manifest, temp, student="160001", coddis="MAC0989", codtur="20990031"
    )
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    http.goto_response(page, response)

    row = db.fetch_one(
        "select sitatl from selections where student_id=%s and school_class_id=%s",
        (manifest["students"]["160001"], class_id),
    )
    assert row is not None
    assert row["sitatl"] == "Ativo"


@pytest.mark.p1
def test_membro_de_outro_departamento_nao_elege(page_as, manifest, temp):
    class_id, enrollment_id, _ = _class_with_enrollment(
        manifest, temp, student="160001", coddis="MAC0988", codtur="20990032"
    )
    page = page_as("membro_comissao_mae")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    assert response.status == 403
    assert db.fetch_one(
        "select id from selections where student_id=%s and school_class_id=%s",
        (manifest["students"]["160001"], class_id),
    ) is None


@pytest.mark.p0
def test_cen_selection_001_index_exige_permissao(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/selections").status == 403


@pytest.mark.p0
def test_cen_selection_002_admin_e_secretaria_listam_todas(page_as):
    for role in ("admin", "secretaria"):
        page = page_as(role)
        page.goto(f"{BASE_URL}/selections")
        body = page.locator("body").inner_text()
        assert "MAC0101" in body
        assert "MAE0101" in body
        assert "MAT0101" in body


@pytest.mark.p0
def test_cen_selection_003_membro_ve_apenas_departamento(page_as):
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/selections")
    body = page.locator("body").inner_text()
    assert "MAC0101" in body
    assert "MAE0101" not in body

    page = page_as("membro_comissao_mae")
    page.goto(f"{BASE_URL}/selections")
    body = page.locator("body").inner_text()
    assert "MAE0101" in body
    assert "MAC0101" not in body


@pytest.mark.p0
def test_cen_selection_004_store_substitui_selecao_existente(page_as, manifest, temp):
    class_id, enrollment_id, requisition_id = _class_with_enrollment(
        manifest, temp, student="160004", coddis="MAC0987", codtur="20990033"
    )
    old_selection = state.add_selection(
        temp,
        student_id=manifest["students"]["160004"],
        class_id=class_id,
        enrollment_id=enrollment_id,
        requisition_id=requisition_id,
        sitatl="Desligado",
    )

    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    http.goto_response(page, response)

    assert db.fetch_one("select id from selections where id=%s", (old_selection,)) is None
    row = db.fetch_one(
        "select sitatl, codpescad from selections where student_id=%s and school_class_id=%s",
        (manifest["students"]["160004"], class_id),
    )
    assert row["sitatl"] == "Ativo"
    assert row["codpescad"] == 10001


@pytest.mark.p0
def test_cen_selection_005_store_preenche_vinculos(page_as, manifest, temp):
    class_id, enrollment_id, requisition_id = _class_with_enrollment(
        manifest, temp, student="160004", coddis="MAC0986", codtur="20990034"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    http.goto_response(page, response)

    row = db.fetch_one(
        "select student_id, school_class_id, requisition_id from selections "
        "where student_id=%s and school_class_id=%s",
        (manifest["students"]["160004"], class_id),
    )
    assert row == {
        "student_id": manifest["students"]["160004"],
        "school_class_id": class_id,
        "requisition_id": requisition_id,
    }


@pytest.mark.p0
def test_cen_selection_007_bloqueia_aluno_eleito_em_outra_turma(page_as, manifest, temp):
    class_id, enrollment_id, _ = _class_with_enrollment(
        manifest, temp, student="160002", coddis="MAC0985", codtur="20990035"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Aluno já foi eleito monitor de outra turma")
    assert db.fetch_one(
        "select id from selections where student_id=%s and school_class_id=%s",
        (manifest["students"]["160002"], class_id),
    ) is None


@pytest.mark.p0
def test_cen_selection_008_membro_so_seleciona_no_departamento(page_as, manifest):
    enrollment_id = db.scalar(
        "select id from enrollments where student_id=%s and school_class_id=%s",
        (manifest["students"]["160001"], manifest["classes"]["mac_a"]["id"]),
    )
    page = page_as("membro_comissao_mae")
    page.goto(f"{BASE_URL}/selections/{manifest['classes']['mac_a']['id']}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    assert response.status == 403


@pytest.mark.p0
def test_cen_selection_009_store_cria_frequencias(page_as, manifest, temp):
    class_id, enrollment_id, _ = _class_with_enrollment(
        manifest, temp, student="160004", coddis="MAC0984", codtur="20990036"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
    http.goto_response(page, response)

    months = db.fetch_all(
        "select month from frequencies where student_id=%s and school_class_id=%s order by month",
        (manifest["students"]["160004"], class_id),
    )
    expected = [3, 4, 5, 6] if manifest["terms"]["open"]["period"] == "1° Semestre" else [8, 9, 10, 11]
    assert [row["month"] for row in months] == expected


@pytest.mark.p1
def test_cen_selection_010_store_usa_first_or_create(page_as, manifest, temp):
    class_id, enrollment_id, _ = _class_with_enrollment(
        manifest, temp, student="160004", coddis="MAC0983", codtur="20990037"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    for _ in range(2):
        response = http.submit(page, "POST", "/selections", {"enrollment_id": str(enrollment_id)})
        http.goto_response(page, response)
    assert db.scalar(
        "select count(*) from selections where student_id=%s and school_class_id=%s",
        (manifest["students"]["160004"], class_id),
    ) == 1


@pytest.mark.p0
def test_cen_selection_011_destroy_bloqueado_com_presenca(page_as, manifest):
    selection_id = manifest["selections"]["ativo"]["id"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{manifest['classes']['mac_monitor']['id']}/enrollments")
    response = http.submit(page, "DELETE", f"/selections/{selection_id}")
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("existe registro de presença na monitoria")
    assert db.fetch_one("select id from selections where id=%s", (selection_id,)) is not None


@pytest.mark.p0
def test_cen_selection_012_destroy_sem_presenca_exclui(page_as, manifest, temp):
    class_id, enrollment_id, requisition_id = _class_with_enrollment(
        manifest, temp, student="160004", coddis="MAC0982", codtur="20990038"
    )
    selection_id = state.add_selection(
        temp,
        student_id=manifest["students"]["160004"],
        class_id=class_id,
        enrollment_id=enrollment_id,
        requisition_id=requisition_id,
    )
    for month in (8, 9, 10, 11):
        state.add_frequency(
            temp,
            student_id=manifest["students"]["160004"],
            class_id=class_id,
            month=month,
            registered=False,
        )

    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(page, "DELETE", f"/selections/{selection_id}")
    assert response.status == 302
    assert db.fetch_one("select id from selections where id=%s", (selection_id,)) is None
    assert db.scalar(
        "select count(*) from frequencies where student_id=%s and school_class_id=%s",
        (manifest["students"]["160004"], class_id),
    ) == 0


@pytest.mark.p1
def test_cen_selection_013_membro_outro_departamento_nao_pretere(page_as, manifest, temp):
    class_id, enrollment_id, requisition_id = _class_with_enrollment(
        manifest, temp, student="160004", coddis="MAC0980", codtur="20990040"
    )
    selection_id = state.add_selection(
        temp,
        student_id=manifest["students"]["160004"],
        class_id=class_id,
        enrollment_id=enrollment_id,
        requisition_id=requisition_id,
    )

    page = page_as("membro_comissao_mae")
    response = http.submit(page, "DELETE", f"/selections/{selection_id}")
    assert response.status == 403
    assert db.fetch_one("select id from selections where id=%s", (selection_id,)) is not None


@pytest.mark.p1
def test_cen_selection_014_enrollments_lista_inscricoes(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{manifest['classes']['mac_a']['id']}/enrollments")
    expect(page.get_by_role("heading", name="Seleção de Monitores")).to_be_visible()
    body = page.locator("body").inner_text()
    assert "Aluno Com Historico E2E" in body
    assert "Aluno Sem Historico E2E" in body


@pytest.mark.p0
def test_cen_selection_015_select_unenrolled_cria_tudo(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0981", codtur="20990039")
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(
        page,
        "POST",
        "/selections/selectunenrolled",
        {"school_class_id": str(class_id), "codpes": "160001"},
    )
    http.goto_response(page, response)

    enrollment = db.fetch_one(
        "select id from enrollments where student_id=%s and school_class_id=%s",
        (manifest["students"]["160001"], class_id),
    )
    assert enrollment is not None
    selection = db.fetch_one(
        "select sitatl from selections where student_id=%s and school_class_id=%s",
        (manifest["students"]["160001"], class_id),
    )
    assert selection["sitatl"] == "Ativo"
    assert db.scalar(
        "select count(*) from frequencies where student_id=%s and school_class_id=%s",
        (manifest["students"]["160001"], class_id),
    ) >= 4


@pytest.mark.p1
def test_cen_selection_016_select_unenrolled_bloqueia_inscrito(page_as, manifest):
    class_id = manifest["classes"]["mac_a"]["id"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(
        page,
        "POST",
        "/selections/selectunenrolled",
        {"school_class_id": str(class_id), "codpes": "160001"},
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("já está inscrito nesta turma")


@pytest.mark.p1
def test_cen_selection_017_select_unenrolled_bloqueia_eleito_em_outra(page_as, manifest):
    class_id = manifest["classes"]["mac_a"]["id"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(
        page,
        "POST",
        "/selections/selectunenrolled",
        {"school_class_id": str(class_id), "codpes": "160002"},
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("já foi eleito monitor da turma")


@pytest.mark.p1
def test_cen_selection_018_select_unenrolled_bloqueia_sem_historico(page_as, manifest):
    class_id = manifest["classes"]["mac_a"]["id"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments")
    response = http.submit(
        page,
        "POST",
        "/selections/selectunenrolled",
        {"school_class_id": str(class_id), "codpes": "800001"},
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("não subiu o histórico escolar")
