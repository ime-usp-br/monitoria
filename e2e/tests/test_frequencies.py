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


@pytest.mark.p0
def test_cen_frequency_003_show_via_url_assinada(anonymous_page, manifest):
    anonymous_page.goto(manifest["urls"]["frequency_signed"])
    expect(anonymous_page.get_by_role("heading", name="Registro de Frequência")).to_be_visible()
    expect(anonymous_page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")


@pytest.mark.p0
def test_cen_frequency_004_show_por_instrutor_e_negado_para_outro(page_as, manifest):
    docente = page_as("docente")
    response = docente.goto(
        f"{BASE_URL}/frequencies/{manifest['classes']['mac_monitor']['id']}/{manifest['selections']['ativo']['student_id']}"
    )
    assert response.status == 200

    outro = page_as("docente_sem_turma")
    outro.goto(f"{BASE_URL}/frequencies")
    outro.goto(
        f"{BASE_URL}/frequencies/{manifest['classes']['mac_monitor']['id']}/{manifest['selections']['ativo']['student_id']}"
    )
    expect(outro.locator("body")).to_contain_text("Você não ministra esta disciplina")


@pytest.mark.p1
def test_cen_frequency_005_show_negado_para_tutor_de_outra_turma(page_as, manifest):
    page = page_as("docente")
    page.goto(f"{BASE_URL}/frequencies")
    page.goto(
        f"{BASE_URL}/frequencies/{manifest['classes']['mac_monitor']['id']}/{manifest['students']['160001']}"
    )
    expect(page.locator("body")).to_contain_text("não pertence a disciplina")


@pytest.mark.p0
def test_cen_frequency_008_update_bloqueado_para_mes_futuro(anonymous_page, manifest):
    future = {
        int(key.rsplit("_", 1)[1]): value
        for key, value in manifest["frequencies"].items()
        if key.startswith("futura_")
    }
    assert future, "Fixture de frequência futura ausente"
    month, _ = next(iter(future.items()))
    if month <= datetime.now().month:
        pytest.skip("Mês futuro não disponível no calendário atual")

    anonymous_page.goto(manifest["urls"]["frequency_signed"])
    row = anonymous_page.locator("tr", has=anonymous_page.get_by_role("cell", name=str(month), exact=True))
    row.get_by_role("link", name="Registrar", exact=True).click()
    anonymous_page.wait_for_load_state("domcontentloaded")
    expect(anonymous_page.locator("body")).to_contain_text("só será liberada")


@pytest.mark.p2
def test_cen_frequency_011_update_requer_autorizacao(anonymous_page, manifest):
    frequency_id = next(
        value
        for key, value in manifest["frequencies"].items()
        if key.startswith("nao_registrada_")
    )
    response = anonymous_page.goto(f"{BASE_URL}/frequencies/{frequency_id}")
    assert response.status == 403
