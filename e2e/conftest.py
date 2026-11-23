import pytest

from support.auth import login_as
from support.manifest import load_manifest


@pytest.fixture(scope="session")
def manifest() -> dict:
    return load_manifest()


@pytest.fixture
def page_as(browser, manifest):
    """Fábrica de páginas autenticadas por papel/`codpes` do manifesto."""
    contexts = []

    def _open(role_or_codpes):
        if isinstance(role_or_codpes, str) and role_or_codpes in manifest["users"]:
            codpes = manifest["users"][role_or_codpes]["codpes"]
        else:
            codpes = str(role_or_codpes)

        context = browser.new_context()
        page = context.new_page()
        login_as(page, codpes)
        contexts.append(context)
        return page

    yield _open

    for context in contexts:
        context.close()


@pytest.fixture
def anonymous_page(browser):
    context = browser.new_context()
    page = context.new_page()
    yield page
    context.close()
