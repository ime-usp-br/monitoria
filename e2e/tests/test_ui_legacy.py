import re

import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


def _schoolclass_edit_url(manifest: dict) -> str:
    return f"{BASE_URL}/schoolclasses/{manifest['classes']['mac_a']['id']}/edit"


def _open_modal(page, trigger: str, modal: str) -> None:
    page.locator(trigger).first.click()
    expect(page.locator(modal)).to_be_visible()


@pytest.mark.p0
def test_ui_legacy_datepicker_inicializa_e_abre_calendario(page_as, manifest):
    page = page_as("secretaria")
    page.goto(_schoolclass_edit_url(manifest), wait_until="domcontentloaded")

    datepicker = page.locator(".custom-datepicker").first
    expect(datepicker).to_have_class(re.compile(r"hasDatepicker"))
    datepicker.click()
    expect(page.locator("#ui-datepicker-div")).to_be_visible()

    month = page.locator(".ui-datepicker-month").inner_text()
    assert month in {
        "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho",
        "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro",
    }
    page.keyboard.press("Escape")


@pytest.mark.p0
def test_ui_legacy_modal_horario_adiciona_horario_valido(page_as, manifest):
    page = page_as("secretaria")
    page.goto(_schoolclass_edit_url(manifest), wait_until="domcontentloaded")

    _open_modal(page, "#btn-addCassSchedule", "#addClassScheduleModal")
    page.select_option("#diasmnocp-add", "seg")
    page.fill("#horent-add", "08:00")
    page.fill("#horsai-add", "10:00")
    page.locator("#btn-addClassSchedule2").click()

    novos = page.locator("#novos-horarios")
    expect(novos).to_contain_text("seg 08:00 10:00")
    expect(novos.locator("input[name='horarios[new1][diasmnocp]']")).to_have_value("seg")
    expect(novos.locator("input[name='horarios[new1][horent]']")).to_have_value("08:00")
    expect(novos.locator("input[name='horarios[new1][horsai]']")).to_have_value("10:00")


@pytest.mark.p1
def test_ui_legacy_modal_horario_valida_campos_obrigatorios(page_as, manifest):
    page = page_as("secretaria")
    page.goto(_schoolclass_edit_url(manifest), wait_until="domcontentloaded")

    _open_modal(page, "#btn-addCassSchedule", "#addClassScheduleModal")
    page.locator("#btn-addClassSchedule2").click()

    expect(page.locator("#horent-add-error-div")).to_contain_text("Informe a hora de entrada")
    expect(page.locator("#horsai-add-error-div")).to_contain_text("Informe a hora de saida")
    expect(page.locator("#novos-horarios > div")).to_have_count(0)


@pytest.mark.p0
def test_ui_legacy_modal_instrutor_busca_e_adiciona_docente(page_as, manifest):
    page = page_as("secretaria")
    page.goto(_schoolclass_edit_url(manifest), wait_until="domcontentloaded")

    docente = manifest["users"]["docente"]
    _open_modal(page, "#btn-addInstructor", "#addInstructorModal")
    page.fill("#codpes-add", str(docente["codpes"]))
    page.locator("#btn-addInstructor2").click()

    novos = page.locator("#novos-instrutores")
    expect(novos).to_contain_text(docente["name"])
    expect(novos.locator("input[name='instrutores[new1][codpes]']")).to_have_value(
        str(docente["codpes"])
    )


@pytest.mark.p1
def test_ui_legacy_modal_instrutor_rejeita_codpes_inexistente(page_as, manifest):
    page = page_as("secretaria")
    page.goto(_schoolclass_edit_url(manifest), wait_until="domcontentloaded")

    _open_modal(page, "#btn-addInstructor", "#addInstructorModal")
    page.fill("#codpes-add", "999999")
    page.locator("#btn-addInstructor2").click()

    expect(page.locator("#codpes-div")).to_contain_text("Docente não encontrado")
    expect(page.locator("#novos-instrutores > div")).to_have_count(0)


