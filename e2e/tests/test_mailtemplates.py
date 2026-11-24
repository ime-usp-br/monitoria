import pytest
from playwright.sync_api import expect

from support import db, http, mailpit, state
from support.manifest import BASE_URL


@pytest.mark.p0
def test_admin_lista_modelos_de_email(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")

    expect(page.get_by_role("heading", name="E-mails")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Notificar aluno selecionado")
    expect(page.locator("body")).to_contain_text("Solicitação de certificado")


@pytest.mark.p1
def test_admin_acessa_area_de_disparos(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/emails")
    expect(page.locator("body")).to_contain_text("E-mails")


@pytest.mark.p1
def test_docente_nao_edita_modelos(page_as):
    page = page_as("docente")
    response = page.goto(f"{BASE_URL}/mailtemplates")
    assert response is not None
    assert response.status == 403


def _description(mail_class="NotifySelectStudent", description="Descrição E2E"):
    import json

    return json.dumps({"description": description, "mail_class": mail_class})


@pytest.mark.p0
def test_cen_mailtemplate_001_crud_exige_permissao(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/mailtemplates").status == 403
    assert page.goto(f"{BASE_URL}/mailtemplates/create").status == 403


@pytest.mark.p0
def test_cen_mailtemplate_002_store_decodifica_campo(page_as, temp):
    name = "Template E2E 002"
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates/create")
    response = http.submit(
        page,
        "POST",
        "/mailtemplates",
        {
            "name": name,
            "description_and_mail_class": _description(),
            "sending_frequency": "Manual",
            "subject": "Assunto E2E",
            "body": "<p>Corpo E2E</p>",
        },
    )
    http.goto_response(page, response)
    row = db.fetch_one("select id, description, mail_class from mail_templates where name=%s", (name,))
    assert row is not None
    temp.append(("delete from mail_templates where id=%s", (row["id"],)))
    assert row["description"] == "Descrição E2E"
    assert row["mail_class"] == "NotifySelectStudent"


@pytest.mark.p1
def test_cen_mailtemplate_003_store_rejeita_nome_duplicado(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates/create")
    response = http.submit(
        page,
        "POST",
        "/mailtemplates",
        {
            "name": "Notificar aluno selecionado",
            "description_and_mail_class": _description(),
            "sending_frequency": "Manual",
            "subject": "Assunto E2E",
            "body": "<p>Corpo E2E</p>",
        },
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Já existe um modelo com esse nome")


@pytest.mark.p1
def test_cen_mailtemplate_004_validacao_de_campos(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates/create")
    response = http.submit(
        page,
        "POST",
        "/mailtemplates",
        {
            "description_and_mail_class": _description(),
            "sending_frequency": "Única",
            "subject": "s" * 257,
            "body": "b" * 8193,
        },
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "name field is required" in body
    assert "subject must not be greater than 256 characters" in body
    assert "body must not be greater than 8192 characters" in body
    assert "sending date field is required unless sending frequency is in Manual" in body


@pytest.mark.p1
def test_cen_mailtemplate_005_update_rejeita_nome_duplicado(page_as, temp):
    template_id = state.add_mail_template(
        temp,
        name="Template E2E 005",
        mail_class="NotifyInstructorAboutSelectAssistant",
        sending_frequency="Única",
        sending_date="01/01/2030",
        sending_hour="08:00",
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates/{template_id}/edit")
    response = http.submit(
        page,
        "PATCH",
        f"/mailtemplates/{template_id}",
        {
            "name": "Notificar aluno selecionado",
            "description_and_mail_class": _description("NotifyInstructorAboutSelectAssistant"),
            "sending_frequency": "Única",
            "sending_date": "01/01/2030",
            "sending_hour": "08:00",
            "subject": "Assunto",
            "body": "<p>Corpo</p>",
        },
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Já existe um modelo com esse nome")

    page.goto(f"{BASE_URL}/mailtemplates/{template_id}/edit")
    response = http.submit(
        page,
        "PATCH",
        f"/mailtemplates/{template_id}",
        {
            "name": "Template E2E 005",
            "description_and_mail_class": _description("NotifyInstructorAboutSelectAssistant"),
            "sending_frequency": "Única",
            "sending_date": "01/01/2030",
            "sending_hour": "08:00",
            "subject": "Assunto",
            "body": "<p>Corpo</p>",
        },
    )
    assert response.status == 302
    assert db.scalar("select name from mail_templates where id=%s", (template_id,)) == "Template E2E 005"


@pytest.mark.p1
def test_cen_mailtemplate_006_update_rejeita_segundo_manual_ativo(page_as, temp):
    template_id = state.add_mail_template(
        temp, name="Template E2E 006", mail_class="NotifySelectStudent", sending_frequency="Manual"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates/{template_id}/edit")
    response = http.submit(
        page,
        "PATCH",
        f"/mailtemplates/{template_id}",
        {
            "name": "Template E2E 006",
            "description_and_mail_class": _description("NotifySelectStudent"),
            "sending_frequency": "Manual",
            "subject": "Assunto",
            "body": "<p>Corpo</p>",
        },
    )
    http.goto_response(page, response)
    expect(page.locator("body")).to_contain_text("Já existe um modelo ativo com essa aplicação")


@pytest.mark.p1
def test_cen_mailtemplate_007_manual_limpa_data_e_hora(page_as, temp):
    seed_id = db.scalar(
        "select id from mail_templates where mail_class='NotifyInstructorAboutSelectAssistant'"
    )
    was_active = db.scalar("select active from mail_templates where id=%s", (seed_id,))
    db.execute("update mail_templates set active=0 where id=%s", (seed_id,))
    temp.append(("update mail_templates set active=%s where id=%s", (was_active, seed_id)))

    template_id = state.add_mail_template(
        temp,
        name="Template E2E 007",
        mail_class="NotifyInstructorAboutSelectAssistant",
        sending_frequency="Mensal",
        sending_date="10",
        sending_hour="08:00",
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates/{template_id}/edit")
    response = http.submit(
        page,
        "PATCH",
        f"/mailtemplates/{template_id}",
        {
            "name": "Template E2E 007",
            "description_and_mail_class": _description("NotifyInstructorAboutSelectAssistant"),
            "sending_frequency": "Manual",
            "subject": "Assunto",
            "body": "<p>Corpo</p>",
        },
    )
    assert response.status == 302
    row = db.fetch_one("select sending_date, sending_hour from mail_templates where id=%s", (template_id,))
    assert row["sending_date"] is None
    assert row["sending_hour"] is None


@pytest.mark.p1
def test_cen_mailtemplate_008_activate_bloqueia_segundo_manual(page_as, temp):
    template_id = state.add_mail_template(
        temp, name="Template E2E 008", mail_class="NotifySelectStudent", sending_frequency="Manual"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")
    response = http.get(page, f"/mailtemplates/activate/{template_id}")
    assert response.status in (200, 302)
    assert db.scalar("select active from mail_templates where id=%s", (template_id,)) == 0


@pytest.mark.p1
def test_cen_mailtemplate_009_activate_valido(page_as, temp):
    seed_id = db.scalar(
        "select id from mail_templates where mail_class='NotifyInstructorAboutSelectAssistant'"
    )
    was_active = db.scalar("select active from mail_templates where id=%s", (seed_id,))
    db.execute("update mail_templates set active=0 where id=%s", (seed_id,))
    temp.append(("update mail_templates set active=%s where id=%s", (was_active, seed_id)))

    template_id = state.add_mail_template(
        temp,
        name="Template E2E 009",
        mail_class="NotifyInstructorAboutSelectAssistant",
        sending_frequency="Manual",
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")
    response = http.get(page, f"/mailtemplates/activate/{template_id}")
    assert response.status in (200, 302)
    assert db.scalar("select active from mail_templates where id=%s", (template_id,)) == 1


@pytest.mark.p1
def test_cen_mailtemplate_010_deactivate(page_as, temp):
    seed_id = db.scalar("select id from mail_templates where mail_class='NotifySelectStudent'")
    was_active = db.scalar("select active from mail_templates where id=%s", (seed_id,))
    db.execute("update mail_templates set active=0 where id=%s", (seed_id,))
    temp.append(("update mail_templates set active=%s where id=%s", (was_active, seed_id)))

    template_id = state.add_mail_template(
        temp, name="Template E2E 010", mail_class="NotifySelectStudent", sending_frequency="Manual"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")
    http.get(page, f"/mailtemplates/activate/{template_id}")
    response = http.get(page, f"/mailtemplates/deactivate/{template_id}")
    assert response.status in (200, 302)
    assert db.scalar("select active from mail_templates where id=%s", (template_id,)) == 0


@pytest.mark.p0
def test_cen_mailtemplate_011_test_envia_email(page_as, manifest):
    mailpit.clear()
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")
    response = http.submit(
        page,
        "POST",
        "/mailtemplates/test",
        {
            "mailtemplate_id": str(manifest["mail_templates"]["NotifySelectStudent"]),
            "email": "destino.teste@monitoria.e2e",
        },
    )
    http.goto_response(page, response)
    message = mailpit.wait_for(
        lambda item: "destino.teste@monitoria.e2e"
        in [address.get("Address", "") for address in (item.get("To") or [])]
    )
    assert message is not None


@pytest.mark.p2
def test_cen_mailtemplate_013_destroy_exclui(page_as, temp):
    template_id = state.add_mail_template(
        temp, name="Template E2E 013", mail_class="NotifySelectStudent", sending_frequency="Manual"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/mailtemplates")
    response = http.submit(page, "DELETE", f"/mailtemplates/{template_id}")
    assert response.status == 302
    assert db.fetch_one("select id from mail_templates where id=%s", (template_id,)) is None
