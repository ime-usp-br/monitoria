from datetime import datetime

import pytest
from playwright.sync_api import expect

from support.db import fetch_one
from support.manifest import BASE_URL


@pytest.mark.p0
def test_docente_ve_monitor_ativo_na_frequencia(page_as, manifest):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/frequencies")

    expect(page.get_by_role("heading", name="Frequência dos Monitores")).to_be_visible()
    expect(page.locator("table")).to_contain_text("Aluno Monitor Ativo E2E")
    expect(page.locator("table")).to_contain_text("MAC0202")


@pytest.mark.p0
def test_link_assinado_registra_frequencia(page_as, manifest):
    page = page_as("docente")
    page.goto(manifest["urls"]["frequency_signed"])

    expect(page.get_by_role("heading", name="Registro de Frequência")).to_be_visible()

    months = sorted(
        int(key.rsplit("_", 1)[1])
        for key in manifest["frequencies"]
        if key.startswith("nao_registrada_")
    )
    today = datetime.now()
    target = next(
        (month for month in months if month < today.month or (month == today.month and today.day >= 20)),
        None,
    )
    if target is None:
        pytest.skip("Nenhum mês passado/liberado para registrar frequência neste período.")

    before = fetch_one(
        "select registered from frequencies where student_id = %s and school_class_id = %s and month = %s",
        (
            manifest["selections"]["ativo"]["student_id"],
            manifest["selections"]["ativo"]["school_class_id"],
            target,
        ),
    )
    assert before["registered"] == 0

    row = page.locator("table tr").filter(has=page.get_by_role("cell", name=str(target), exact=True))
    expect(row).to_contain_text("Não")
    row.get_by_role("link", name="Registrar", exact=True).click()
    page.wait_for_load_state("domcontentloaded")

    expect(page.get_by_role("heading", name="Confirmar Frequência")).to_be_visible()
    page.get_by_role("button", name="Confirmar registro").click()
    page.wait_for_load_state("domcontentloaded")

    after = fetch_one(
        "select registered from frequencies where student_id = %s and school_class_id = %s and month = %s",
        (
            manifest["selections"]["ativo"]["student_id"],
            manifest["selections"]["ativo"]["school_class_id"],
            target,
        ),
    )
    assert after["registered"] == 1


@pytest.mark.p1
def test_aluno_nao_acessa_frequencia(page_as):
    page = page_as("aluno_com_historico")
    response = page.goto(f"{BASE_URL}/frequencies")
    assert response is not None
    assert response.status == 403
