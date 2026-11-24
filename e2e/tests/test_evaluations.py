import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.manifest import BASE_URL


@pytest.mark.p0
def test_link_assinado_abre_autoavaliacao(anonymous_page, manifest):
    anonymous_page.goto(manifest["urls"]["self_evaluation_signed"])
    expect(anonymous_page.get_by_role("heading", name="Cadastrar Relatório das Atividades Desenvolvidas")).to_be_visible()
    expect(anonymous_page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")


@pytest.mark.p0
def test_link_assinado_abre_avaliacao_do_docente(anonymous_page, manifest):
    anonymous_page.goto(manifest["urls"]["instructor_evaluation_signed"])
    expect(anonymous_page.get_by_role("heading", name="Cadastrar Avaliação de Monitor")).to_be_visible()


@pytest.mark.p2
def test_link_adulterado_e_rejeitado(anonymous_page, manifest):
    response = anonymous_page.goto(manifest["urls"]["self_evaluation_signed"] + "0")
    assert response is not None
    assert response.status == 403


def _temp_selection(manifest, temp, *, student="160004", coddis="MAC0960", codtur="20990060", department="MAC", instructor=("20001",)):
    class_id = state.add_class(
        manifest, temp, coddis=coddis, codtur=codtur,
        department_id=manifest["departments"][department], instructor_codpes=instructor,
    )
    enrollment_id = state.add_enrollment(temp, student_id=manifest["students"][student], class_id=class_id)
    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    selection_id = state.add_selection(
        temp,
        student_id=manifest["students"][student],
        class_id=class_id,
        enrollment_id=enrollment_id,
        requisition_id=requisition_id,
    )
    return class_id, enrollment_id, selection_id


# --- Autoavaliação -------------------------------------------------------

@pytest.mark.p0
def test_cen_selfeval_001_index_exige_permissao(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/selfevaluations").status == 403


@pytest.mark.p1
def test_cen_selfeval_002_index_lista_do_periodo(page_as, manifest, temp):
    _, _, selection_id = _temp_selection(manifest, temp, coddis="MAC0961", codtur="20990061")
    state.add_self_evaluation(temp, selection_id=selection_id, comments="Autoavaliação temporária E2E")

    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/selfevaluations")
    expect(page.get_by_role("heading", name="Relatórios das Atividades Desenvolvidas")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Aluno Monitor Concluido E2E")


@pytest.mark.p0
def test_cen_selfeval_003_student_index_lista_proprias(page_as):
    page = page_as("aluno_monitor")
    page.goto(f"{BASE_URL}/students/selfevaluations")
    body = page.locator("body").inner_text()
    assert "MAC0202" in body
    assert "MAC0303" not in body

    page = page_as("aluno_concluido")
    page.goto(f"{BASE_URL}/students/selfevaluations")
    body = page.locator("body").inner_text()
    assert "MAC0303" in body

    page = page_as("aluno_sem_historico")
    page.goto(f"{BASE_URL}/students/selfevaluations")
    expect(page.locator("body")).to_contain_text("Não constam monitorias em seu nome")


@pytest.mark.p2
def test_cen_selfeval_004_student_index_exige_aluno(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/students/selfevaluations").status == 403


@pytest.mark.p0
def test_cen_selfeval_005_create_fora_da_janela(page_as, manifest):
    page = page_as("aluno_concluido")
    page.goto(f"{BASE_URL}/selfevaluations/create?selectionID={manifest['selections']['concluido']['id']}")
    expect(page.locator("body")).to_contain_text("Período de avaliação encerrado")


@pytest.mark.p0
def test_cen_selfeval_006_create_na_janela_pelo_dono(page_as, manifest):
    page = page_as("aluno_monitor")
    response = page.goto(f"{BASE_URL}/selfevaluations/create?selectionID={manifest['selections']['ativo']['id']}")
    assert response.status == 200
    expect(page.get_by_role("heading", name="Cadastrar Relatório das Atividades Desenvolvidas")).to_be_visible()


@pytest.mark.p0
def test_cen_selfeval_007_create_via_url_assinada(page_as, anonymous_page, manifest):
    response = anonymous_page.goto(manifest["urls"]["self_evaluation_signed"])
    assert response.status == 200

    page = page_as("aluno_monitor")
    response = page.goto(f"{BASE_URL}/selfevaluations/create?selectionID=99999&signature=invalida")
    assert response.status == 403


@pytest.mark.p2
def test_cen_selfeval_008_create_por_outro_aluno_negado(page_as, manifest):
    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/selfevaluations/create?selectionID={manifest['selections']['ativo']['id']}")
    expect(page.locator("body")).to_contain_text("Essa monitoria não pertence a você")


@pytest.mark.p0
def test_cen_selfeval_009_store_cria_e_atualiza(page_as, manifest, temp):
    page = page_as("aluno_monitor")
    page.goto(f"{BASE_URL}/students/selfevaluations")
    payload = {
        "selection_id": str(manifest["selections"]["ativo"]["id"]),
        "selection_hash": "hash-validado-pelo-dono",
        "student_amount": "2",
        "homework_amount": "1",
        "workload": "2",
    }
    response = http.submit(page, "POST", "/selfevaluations", payload)
    http.goto_response(page, response)
    row = db.fetch_one(
        "select id, student_amount from self_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    )
    assert row is not None
    temp.append(("delete from self_evaluations where id=%s", (row["id"],)))
    assert row["student_amount"] == 2

    page.goto(f"{BASE_URL}/students/selfevaluations")
    payload["student_amount"] = "3"
    response = http.submit(page, "POST", "/selfevaluations", payload)
    http.goto_response(page, response)
    assert db.scalar(
        "select count(*) from self_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    ) == 1
    assert db.scalar(
        "select student_amount from self_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    ) == 3


@pytest.mark.p0
def test_cen_selfeval_010_store_por_link_assinado_valida_hash(anonymous_page, manifest, temp):
    anonymous_page.goto(manifest["urls"]["self_evaluation_signed"])
    selection_hash = anonymous_page.locator("input[name=selection_hash]").input_value()
    payload = {
        "selection_id": str(manifest["selections"]["ativo"]["id"]),
        "selection_hash": selection_hash,
        "student_amount": "1",
        "homework_amount": "1",
        "workload": "2",
    }
    response = http.submit(anonymous_page, "POST", "/selfevaluations", payload)
    http.goto_response(anonymous_page, response)
    row = db.fetch_one(
        "select id from self_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    )
    assert row is not None
    temp.append(("delete from self_evaluations where id=%s", (row["id"],)))

    anonymous_page.goto(manifest["urls"]["self_evaluation_signed"])
    payload["selection_hash"] = "hash-invalido"
    response = http.submit(anonymous_page, "POST", "/selfevaluations", payload)
    http.goto_response(anonymous_page, response)
    expect(anonymous_page.locator("body")).to_contain_text("Essa monitoria não pertence a você")


@pytest.mark.p2
def test_cen_selfeval_011_store_exige_dono(page_as, manifest):
    page = page_as("aluno_com_historico")
    page.goto(f"{BASE_URL}/students/selfevaluations")
    response = http.submit(
        page,
        "POST",
        "/selfevaluations",
        {
            "selection_id": str(manifest["selections"]["ativo"]["id"]),
            "selection_hash": "x",
            "student_amount": "1",
            "homework_amount": "1",
            "workload": "2",
        },
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Essa monitoria não pertence a você")


@pytest.mark.p1
def test_cen_selfeval_012_validacao_do_store(page_as, manifest):
    page = page_as("aluno_monitor")
    page.goto(f"{BASE_URL}/students/selfevaluations")
    response = http.submit(
        page,
        "POST",
        "/selfevaluations",
        {
            "selection_id": "x",
            "student_amount": "x",
            "homework_amount": "x",
            "workload": "x",
        },
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "selection id must be an integer" in body
    assert "selection hash field is required" in body
    assert "student amount must be an integer" in body


@pytest.mark.p1
def test_cen_selfeval_013_show_dono_ou_permissao(page_as, manifest):
    evaluation_id = manifest["self_evaluations"]["concluido"]
    assert page_as("aluno_concluido").goto(f"{BASE_URL}/selfevaluations/{evaluation_id}").status == 200
    assert page_as("admin").goto(f"{BASE_URL}/selfevaluations/{evaluation_id}").status == 200
    assert page_as("servidor_sem_papel").goto(f"{BASE_URL}/selfevaluations/{evaluation_id}").status == 403


@pytest.mark.p1
def test_cen_selfeval_014_edit_update_restrito_ao_dono(page_as, manifest, temp):
    evaluation_id = manifest["self_evaluations"]["concluido"]
    original = db.fetch_one("select student_amount, homework_amount, workload from self_evaluations where id=%s", (evaluation_id,))
    temp.append(
        (
            "update self_evaluations set student_amount=%s, homework_amount=%s, workload=%s where id=%s",
            (original["student_amount"], original["homework_amount"], original["workload"], evaluation_id),
        )
    )

    outsider = page_as("aluno_com_historico")
    outsider.goto(f"{BASE_URL}/selfevaluations/{evaluation_id}/edit")
    expect(outsider.locator("body")).to_contain_text("Essa monitoria não pertence a você")

    owner = page_as("aluno_concluido")
    assert owner.goto(f"{BASE_URL}/selfevaluations/{evaluation_id}/edit").status == 200
    owner.goto(f"{BASE_URL}/students/selfevaluations")
    response = http.submit(
        owner,
        "PUT",
        f"/selfevaluations/{evaluation_id}",
        {"student_amount": "5", "homework_amount": "2", "workload": "6"},
    )
    http.goto_response(owner, response)
    assert db.scalar("select student_amount from self_evaluations where id=%s", (evaluation_id,)) == 5


# --- Avaliação do docente -----------------------------------------------

@pytest.mark.p0
def test_cen_inseval_001_index_exige_permissao(page_as):
    page = page_as("aluno_com_historico")
    assert page.goto(f"{BASE_URL}/instructorevaluations").status == 403


@pytest.mark.p0
def test_cen_inseval_002_instructor_index_lista_proprias(page_as, manifest, temp):
    _, _, _ = _temp_selection(
        manifest, temp, student="160001", coddis="MAE0960", codtur="20990062",
        department="MAE", instructor=("20005",),
    )
    page = page_as("docente")
    page.goto(f"{BASE_URL}/instructors/evaluations")
    body = page.locator("body").inner_text()
    assert "Aluno Monitor Ativo E2E" in body
    assert "Aluno Monitor Concluido E2E" in body
    assert "Aluno Com Historico E2E" not in body


@pytest.mark.p0
def test_cen_inseval_003_create_por_instrutor_ou_link(page_as, anonymous_page, manifest):
    page = page_as("docente")
    response = page.goto(f"{BASE_URL}/instructorevaluations/create?selectionID={manifest['selections']['ativo']['id']}")
    assert response.status == 200
    expect(page.get_by_role("heading", name="Cadastrar Avaliação de Monitor")).to_be_visible()

    assert anonymous_page.goto(manifest["urls"]["instructor_evaluation_signed"]).status == 200


@pytest.mark.p0
def test_cen_inseval_004_create_fora_da_janela(page_as, manifest):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/instructorevaluations/create?selectionID={manifest['selections']['concluido']['id']}")
    expect(page.locator("body")).to_contain_text("Período de avaliação encerrado")


@pytest.mark.p2
def test_cen_inseval_005_create_por_nao_instrutor_negado(page_as, manifest):
    page = page_as("docente_sem_turma")
    page.goto(f"{BASE_URL}/instructorevaluations/create?selectionID={manifest['selections']['ativo']['id']}")
    expect(page.locator("body")).to_contain_text("não esta sob sua responsabilidade")


@pytest.mark.p0
def test_cen_inseval_006_store_cria_e_atualiza(page_as, manifest, temp):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/instructors/evaluations")
    payload = {
        "selection_id": str(manifest["selections"]["ativo"]["id"]),
        "selection_hash": "hash-pelo-docente",
        "ease_of_contact": "2",
        "efficiency": "1",
        "reliability": "2",
        "overall": "1",
        "comments": "Avaliação E2E",
    }
    response = http.submit(page, "POST", "/instructorevaluations", payload)
    http.goto_response(page, response)
    row = db.fetch_one(
        "select id, overall from instructor_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    )
    assert row is not None
    temp.append(("delete from instructor_evaluations where id=%s", (row["id"],)))
    assert row["overall"] == 1

    page.goto(f"{BASE_URL}/instructors/evaluations")
    payload["overall"] = "2"
    response = http.submit(page, "POST", "/instructorevaluations", payload)
    http.goto_response(page, response)
    assert db.scalar(
        "select count(*) from instructor_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    ) == 1


@pytest.mark.p0
def test_cen_inseval_007_store_por_link_valida_hash(anonymous_page, manifest, temp):
    anonymous_page.goto(manifest["urls"]["instructor_evaluation_signed"])
    selection_hash = anonymous_page.locator("input[name=selection_hash]").input_value()
    payload = {
        "selection_id": str(manifest["selections"]["ativo"]["id"]),
        "selection_hash": selection_hash,
        "ease_of_contact": "1",
        "efficiency": "1",
        "reliability": "1",
        "overall": "1",
    }
    response = http.submit(anonymous_page, "POST", "/instructorevaluations", payload)
    row = db.fetch_one(
        "select id from instructor_evaluations where selection_id=%s",
        (manifest["selections"]["ativo"]["id"],),
    )
    assert row is not None
    temp.append(("delete from instructor_evaluations where id=%s", (row["id"],)))

    anonymous_page.goto(manifest["urls"]["instructor_evaluation_signed"])
    payload["selection_hash"] = "invalido"
    response = http.submit(anonymous_page, "POST", "/instructorevaluations", payload, follow=True)
    assert response.status == 200


@pytest.mark.p1
def test_cen_inseval_008_validacao_do_store(page_as, manifest):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/instructors/evaluations")
    response = http.submit(
        page,
        "POST",
        "/instructorevaluations",
        {
            "selection_id": str(manifest["selections"]["ativo"]["id"]),
            "selection_hash": "x",
            "ease_of_contact": "9",
            "efficiency": "9",
            "reliability": "9",
            "overall": "9",
            "comments": "x" * 65537,
        },
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "selected ease of contact is invalid" in body
    assert "comments must not be greater than 65536 characters" in body


@pytest.mark.p1
def test_cen_inseval_009_show_dono_ou_permissao(page_as, manifest):
    evaluation_id = manifest["instructor_evaluations"]["concluido"]
    assert page_as("docente").goto(f"{BASE_URL}/instructorevaluations/{evaluation_id}").status == 200
    assert page_as("admin").goto(f"{BASE_URL}/instructorevaluations/{evaluation_id}").status == 200
    assert page_as("docente_sem_turma").goto(f"{BASE_URL}/instructorevaluations/{evaluation_id}").status == 403


@pytest.mark.p1
def test_cen_inseval_010_edit_update_restrito_ao_instrutor(page_as, manifest, temp):
    evaluation_id = manifest["instructor_evaluations"]["concluido"]
    original = db.fetch_one("select overall from instructor_evaluations where id=%s", (evaluation_id,))
    temp.append(("update instructor_evaluations set overall=%s where id=%s", (original["overall"], evaluation_id)))

    outsider = page_as("docente_sem_turma")
    outsider.goto(f"{BASE_URL}/instructorevaluations/{evaluation_id}/edit")
    expect(outsider.locator("body")).to_contain_text("não esta sob sua responsabilidade")

    owner = page_as("docente")
    assert owner.goto(f"{BASE_URL}/instructorevaluations/{evaluation_id}/edit").status == 200
    owner.goto(f"{BASE_URL}/instructors/evaluations")
    response = http.submit(
        owner,
        "PUT",
        f"/instructorevaluations/{evaluation_id}",
        {"ease_of_contact": "0", "efficiency": "0", "reliability": "0", "overall": "0"},
    )
    http.goto_response(owner, response)
    assert db.scalar("select overall from instructor_evaluations where id=%s", (evaluation_id,)) == 0
