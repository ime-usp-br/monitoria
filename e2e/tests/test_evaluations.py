import pytest
from playwright.sync_api import expect

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
