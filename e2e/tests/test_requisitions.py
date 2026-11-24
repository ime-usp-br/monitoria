import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.manifest import BASE_URL

ACTIVITIES = (
    "Atendimento a alunos",
    "Correção de listas de exercícios",
    "Fiscalização de provas",
)


def _requisition_payload(class_id, **overrides):
    payload = {
        "school_class_id": str(class_id),
        "requested_number": "2",
        "priority": "2",
        "activities[0]": ACTIVITIES[0],
        "activities[1]": ACTIVITIES[1],
        "scholarships[]": "1",
        "comments": "Solicitação E2E",
    }
    payload.update(overrides)
    return payload


def _new_requisition(manifest, temp):
    class_id = state.add_class(
        manifest, temp, coddis="MAC0994", codtur="20990011", requisition=False
    )
    return class_id


@pytest.mark.p0
def test_docente_ve_suas_turmas_no_periodo_de_solicitacao(page_as):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/requisitions")

    expect(page.get_by_role("heading", name="Solicitação de Monitores")).to_be_visible()
    expect(page.locator("body")).to_contain_text("MAC0101")


@pytest.mark.p1
def test_aluno_nao_acessa_solicitacoes(page_as):
    page = page_as("aluno_com_historico")
    response = page.goto(f"{BASE_URL}/requisitions")
    assert response is not None
    assert response.status == 403


@pytest.mark.p0
def test_cen_requisition_005_index_lista_apenas_turmas_do_docente(page_as):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/requisitions")
    body = page.locator("body").inner_text()
    assert "MAC0101" in body
    assert "MAC0202" in body
    assert "MAE0101" not in body
    assert "MAT0101" not in body


@pytest.mark.p0
def test_cen_requisition_006_create_exige_instrutor(page_as, manifest):
    page = page_as("docente_sem_turma")
    response = page.goto(f"{BASE_URL}/requisitions/create?school_class_id={manifest['classes']['mac_a']['id']}")
    assert response.status == 403


@pytest.mark.p1
def test_cen_requisition_007_create_exige_periodo_de_solicitacao(page_as, manifest):
    page = page_as("docente")
    page.goto(
        f"{BASE_URL}/requisitions/create?school_class_id={manifest['classes']['closed_a']['id']}"
    )
    expect(page.locator("body")).to_contain_text("Período de solicitação de monitores encerrado")


@pytest.mark.p0
def test_cen_requisition_008_store_cria_com_instrutor_autenticado(page_as, manifest, temp):
    class_id = _new_requisition(manifest, temp)
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/requisitions", _requisition_payload(class_id))
    http.goto_response(page, response)

    row = db.fetch_one("select * from requisitions where school_class_id=%s", (class_id,))
    assert row is not None
    assert row["instructor_id"] == manifest["instructors"]["20001"]
    assert row["requested_number"] == 2
    assert row["priority"] == "2"
    assert row["comments"] == "Solicitação E2E"


@pytest.mark.p0
def test_cen_requisition_009_store_anexa_atividades(page_as, manifest, temp):
    class_id = _new_requisition(manifest, temp)
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(page, "POST", "/requisitions", _requisition_payload(class_id))
    http.goto_response(page, response)

    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    activities = db.fetch_all(
        "select a.description from activity_requisition ar "
        "join activities a on a.id = ar.activity_id where ar.requisition_id=%s order by a.id",
        (requisition_id,),
    )
    assert [row["description"] for row in activities] == [ACTIVITIES[0], ACTIVITIES[1]]


@pytest.mark.p0
def test_cen_requisition_010_store_cria_recomendacoes(page_as, manifest, temp):
    class_id = _new_requisition(manifest, temp)
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "POST",
        "/requisitions",
        _requisition_payload(class_id, **{"recommendations[0][codpes]": "160001"}),
    )
    http.goto_response(page, response)

    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    assert db.scalar(
        "select count(*) from recommendations where requisition_id=%s and student_id=%s",
        (requisition_id, manifest["students"]["160001"]),
    ) == 1


