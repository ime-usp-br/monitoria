from datetime import datetime

import pytest
from playwright.sync_api import expect

from support import db, http, mailpit
from support.manifest import BASE_URL


def _addresses(item):
    return [address.get("Address", "") for address in (item.get("To") or [])]


def _body(message_id):
    detail = mailpit.message(message_id)
    return (detail.get("Text") or "") + (detail.get("HTML") or "")


def _deactivate(temp, mail_class):
    template = db.fetch_one("select id, active from mail_templates where mail_class=%s", (mail_class,))
    db.execute("update mail_templates set active=0 where id=%s", (template["id"],))
    temp.append(("update mail_templates set active=%s where id=%s", (template["active"], template["id"])))
    return template


@pytest.mark.p0
def test_cen_email_001_exige_permissao(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/emails").status == 403
    assert page.goto(f"{BASE_URL}/emails/selections").status == 403
    assert page.goto(f"{BASE_URL}/emails/attendanceRecords").status == 403


@pytest.mark.p0
def test_cen_email_002_index_selections_lista_turmas_com_selecoes(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/selections")
    expect(page.get_by_role("heading", name="Comunicar Resultado das Seleções")).to_be_visible()
    expect(page.locator("body")).to_contain_text("MAC0202")


@pytest.mark.p1
def test_cen_email_003_index_attendance_valida_meses_do_periodo(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/attendanceRecords?month=8")
    expect(page.get_by_role("heading", name="Informar sobre Frequências")).to_be_visible()
    expect(page.locator("body")).to_contain_text("agosto")

    page.goto(f"{BASE_URL}/emails/attendanceRecords?month=11")
    expect(page.locator("body")).to_contain_text("novembro")


@pytest.mark.p1
def test_cen_email_004_index_attendance_rejeita_mes_invalido(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails")
    page.goto(f"{BASE_URL}/emails/attendanceRecords?month=99")
    expect(page.locator("body")).to_contain_text("Mês invalido")


@pytest.mark.p1
def test_cen_email_005_index_attendance_deriva_mes_atual(page_as):
    page = page_as("admin")
    response = page.goto(f"{BASE_URL}/emails/attendanceRecords")
    assert response.status == 200
    expect(page.get_by_role("heading", name="Informar sobre Frequências")).to_be_visible()


@pytest.mark.p0
def test_cen_email_006_index_attendance_lista_nao_registradas(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/attendanceRecords?month=11")
    expect(page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")


@pytest.mark.p0
def test_cen_email_007_index_self_evaluations(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/selfEvaluations")
    expect(page.get_by_role("heading", name="Informar sobre Auto Avaliações")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")


@pytest.mark.p0
def test_cen_email_008_index_instructor_evaluations(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/instructorEvaluations")
    expect(page.get_by_role("heading", name="Informar sobre Avaliações")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")


@pytest.mark.p1
def test_cen_email_009_trigger_selections_exige_modelos(page_as, temp):
    _deactivate(temp, "NotifyInstructorAboutSelectAssistant")
    _deactivate(temp, "NotifySelectStudent")
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/selections")
    response = http.submit(
        page,
        "POST",
        "/emails/triggerSelections",
        {"school_classes_id[]": "3"},
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("modelo de e-mail ativo")


@pytest.mark.p0
def test_cen_email_010_trigger_selections_envia(page_as, manifest):
    mailpit.clear()
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/selections")
    response = http.submit(
        page,
        "POST",
        "/emails/triggerSelections",
        {"school_classes_id[]": str(manifest["classes"]["mac_monitor"]["id"])},
    )
    http.goto_response(page, response)

    mailpit.wait_for(lambda item: "docente.mac@monitoria.e2e" in _addresses(item))
    assert any("aluno.monitor@monitoria.e2e" in _addresses(item) for item in mailpit.messages())


@pytest.mark.p1
def test_cen_email_011_trigger_attendance_exige_modelo(page_as, manifest, temp):
    _deactivate(temp, "NotifyInstructorAboutAttendanceRecord")
    frequency_key = next(key for key in manifest["frequencies"] if key.startswith("nao_registrada_"))
    frequency_id = manifest["frequencies"][frequency_key]
    month = int(frequency_key.rsplit("_", 1)[1])
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/attendanceRecords?month={month}")
    response = http.submit(
        page,
        "POST",
        "/emails/triggerAttendanceRecords",
        {"frequencies_id[]": str(frequency_id)},
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("modelo de e-mail ativo")


@pytest.mark.p0
def test_cen_email_012_trigger_attendance_envia_com_url_assinada(page_as, manifest):
    mailpit.clear()
    frequency_key = next(key for key in manifest["frequencies"] if key.startswith("nao_registrada_"))
    frequency_id = manifest["frequencies"][frequency_key]
    month = int(frequency_key.rsplit("_", 1)[1])
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/attendanceRecords?month={month}")
    response = http.submit(
        page,
        "POST",
        "/emails/triggerAttendanceRecords",
        {"frequencies_id[]": str(frequency_id)},
    )
    http.goto_response(page, response)

    message = mailpit.wait_for(lambda item: "docente.mac@monitoria.e2e" in _addresses(item))
    assert "signature=" in _body(message["ID"])


@pytest.mark.p0
def test_cen_email_014_trigger_self_evaluations(page_as, manifest):
    mailpit.clear()
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/selfEvaluations")
    response = http.submit(
        page,
        "POST",
        "/emails/triggerSelfEvaluations",
        {"selections_id[]": str(manifest["selections"]["ativo"]["id"])},
    )
    http.goto_response(page, response)

    message = mailpit.wait_for(lambda item: "aluno.monitor@monitoria.e2e" in _addresses(item))
    assert "selfevaluations/create" in _body(message["ID"])


@pytest.mark.p0
def test_cen_email_015_trigger_instructor_evaluations(page_as, manifest):
    mailpit.clear()
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/instructorEvaluations")
    response = http.submit(
        page,
        "POST",
        "/emails/triggerInstructorEvaluations",
        {"selections_id[]": str(manifest["selections"]["ativo"]["id"])},
    )
    http.goto_response(page, response)

    message = mailpit.wait_for(lambda item: "docente.mac@monitoria.e2e" in _addresses(item))
    assert "instructorevaluations/create" in _body(message["ID"])


@pytest.mark.p1
def test_cen_email_016_payload_valida_arrays(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails/selections")
    response = http.submit(page, "POST", "/emails/triggerSelections", {})
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("school classes id field is required")
