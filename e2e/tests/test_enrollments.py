import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.manifest import BASE_URL


def _enrollment_id(manifest, student, class_key):
    return db.scalar(
        "select id from enrollments where student_id=%s and school_class_id=%s",
        (manifest["students"][student], manifest["classes"][class_key]["id"]),
    )


def _enroll_student(page, class_id, **overrides):
    payload = {
        "school_class_id": str(class_id),
        "preferencia_horario": "Diurno",
        "disponibilidade_diurno": "1",
        "voluntario": "1",
        "observacoes": "Inscrição E2E",
    }
    payload.update(overrides)
    return http.submit(page, "POST", "/enrollments", payload)


@pytest.mark.p0
def test_aluno_ve_turmas_do_periodo_de_inscricao(page_as, manifest):
    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/enrollments")

    expect(page.get_by_role("heading", name="Disciplinas com inscrições abertas")).to_be_visible()
    expect(page.locator("table")).to_contain_text("MAC0101")
    expect(page.locator("table")).to_contain_text("Sim")


@pytest.mark.p0
def test_aluno_edita_inscricao_e_propaga_preferencia(page_as, manifest):
    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/enrollments")
    page.locator("table").get_by_role("link", name="Editar").first.click()

    page.wait_for_url("**/enrollments/*/edit")
    page.select_option("#preferencia_horario", "Noturno")
    page.get_by_role("button", name="Salvar").click()

    page.wait_for_url(f"{BASE_URL}/enrollments")
    row = db.fetch_one(
        "select preferencia_horario from enrollments where student_id = %s and school_class_id = %s",
        (manifest["students"]["160001"], manifest["classes"]["mac_a"]["id"]),
    )
    assert row["preferencia_horario"] == "Noturno"


@pytest.mark.p2
def test_docente_nao_acessa_inscricao_de_aluno(page_as):
    page = page_as("docente")
    response = page.goto(f"{BASE_URL}/enrollments")
    assert response is not None
    assert response.status == 403


@pytest.mark.p0
def test_cen_enrollment_004_sem_historico_redireciona(page_as):
    page = page_as("aluno_pos")
    page.goto(f"{BASE_URL}/enrollments")
    page.wait_for_url(f"{BASE_URL}/schoolRecords/create")


@pytest.mark.p0
def test_cen_enrollment_005_inscritas_primeiro(page_as):
    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/enrollments")
    first_row = page.locator("table tr").nth(1).inner_text()
    assert first_row.startswith("MAC0101")
    assert "Sim" in first_row


@pytest.mark.p0
def test_cen_enrollment_006_guarda_de_maximo(page_as, manifest, temp):
    term_id = manifest["terms"]["open"]["id"]
    original = db.scalar("select max_enrollments from school_terms where id=%s", (term_id,))
    db.execute("update school_terms set max_enrollments=1 where id=%s", (term_id,))
    temp.append(("update school_terms set max_enrollments=%s where id=%s", (original, term_id)))

    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/enrollments/create?school_class_id={manifest['classes']['mae_a']['id']}")
    expect(page.locator("body")).to_contain_text("excedeu o número máximo de inscrições")


@pytest.mark.p0
def test_cen_enrollment_007_guarda_respeitada_no_limite(page_as, manifest):
    page = page_as("aluno_com_historico")
    response = page.goto(
        f"{BASE_URL}/enrollments/create?school_class_id={manifest['classes']['mae_a']['id']}"
    )
    assert response.status == 200
    expect(page.locator("body")).to_contain_text("Ficha de Inscrição")


@pytest.mark.p0
def test_cen_enrollment_008_store_inscreve_todas_da_mesma_coddis(page_as, manifest, temp):
    target = manifest["classes"]["mac_b"]
    page = page_as("aluno_concluido")
    page.goto(BASE_URL)
    response = _enroll_student(page, target["id"])
    http.goto_response(page, response)

    rows = db.fetch_all(
        "select e.id, c.coddis from enrollments e join school_classes c on c.id=e.school_class_id "
        "where e.student_id=%s and c.school_term_id=%s and c.coddis=%s",
        (manifest["students"]["160004"], manifest["terms"]["open"]["id"], "MAC0101"),
    )
    for row in rows:
        temp.append(("delete from enrollments where id=%s", (row["id"],)))
    assert len(rows) == 3
    assert {row["coddis"] for row in rows} == {"MAC0101"}


@pytest.mark.p0
def test_cen_enrollment_009_store_persiste_preferencias(page_as, manifest, temp):
    page = page_as("aluno_concluido")
    page.goto(BASE_URL)
    response = _enroll_student(
        page,
        manifest["classes"]["mat_a"]["id"],
        disponibilidade_noturno="1",
        preferencia_horario="Noturno",
        observacoes="Observação E2E única",
    )
    http.goto_response(page, response)

    row = db.fetch_one(
        "select * from enrollments where student_id=%s and school_class_id=%s",
        (manifest["students"]["160004"], manifest["classes"]["mat_a"]["id"]),
    )
    assert row is not None
    temp.append(("delete from enrollments where id=%s", (row["id"],)))
    assert row["preferencia_horario"] == "Noturno"
    assert row["disponibilidade_noturno"] == 1
    assert row["observacoes"] == "Observação E2E única"


