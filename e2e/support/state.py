"""Criação controlada de estado para cenários que não têm UI de preparação.

Os registros usam sufixo único e são removidos no teardown pelo fixture
``temp`` (ver ``conftest.py``). Isso mantém os testes independentes de ordem e
repetíveis na mesma stack.
"""

from __future__ import annotations

from datetime import datetime, timedelta

from support import db


def _now() -> str:
    return datetime.now().strftime("%Y-%m-%d %H:%M:%S")


def add_term(
    temp,
    *,
    year: int,
    period: str,
    status: str = "Fechado",
    max_enrollments: int = 5,
    public_notice_file_path: str = "20262/edital_e2e.pdf",
) -> int:
    today = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

    def offset(days: int) -> str:
        return (datetime.now() + timedelta(days=days)).strftime("%Y-%m-%d %H:%M:%S")

    term_id = db.insert(
        "insert into school_terms "
        "(year, period, status, max_enrollments, public_notice_file_path, "
        "started_at, finished_at, start_date_requisitions, end_date_requisitions, "
        "start_date_enrollments, end_date_enrollments, start_date_evaluations, "
        "end_date_evaluations, created_at, updated_at) "
        "values (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            year,
            period,
            status,
            max_enrollments,
            public_notice_file_path,
            offset(-30),
            offset(60),
            offset(-15),
            offset(15),
            offset(-15),
            offset(15),
            offset(-15),
            offset(15),
            today,
            today,
        ),
    )
    temp.append(("delete from school_terms where id=%s", (term_id,)))
    return term_id


def add_class(
    manifest,
    temp,
    *,
    coddis: str = "MAC0999",
    codtur: str | None = None,
    nomdis: str = "Turma E2E",
    term_id: int | None = None,
    department_id: int | None = None,
    instructor_codpes: tuple[str, ...] = ("20001",),
    requisition: bool = True,
) -> int:
    term_id = term_id or manifest["terms"]["open"]["id"]
    department_id = department_id or manifest["departments"]["MAC"]
    codtur = codtur or ("9" + datetime.now().strftime("%H%M%S%f")[:12])
    class_id = db.insert(
        "insert into school_classes "
        "(school_term_id, department_id, codtur, coddis, nomdis, tiptur, created_at, updated_at) "
        "values (%s, %s, %s, %s, %s, %s, %s, %s)",
        (term_id, department_id, codtur, coddis, nomdis, "Teoria", _now(), _now()),
    )
    temp.append(("delete from school_classes where id=%s", (class_id,)))

    for codpes in instructor_codpes:
        instructor_id = manifest["instructors"].get(str(codpes))
        if instructor_id:
            db.execute(
                "insert into instructor_school_class (school_class_id, instructor_id) values (%s, %s)",
                (class_id, instructor_id),
            )

    if requisition:
        instructor_id = manifest["instructors"].get(str(instructor_codpes[0]))
        db.insert(
            "insert into requisitions "
            "(instructor_id, school_class_id, requested_number, priority, created_at, updated_at) "
            "values (%s, %s, %s, %s, %s, %s)",
            (instructor_id, class_id, 1, "3", _now(), _now()),
        )
    return class_id


def add_requisition(temp, *, instructor_id: int, class_id: int, requested_number: int = 1) -> int:
    req_id = db.insert(
        "insert into requisitions "
        "(instructor_id, school_class_id, requested_number, priority, created_at, updated_at) "
        "values (%s, %s, %s, %s, %s, %s)",
        (instructor_id, class_id, requested_number, "3", _now(), _now()),
    )
    temp.append(("delete from requisitions where id=%s", (req_id,)))
    return req_id


def add_enrollment(temp, *, student_id: int, class_id: int, voluntario: int = 0) -> int:
    enrollment_id = db.insert(
        "insert into enrollments "
        "(school_class_id, student_id, voluntario, disponibilidade_diurno, "
        "disponibilidade_noturno, preferencia_horario, observacoes, created_at, updated_at) "
        "values (%s, %s, %s, 1, 0, %s, %s, %s, %s)",
        (
            class_id,
            student_id,
            voluntario,
            "Diurno",
            "Inscrição temporária E2E",
            _now(),
            _now(),
        ),
    )
    temp.append(("delete from enrollments where id=%s", (enrollment_id,)))
    return enrollment_id


