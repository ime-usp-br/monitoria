import pytest

from support import http


@pytest.mark.p1
def test_cen_student_001_busca_json_por_codpes(page_as):
    page = page_as("admin")
    response = http.ajax_get(page, "/students", {"codpes": "160001"})
    assert response.status == 200
    data = response.json()
    assert data["codpes"] == 160001
    assert data["nompes"] == "Aluno Com Historico E2E"


@pytest.mark.p1
def test_cen_student_002_busca_json_por_codpes_inexistente(page_as):
    page = page_as("admin")
    response = http.ajax_get(page, "/students", {"codpes": "999999"})
    assert response.status == 200
    assert response.json() == ""


@pytest.mark.p1
def test_cen_student_003_busca_json_por_nompes_filtra_alunos(page_as):
    page = page_as("admin")

    response = http.ajax_get(page, "/students", {"nompes": "Aluno Monitor Ativo E2E"})
    assert response.status == 200
    data = response.json()
    assert isinstance(data, list)
    assert data[0]["codpes"] == 160002

    response = http.ajax_get(page, "/students", {"nompes": "Docente MAC E2E"})
    assert response.status == 200
    assert response.json() == ""