@pytest.mark.p1
def test_cen_enrollment_010_validacao_do_formulario(page_as, manifest):
    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/enrollments/create?school_class_id={manifest['classes']['mae_a']['id']}")
    response = _enroll_student(
        page,
        "abc",
        preferencia_horario="",
        observacoes="x" * 65501,
        **{"scholarships[]": "999"},
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "school class id must be a number" in body
    assert "preferencia horario field is required" in body
    assert "observacoes must not be greater than 65500 characters" in body
    assert "selected scholarships.0 is invalid" in body


@pytest.mark.p0
def test_cen_enrollment_011_update_propaga_mesma_coddis(page_as, manifest, temp):
    student = manifest["students"]["160003"]
    mac_a = manifest["classes"]["mac_a"]["id"]
    mac_b = manifest["classes"]["mac_b"]["id"]
    state.add_enrollment(temp, student_id=student, class_id=mac_b)
    enrollment_id = _enrollment_id(manifest, "160003", "mac_a")

    page = page_as("aluno_sem_historico")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/enrollments/{enrollment_id}",
        {"preferencia_horario": "Noturno", "disponibilidade_noturno": "1"},
    )
    http.goto_response(page, response)

    rows = db.fetch_all(
        "select preferencia_horario, disponibilidade_noturno from enrollments "
        "where student_id=%s and school_class_id in (%s, %s)",
        (student, mac_a, mac_b),
    )
    assert len(rows) == 2
    assert all(row["preferencia_horario"] == "Noturno" for row in rows)
    assert all(row["disponibilidade_noturno"] == 1 for row in rows)


@pytest.mark.p1
def test_cen_enrollment_012_update_normaliza_booleanos(page_as, manifest, temp):
    student = manifest["students"]["160003"]
    mac_b = manifest["classes"]["mac_b"]["id"]
    state.add_enrollment(temp, student_id=student, class_id=mac_b)
    enrollment_id = _enrollment_id(manifest, "160003", "mac_a")

    page = page_as("aluno_sem_historico")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/enrollments/{enrollment_id}",
        {"preferencia_horario": "Diurno", "voluntario": "1"},
    )
    http.goto_response(page, response)

    row = db.fetch_one("select voluntario, disponibilidade_diurno from enrollments where id=%s", (enrollment_id,))
    assert row["voluntario"] == 1
    assert row["disponibilidade_diurno"] == 0


@pytest.mark.p0
def test_cen_enrollment_013_delete_bloqueado_com_selecao(page_as, manifest):
    enrollment_id = _enrollment_id(manifest, "160002", "mac_monitor")
    page = page_as("aluno_monitor")
    page.goto(BASE_URL)
    response = http.submit(page, "DELETE", f"/enrollments/{enrollment_id}")
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Você foi selecionado como monitor")
    assert db.fetch_one("select id from enrollments where id=%s", (enrollment_id,)) is not None


@pytest.mark.p0
def test_cen_enrollment_014_delete_exclui_todas_da_coddis(page_as, manifest, temp):
    student = manifest["students"]["160004"]
    mac_a = manifest["classes"]["mac_a"]["id"]
    mac_b = manifest["classes"]["mac_b"]["id"]
    state.add_enrollment(temp, student_id=student, class_id=mac_a)
    state.add_enrollment(temp, student_id=student, class_id=mac_b)
    enrollment_id = db.scalar(
        "select id from enrollments where student_id=%s and school_class_id=%s", (student, mac_a)
    )

    page = page_as("aluno_concluido")
    page.goto(BASE_URL)
    response = http.submit(page, "DELETE", f"/enrollments/{enrollment_id}")
    assert response.status == 302
    assert db.scalar(
        "select count(*) from enrollments where student_id=%s and school_class_id in (%s,%s)",
        (student, mac_a, mac_b),
    ) == 0


@pytest.mark.p1
def test_cen_enrollment_015_showall_lista_inscritos(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/enrollments/showAll")
    expect(page.get_by_role("heading", name="Alunos Inscritos")).to_be_visible()
    body = page.locator("body").inner_text()
    assert "Aluno Com Historico E2E" in body
    assert "Aluno Sem Historico E2E" in body


@pytest.mark.p2
def test_cen_enrollment_016_showall_sem_permissao(page_as):
    page = page_as("docente")
    response = page.goto(f"{BASE_URL}/enrollments/showAll")
    assert response.status == 403