@pytest.mark.p0
def test_ui_legacy_busca_aluno_no_modal_de_selecao(page_as, manifest):
    class_id = manifest["classes"]["mac_a"]["id"]
    aluno = manifest["users"]["aluno_com_historico"]

    page = page_as("secretaria")
    page.goto(f"{BASE_URL}/selections/{class_id}/enrollments", wait_until="domcontentloaded")
    _open_modal(page, 'a[data-target="#selectUnenrolledStudentModal"]', "#selectUnenrolledStudentModal")

    page.fill("#codpes-select", str(aluno["codpes"]))
    page.locator("#btn-searchSelectModal").click()
    expect(page.locator("#select-student-div")).to_contain_text(aluno["name"])
    expect(page.locator("#select-student-div input[name='codpes']")).to_have_value(
        str(aluno["codpes"])
    )

    page.fill("#codpes-select", "")
    page.fill("#nompes-select", "Aluno")
    page.locator("#btn-searchSelectModal").click()
    expect(page.locator("#select-student-div input[name='codpes']").first).to_be_visible()

    page.fill("#nompes-select", "")
    page.fill("#codpes-select", "999999")
    page.locator("#btn-searchSelectModal").click()
    expect(page.locator("#msn-div")).to_contain_text("Aluno não encontrado")


@pytest.mark.p0
def test_ui_legacy_modal_indicacao_busca_e_adiciona_aluno(page_as, manifest):
    requisition_id = manifest["requisitions"]["mac_a"]["id"]
    aluno = manifest["users"]["aluno_com_historico"]

    page = page_as("docente")
    page.goto(f"{BASE_URL}/requisitions/{requisition_id}/edit", wait_until="domcontentloaded")
    _open_modal(page, "#btn-addRecommendation", "#addRecommendationModal")

    page.fill("#codpes-add", str(aluno["codpes"]))
    page.locator("#btn-addRecommendationModal").click()
    expect(page.locator("#select-student-div")).to_contain_text(aluno["name"])

    page.locator("#chosen-student").check()
    page.locator("#btn-chooseRecommendationModal").click()

    novas = page.locator("#novas-indicacoes")
    expect(novas).to_contain_text(aluno["name"])
    expect(novas.locator("input[name='recommendations[new1][codpes]']")).to_have_value(
        str(aluno["codpes"])
    )


@pytest.mark.p0
def test_ui_legacy_datatable_inicializa_com_botoes_de_exportacao(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors", wait_until="domcontentloaded")

    table = page.locator("#table_id")
    expect(table).to_have_class(re.compile(r"dataTable"))
    expect(page.locator(".dataTables_wrapper")).to_be_visible()
    expect(page.locator(".dt-buttons")).to_be_visible()
    assert page.locator(".dt-buttons .dt-button").count() >= 4
    expect(page.locator(".dataTables_filter input")).to_be_visible()


@pytest.mark.p1
def test_ui_legacy_modal_remocao_define_action_do_form(page_as, manifest):
    page = page_as("admin")
    page.goto(
        f"{BASE_URL}/schoolclasses?periodoId={manifest['terms']['open']['id']}",
        wait_until="domcontentloaded",
    )

    _open_modal(page, 'a[data-target="#removalModal"]', "#removalModal")

    form = page.locator("#removalModal .modal-footer form")
    action = form.get_attribute("action")
    assert action is not None and re.search(r"/schoolclasses/\d+$", action), f"action={action!r}"
    method = form.locator("input[name='_method']").get_attribute("value")
    assert (method or "").upper() == "DELETE", f"method={method!r} html={form.inner_html()}"


@pytest.mark.p1
def test_ui_legacy_modal_revogacao_define_action_do_form(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors", wait_until="domcontentloaded")

    _open_modal(page, "#btn-revokeModal", "#revokeModal")

    form = page.locator("#revokeModal #revokeForm")
    action = form.get_attribute("action")
    assert action is not None and re.search(r"/tutors/revoke/\d+$", action), f"action={action!r}"
    method = form.locator("input[name='_method']").get_attribute("value")
    assert (method or "").upper() == "PATCH", f"method={method!r}"
