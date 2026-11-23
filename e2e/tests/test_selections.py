import pytest
from playwright.sync_api import expect

from support.db import fetch_one
from support.manifest import BASE_URL


@pytest.mark.p0
def test_membro_comissao_ve_apenas_solicitacoes_do_departamento(page_as, manifest):
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/selections")

    expect(page.locator("body")).to_contain_text("Departamento de Ciência da Computação")
    expect(page.locator("table")).to_contain_text("MAC0101")
    assert "MAE0101" not in page.locator("body").inner_text()


@pytest.mark.p0
def test_membro_comissao_elege_monitor(page_as, manifest):
    page = page_as("membro_comissao")
    page.goto(f"{BASE_URL}/selections/{manifest['classes']['mac_a']['id']}/enrollments")

    expect(page.get_by_role("heading", name="Seleção de Monitores")).to_be_visible()
    row = page.locator("tr", has_text="Aluno Com Historico E2E")
    row.get_by_role("button", name="Elerger Monitor").click()

    page.wait_for_load_state("domcontentloaded")
    row = fetch_one(
        "select sitatl from selections where student_id = %s and school_class_id = %s",
        (manifest["students"]["160001"], manifest["classes"]["mac_a"]["id"]),
    )
    assert row is not None
    assert row["sitatl"] == "Ativo"


@pytest.mark.p1
def test_membro_de_outro_departamento_nao_elege(page_as, manifest):
    page = page_as("membro_comissao_mae")
    page.goto(f"{BASE_URL}/selections/{manifest['classes']['mac_a']['id']}/enrollments")

    row = page.locator("tr", has_text="Aluno Sem Historico E2E")
    row.get_by_role("button", name="Elerger Monitor").click()
    page.wait_for_load_state("domcontentloaded")

    row = fetch_one(
        "select sitatl from selections where student_id = %s and school_class_id = %s",
        (manifest["students"]["160003"], manifest["classes"]["mac_a"]["id"]),
    )
    assert row is None
