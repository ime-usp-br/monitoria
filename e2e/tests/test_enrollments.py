import pytest
from playwright.sync_api import expect

from support.db import fetch_one
from support.manifest import BASE_URL


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
    row = fetch_one(
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
