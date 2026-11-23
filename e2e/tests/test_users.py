import pytest
from playwright.sync_api import expect

from support import db, http, state
from support.auth import restore_identity
from support.manifest import BASE_URL


@pytest.mark.p1
def test_admin_lista_usuarios(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/users")

    expect(page.get_by_role("heading", name="Usuários")).to_be_visible()
    expect(page.locator("body")).to_contain_text("Administrador")
    expect(page.locator("body")).to_contain_text("160002")


@pytest.mark.p2
def test_secretaria_acessa_usuarios(page_as):
    page = page_as("secretaria")
    response = page.goto(f"{BASE_URL}/users")
    assert response is not None
    assert response.status == 200
    expect(page.get_by_role("heading", name="Usuários")).to_be_visible()


@pytest.mark.p0
def test_cen_user_001_index_exige_permissao(page_as):
    page = page_as("docente")
    assert page.goto(f"{BASE_URL}/users").status == 403
    assert page.goto(f"{BASE_URL}/users/search").status == 403
    assert page.goto(f"{BASE_URL}/users/loginas").status == 403


@pytest.mark.p1
def test_cen_user_002_index_ordena_papeis_especiais(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/users")
    body = page.locator("body").inner_text()
    assert body.index("Admin E2E") < body.index("Aluno Com Historico E2E")
    assert body.index("Secretaria E2E") < body.index("Servidor Sem Papel")


@pytest.mark.p0
def test_cen_user_003_update_desanexa_e_atribui_papel(page_as, temp):
    user_id = state.add_user(
        temp, codpes=990001, name="Usuario E2E 003", email="usuario.e2e.003@monitoria.e2e", role="Aluno"
    )
    docente_role = db.scalar("select id from roles where name='Docente'")
    db.execute(
        "insert into model_has_roles (role_id, model_type, model_id) "
        "values (%s, 'App\\\\Models\\\\User', %s)",
        (docente_role, user_id),
    )

    page = page_as("admin")
    page.goto(f"{BASE_URL}/users/{user_id}/edit")
    response = http.submit(
        page,
        "PATCH",
        f"/users/{user_id}",
        {
            "name": "Usuario E2E Atualizado",
            "email": "usuario.e2e.003@monitoria.e2e",
            "roles[]": "Secretaria",
        },
    )
    assert response.status == 302
    assert db.scalar("select name from users where id=%s", (user_id,)) == "Usuario E2E Atualizado"
    roles = db.fetch_all(
        "select r.name from roles r join model_has_roles m on m.role_id=r.id "
        "where m.model_type='App\\\\Models\\\\User' and m.model_id=%s",
        (user_id,),
    )
    assert [row["name"] for row in roles] == ["Secretaria"]


@pytest.mark.p1
def test_cen_user_004_validacao_do_user_request(page_as, temp):
    user_id = state.add_user(
        temp, codpes=990004, name="Usuario E2E 004", email="usuario.e2e.004@monitoria.e2e", role="Aluno"
    )
    page = page_as("admin")
    page.goto(f"{BASE_URL}/users/{user_id}/edit")
    response = http.submit(
        page,
        "PATCH",
        f"/users/{user_id}",
        {"name": "", "email": "admin@monitoria.e2e"},
    )
    http.goto_response(page, response)
    body = page.locator("body").inner_text()
    assert "name field is required" in body
    assert "email has already been taken" in body
    assert "roles field is required" in body


@pytest.mark.p0
def test_cen_user_005_search_filtra(page_as):
    page = page_as("admin")

    page.goto(f"{BASE_URL}/users/search?name=Aluno&codpes=")
    body = page.locator("body").inner_text()
    assert "Aluno Com Historico E2E" in body
    assert "Docente MAC E2E" not in body

    page.goto(f"{BASE_URL}/users/search?name=&codpes=20001")
    body = page.locator("body").inner_text()
    assert "Docente MAC E2E" in body
    assert "Aluno Com Historico E2E" not in body

    page.goto(f"{BASE_URL}/users/search?name=&codpes=&roles[]=Docente")
    body = page.locator("body").inner_text()
    assert "Docente MAC E2E" in body
    assert "Aluno Com Historico E2E" not in body


@pytest.mark.p1
def test_cen_user_006_loginas_renderiza(page_as):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/users/loginas")
    expect(page.get_by_role("heading", name="Logar Como")).to_be_visible()


@pytest.mark.p0
def test_cen_user_010_login_cria_vinculo(page_as, manifest):
    page = page_as("admin")
    page.goto(f"{BASE_URL}/users/loginas")
    response = http.submit(page, "POST", "/loginas", {"codpes": "160002"})
    assert response.status in (302, 303)
    restore_identity("160002")
    page.goto(BASE_URL)
    expect(page.locator("body")).to_contain_text("Aluno Monitor Ativo E2E")