def add_selection(
    temp,
    *,
    student_id: int,
    class_id: int,
    enrollment_id: int,
    requisition_id: int,
    sitatl: str = "Ativo",
    codpescad: int = 10001,
) -> int:
    selection_id = db.insert(
        "insert into selections "
        "(student_id, school_class_id, enrollment_id, requisition_id, "
        "selecionado_sem_inscricao, codpescad, sitatl, motdes, dtafimvin, created_at, updated_at) "
        "values (%s, %s, %s, %s, 0, %s, %s, null, null, %s, %s)",
        (student_id, class_id, enrollment_id, requisition_id, codpescad, sitatl, _now(), _now()),
    )
    temp.append(("delete from selections where id=%s", (selection_id,)))
    return selection_id


def add_frequency(temp, *, student_id: int, class_id: int, month: int, registered: bool = False) -> int:
    frequency_id = db.insert(
        "insert into frequencies "
        "(registered, month, student_id, school_class_id, created_at, updated_at) "
        "values (%s, %s, %s, %s, %s, %s)",
        (1 if registered else 0, month, student_id, class_id, _now(), _now()),
    )
    temp.append(("delete from frequencies where id=%s", (frequency_id,)))
    return frequency_id


def add_self_evaluation(temp, *, selection_id: int, comments: str = "E2E") -> int:
    evaluation_id = db.insert(
        "insert into self_evaluations "
        "(selection_id, student_amount, homework_amount, secondary_activity, "
        "workload, workload_reason, comments, created_at, updated_at) "
        "values (%s, 1, 1, null, 8, null, %s, %s, %s)",
        (selection_id, comments, _now(), _now()),
    )
    temp.append(("delete from self_evaluations where id=%s", (evaluation_id,)))
    return evaluation_id


def add_instructor_evaluation(temp, *, selection_id: int, comments: str = "E2E") -> int:
    evaluation_id = db.insert(
        "insert into instructor_evaluations "
        "(selection_id, ease_of_contact, efficiency, reliability, overall, comments, created_at, updated_at) "
        "values (%s, 2, 2, 2, 2, %s, %s, %s)",
        (selection_id, comments, _now(), _now()),
    )
    temp.append(("delete from instructor_evaluations where id=%s", (evaluation_id,)))
    return evaluation_id


def add_mail_template(
    temp,
    *,
    name: str,
    mail_class: str,
    sending_frequency: str = "Manual",
    active: bool = False,
    sending_date: str | None = None,
    sending_hour: str | None = None,
) -> int:
    template_id = db.insert(
        "insert into mail_templates "
        "(name, mail_class, description, sending_frequency, sending_date, sending_hour, "
        "active, subject, body, created_at, updated_at) "
        "values (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (
            name,
            mail_class,
            f"{name} E2E",
            sending_frequency,
            sending_date,
            sending_hour,
            1 if active else 0,
            f"[E2E] {name}",
            "<p>Mensagem E2E</p>",
            _now(),
            _now(),
        ),
    )
    temp.append(("delete from mail_templates where id=%s", (template_id,)))
    return template_id


def add_user(temp, *, codpes: int, name: str, email: str, role: str = "Aluno") -> int:
    user_id = db.insert(
        "insert into users (name, email, codpes, password, created_at, updated_at) "
        "values (%s, %s, %s, null, %s, %s)",
        (name, email, codpes, _now(), _now()),
    )
    role_id = db.scalar("select id from roles where name=%s", (role,))
    db.execute(
        "insert into model_has_roles (role_id, model_type, model_id) "
        "values (%s, 'App\\\\Models\\\\User', %s)",
        (role_id, user_id),
    )
    temp.append(("delete from model_has_roles where model_type='App\\\\Models\\\\User' and model_id=%s", (user_id,)))
    temp.append(("delete from users where id=%s", (user_id,)))
    return user_id