@pytest.mark.p1
def test_cen_requisition_011_store_anexa_bolsas(page_as, manifest, temp):
    class_id = _new_requisition(manifest, temp)
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "POST",
        "/requisitions",
        _requisition_payload(class_id, **{"scholarships[]": "2"}),
    )
    http.goto_response(page, response)

    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    assert db.scalar(
        "select count(*) from model_has_scholarships "
        "where model_type='App\\\\Models\\\\Requisition' and model_id=%s and scholarship_id=2",
        (requisition_id,),
    ) == 1


@pytest.mark.p0
def test_cen_requisition_012_validacao_do_formulario(page_as, manifest):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/requisitions/create?school_class_id={manifest['classes']['mac_a']['id']}")
    response = http.submit(
        page,
        "POST",
        "/requisitions",
        {
            "school_class_id": "abc",
            "requested_number": "0",
            "priority": "4",
            "activities[0]": "Atividade inválida",
            "recommendations[0][codpes]": "abc",
            "scholarships[]": "999",
        },
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "school class id must be a number" in body
    assert "requested number must be greater than 0" in body
    assert "selected priority is invalid" in body
    assert "selected activities.0 is invalid" in body
    assert "recommendations.0.codpes must be a number" in body
    assert "selected scholarships.0 is invalid" in body


@pytest.mark.p0
def test_cen_requisition_013_update_substitui_atividades(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0993", codtur="20990013")
    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    page = page_as("docente")
    page.goto(BASE_URL)
    payload = _requisition_payload(class_id)
    payload.pop("activities[1]")
    payload["activities[0]"] = ACTIVITIES[2]
    response = http.submit(
        page,
        "PATCH",
        f"/requisitions/{requisition_id}",
        payload,
    )
    http.goto_response(page, response)

    activities = db.fetch_all(
        "select a.description from activity_requisition ar "
        "join activities a on a.id = ar.activity_id where ar.requisition_id=%s",
        (requisition_id,),
    )
    assert [row["description"] for row in activities] == [ACTIVITIES[2]]


@pytest.mark.p1
def test_cen_requisition_014_update_recria_recomendacoes(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0992", codtur="20990014")
    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    db.execute(
        "insert into recommendations (student_id, requisition_id, created_at, updated_at) "
        "values (%s, %s, now(), now())",
        (manifest["students"]["160001"], requisition_id),
    )
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/requisitions/{requisition_id}",
        _requisition_payload(class_id, **{"recommendations[0][codpes]": "160003"}),
    )
    http.goto_response(page, response)

    rows = db.fetch_all(
        "select student_id from recommendations where requisition_id=%s", (requisition_id,)
    )
    assert [row["student_id"] for row in rows] == [manifest["students"]["160003"]]


@pytest.mark.p1
def test_cen_requisition_015_update_ressincroniza_bolsas(page_as, manifest, temp):
    class_id = state.add_class(manifest, temp, coddis="MAC0991", codtur="20990015")
    requisition_id = db.scalar("select id from requisitions where school_class_id=%s", (class_id,))
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "PATCH",
        f"/requisitions/{requisition_id}",
        _requisition_payload(class_id, **{"scholarships[]": "1"}),
    )
    http.goto_response(page, response)

    scholarships = db.fetch_all(
        "select scholarship_id from model_has_scholarships "
        "where model_type='App\\\\Models\\\\Requisition' and model_id=%s",
        (requisition_id,),
    )
    assert [row["scholarship_id"] for row in scholarships] == [1]


@pytest.mark.p1
def test_cen_requisition_016_edit_exige_turma_propria(page_as, manifest):
    page = page_as("membro_comissao")
    response = page.goto(f"{BASE_URL}/requisitions/{manifest['requisitions']['mae_a']['id']}/edit")
    assert response.status == 403


@pytest.mark.p1
def test_cen_requisition_018_assinatura_do_instrutor_autenticado(page_as, manifest, temp):
    class_id = _new_requisition(manifest, temp)
    page = page_as("docente")
    page.goto(BASE_URL)
    response = http.submit(
        page,
        "POST",
        "/requisitions",
        _requisition_payload(class_id, instructor_id=str(manifest["instructors"]["20005"])),
    )
    http.goto_response(page, response)

    row = db.fetch_one("select instructor_id from requisitions where school_class_id=%s", (class_id,))
    assert row["instructor_id"] == manifest["instructors"]["20001"]
