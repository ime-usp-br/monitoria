import pytest
from playwright.sync_api import expect

from support.manifest import BASE_URL


@pytest.mark.p0
def test_admin_ve_monitor_ativo(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/tutors")

    expect(page.get_by_role("heading", name="Monitores")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")
    expect(page.locator("body")).to_contain_text("Ativo")


@pytest.mark.p1
def test_membro_mae_nao_ve_monitor_do_mac(page_as):
    page = page_as("membro_comissao_mae")
    page.goto(f"{BASE_URL}/tutors")

    assert "Aluno Monitor Ativo E2E" not in page.locator("body").inner_text()
