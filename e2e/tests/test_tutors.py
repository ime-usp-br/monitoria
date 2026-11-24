import pytest
from playwright.sync_api import expect

from support import clock, db, http, state
from support.manifest import BASE_URL


def _selection_fixture(manifest, temp, *, student="160004", coddis="MAC0970", codtur="20990050", voluntario=0):
    class_id = state.add_class(
        manifest, temp, coddis=coddis, codtur=codtur, department_id=manifest["departments"]["MAC"]
    )
    enrollment_id = state.add_enrollment(
        temp, student_id=manifest["students"][student], class_id=class_id, voluntario=voluntario
    )
    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    selection_id = state.add_selection(
        temp,
        student_id=manifest["students"][student],
        class_id=class_id,
        enrollment_id=enrollment_id,
        requisition_id=requisition_id,
    )
    return class_id, enrollment_id, selection_id


@pytest.mark.p0
def test_cen_tutor_001_admin_secretaria_presidente_veem_todas(page_as):
    for role in ("admin", "secretaria", "presidente"):
        page = page_as(role)
        page.goto(f"{BASE_URL}/tutors")
        body = page.locator("body").inner_text()
        assert "Aluno Monitor Ativo E2E" in body
        assert "Aluno Sem Historico E2E" in body


@pytest.mark.p0
def test_cen_tutor_002_membro_ve_apenas_departamento(page_as):
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/tutors")
    body = page.locator("body").inner_text()
    assert "Aluno Monitor Ativo E2E" in body

    page = page_as("membro_comissao_mae")
    page.goto(f"{BASE_URL}/tutors")
    assert "Aluno Monitor Ativo E2E" not in page.locator("body").inner_text()


@pytest.mark.p0
def test_cen_tutor_003_docente_ve_somente_suas_selecoes(page_as, manifest, temp):
    class_id = state.add_class(
        manifest, temp, coddis="MAE0970", codtur="20990051", department_id=manifest["departments"]["MAE"],
        instructor_codpes=("20005",),
    )
    enrollment_id = state.add_enrollment(
        temp, student_id=manifest["students"]["160004"], class_id=class_id
    )
    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    state.add_selection(
        temp,
        student_id=manifest["students"]["160004"],
        class_id=class_id,
        enrollment_id=enrollment_id,
        requisition_id=requisition_id,
    )

    page = page_as("docente")
    page.goto(f"{BASE_URL}/tutors")
    body = page.locator("body").inner_text()
    assert "Aluno Monitor Ativo E2E" in body
    assert "Aluno Sem Historico E2E" in body
    assert "Aluno Monitor Concluido E2E" not in body


@pytest.mark.p2
def test_cen_tutor_004_aluno_recebe_403(page_as):
    page = page_as("aluno_com_historico")
    assert page.goto(f"{BASE_URL}/tutors").status == 403


@pytest.mark.p1
def test_cen_tutor_005_revoke_exige_secretaria_admin(page_as, manifest):
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page, "PATCH", f"/tutors/revoke/{manifest['selections']['ativo']['id']}", {"motdes": "E2E"}
    )
    assert response.status == 403


@pytest.mark.p1
def test_cen_tutor_006_revoke_bloqueia_nao_ativo(page_as, manifest):
    selection_id = manifest["selections"]["concluido"]["id"]
    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/revoke/{selection_id}", {"motdes": "E2E"})
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("status Concluido")
    assert db.scalar("select sitatl from selections where id=%s", (selection_id,)) == "Concluido"


@pytest.mark.p0
def test_cen_tutor_007_revoke_exclui_frequencias_futuras(page_as, manifest, temp):
    class_id, enrollment_id, selection_id = _selection_fixture(manifest, temp)
    student_id = manifest["students"]["160004"]
    current = clock.now().month
    past = min(8, current - 1)
    future = min(12, current + 1)
    state.add_frequency(temp, student_id=student_id, class_id=class_id, month=past, registered=True)
    state.add_frequency(temp, student_id=student_id, class_id=class_id, month=current, registered=False)
    state.add_frequency(temp, student_id=student_id, class_id=class_id, month=future, registered=False)

    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/revoke/{selection_id}", {"motdes": "Desligamento E2E"})
    http.goto_response(page, response)

    assert db.fetch_one(
        "select id from frequencies where student_id=%s and school_class_id=%s and month=%s",
        (student_id, class_id, past),
    ) is not None
    assert db.scalar(
        "select count(*) from frequencies where student_id=%s and school_class_id=%s and month>=%s",
        (student_id, class_id, current),
    ) == 0


@pytest.mark.p0
def test_cen_tutor_008_revoke_define_desligado(page_as, manifest, temp):
    _, _, selection_id = _selection_fixture(manifest, temp)

    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/revoke/{selection_id}", {"motdes": "Motivo E2E"})
    http.goto_response(page, response)

    row = db.fetch_one("select sitatl, motdes, dtafimvin from selections where id=%s", (selection_id,))
    assert row["sitatl"] == "Desligado"
    assert row["motdes"] == "Motivo E2E"
    today = clock.now()
    assert row["dtafimvin"] in (today.strftime("%d/%m/%Y"), today.strftime("%Y-%m-%d"), today.strftime("%Y-%m-%d 00:00:00")) or row["dtafimvin"].startswith(today.strftime("%Y-%m-%d"))


@pytest.mark.p0
def test_cen_tutor_009_turn_into_volunteer(page_as, manifest, temp):
    _, enrollment_id, selection_id = _selection_fixture(manifest, temp, voluntario=0)
    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/turnintovolunteer/{selection_id}")
    http.goto_response(page, response)
    assert db.scalar("select voluntario from enrollments where id=%s", (enrollment_id,)) == 1

    concluido = manifest["selections"]["concluido"]["id"]
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/turnintovolunteer/{concluido}")
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("status Concluido")


@pytest.mark.p0
def test_cen_tutor_010_turn_into_non_volunteer(page_as, manifest, temp):
    _, enrollment_id, selection_id = _selection_fixture(manifest, temp, voluntario=1)
    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/turnintononvolunteer/{selection_id}")
    http.goto_response(page, response)
    assert db.scalar("select voluntario from enrollments where id=%s", (enrollment_id,)) == 0


@pytest.mark.p1
def test_cen_tutor_011_alternancia_nao_altera_outras_inscricoes(page_as, manifest, temp):
    first_class, first_enrollment, selection_id = _selection_fixture(
        manifest, temp, student="160004", coddis="MAC0969", codtur="20990052", voluntario=0
    )
    other_class = state.add_class(
        manifest, temp, coddis="MAC0968", codtur="20990053", requisition=False
    )
    other_enrollment = state.add_enrollment(
        temp, student_id=manifest["students"]["160004"], class_id=other_class, voluntario=0
    )

    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")
    response = http.submit(page, "PATCH", f"/tutors/turnintovolunteer/{selection_id}")
    http.goto_response(page, response)

    assert db.scalar("select voluntario from enrollments where id=%s", (first_enrollment,)) == 1
    assert db.scalar("select voluntario from enrollments where id=%s", (other_enrollment,)) == 0
