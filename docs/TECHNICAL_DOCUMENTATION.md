# Sistema de Monitoria — Documentação Técnica

**Projeto:** Sistema de Monitoria do IME (Instituto de Matemática e Estatística, USP)
**URL:** https://monitoria.ime.usp.br
**Stack:** Laravel 8 (PHP ^7.3|^8.0), MySQL, Laravel Sanctum, Spatie Permission, `uspdev/senhaunica-socialite`, `uspdev/replicado`, `ismaielw/laratex`, `romanzipp/laravel-queue-monitor`, npm/webpack.mix.

Este documento é uma referência técnica exaustiva, derivada diretamente da análise do código-fonte. Ele detalha todas as funcionalidades e mapeia todos os fluxos de trabalho, servindo como base primária para a geração de testes baseados em cenários.

---

## Sumário

1. [Visão Geral do Domínio](#1-visão-geral-do-domínio)
2. [Atores, Papéis e Permissões](#2-atores-papéis-e-permissões)
3. [Períodos do Sistema e Regras de Negócio](#3-períodos-do-sistema-e-regras-de-negócio)
4. [Modelo de Dados (Dicionário de Dados)](#4-modelo-de-dados-dicionário-de-dados)
5. [Arquitetura e Fluxo de Requisições](#5-arquitetura-e-fluxo-de-requisições)
6. [Referência Funcional por Módulo](#6-referência-funcional-por-módulo)
7. [Fluxos de Trabalho Detalhados](#7-fluxos-de-trabalho-detalhados)
8. [Tarefas Automatizadas / Agendadas (Kernel)](#8-tarefas-automatizadas--agendadas-kernel)
9. [Jobs e Filas em Segundo Plano](#9-jobs-e-filas-em-segundo-plano)
10. [Comandos do Console](#10-comandos-do-console)
11. [Sistema de E-mails (Modelos e Disparos)](#11-sistema-de-e-mails-modelos-e-disparos)
12. [Relatórios e Geração em LaTeX](#12-relatórios-e-geração-em-latex)
13. [Integração com o Replicado (Banco Acadêmico da USP)](#13-integração-com-o-replicado-banco-acadêmico-da-usp)
14. [Referência de Validação (Form Requests)](#14-referência-de-validação-form-requests)
15. [Matriz de Autorização](#15-matriz-de-autorização)
16. [Fundação para Testes / Checklist de Cenários](#16-fundação-para-testes--checklist-de-cenários)

---

## 1. Visão Geral do Domínio

O sistema gerencia a **distribuição das bolsas de monitoria** das disciplinas de graduação do IME-USP. O ciclo anual é organizado em **Períodos Letivos** ("períodos letivos" — 1° Semestre / 2° Semestre) que percorrem as seguintes fases a cada ano:

1. **Período de Solicitacão** — os professores (docentes) solicitam monitores para suas turmas.
2. **Período de Inscrições** — os alunos se inscrevem nas vagas de monitoria, enviando o histórico escolar.
3. **Período de Seleção** — a Comissão de Monitoria elege os monitores de cada turma.
4. **Período de Monitoria** — os monitores eleitos registram a frequência mensal.
5. **Período de Avaliação** — os alunos fazem autoavaliação e os docentes avaliam os monitores.
6. **Fechamento** — as seleções transitam de "Ativo" para "Concluído" no fim do período.

O sistema integra-se em tempo real com o **Replicado** (banco de dados acadêmico central da USP) para obter dados de pessoas (alunos, docentes), turmas, departamentos, cursos, horários e até estimativas de matrículas.

---

## 2. Atores, Papéis e Permissões

A autorização é implementada com **Spatie Permission** e verificada nos controllers via `Gate::allows('<permissão>')` ou diretamente com `Auth::user()->hasRole(...)`. Existem Policies nos models, porém todas são stubs vazios (a autorização é feita inline nos controllers).

### 2.1 Papéis

| Papel | Descrição |
|-------|-----------|
| `Administrador` | Acesso total; possui **todas** as permissões. Também é atribuído automaticamente para codpes listados em `LOG_AS_ADMINISTRATOR`. |
| `Secretaria` | Equipe da secretaria de monitoria; amplo acesso operacional. |
| `Docente` | Professor; solicita monitores, registra frequências e avalia monitores. |
| `Aluno` | Estudante; inscreve-se, faz autoavaliação e baixa atestados. |
| `Monitor` | Papel semeado (sem permissões especiais — apenas informativo). |
| `Presidente de Comissão` / `Vice Presidente de Comissão` | Visualizam avaliações e todos os inscritos. |
| `Membro Comissão` | Membro da comissão de monitoria; seleciona e pretere monitores **apenas dentro do próprio departamento**. |

### 2.2 Permissões (definidas no `RolesAndPermissionsSeeder`)

`visualizar menu de configuração`, `editar usuario`, `visualizar periodo letivo`, `criar periodo letivo`, `editar periodo letivo`, `deletar periodo letivo`, `visualizar turma`, `criar turma`, `editar turma`, `deletar turma`, `importar turmas do replicado`, `buscar turmas`, `visualizar solicitação de monitor`, `criar solicitação de monitor`, `editar solicitação de monitor`, `deletar solicitação de monitor`, `visualizar docente`, `criar docente`, `editar docente`, `deletar docente`, `visualizar inscrição`, `fazer inscrição`, `editar inscrição`, `deletar inscrição`, `baixar histórico escolar`, `Selecionar monitor`, `Preterir monitor`, `Disparar emails`, `registrar frequencia`, `gerar relatorio`, `visualizar monitores`, `visualizar todos inscritos`, `Emitir Atestado`, `Editar E-mails`, `Visualizar auto avaliações`, `Visualizar avaliações dos docentes`.

Veja a [Seção 15 — Matriz de Autorização](#15-matriz-de-autorização) para saber quais papéis possuem cada permissão.

### 2.3 Atribuição Automática de Papéis (Model `User`)

- Em todo `User->save()`, o hook estático `booted()` executa:
  1. Se o `codpes` do usuário está listado em `LOG_AS_ADMINISTRATOR`, ele recebe o papel `Administrador`.
  2. `syncVinculoRoles()` é chamada: com base nos **vínculos** obtidos do Replicado (`getVinculosFromReplicadoByCodpes`), os papéis `Aluno` (vínculos ALUNOGR/ALUNOPOS/ALUNOPOSESP) e `Docente` (SERVIDOR com `tipfnc=Docente`) são atribuídos/removidos para corresponder ao vínculo atual (idempotente).
- No login, se um usuário com papel `Aluno` não possui registro correspondente em `Student` (por `codpes`), um é criado automaticamente a partir do Replicado (`MainController@index`). O mesmo vale para usuários `Docente` sem registro em `Instructor`.

---

## 3. Períodos do Sistema e Regras de Negócio

Um `SchoolTerm` define várias janelas de datas que controlam o acesso às funcionalidades:

| Método de período | Regra |
|-------------------|-------|
| `isRequisitionPeriod()` | `start_date_requisitions <= now() <= end_date_requisitions` |
| `isEnrollmentPeriod()` | `start_date_enrollments <= now() <= end_date_enrollments` |
| `isEvaluationPeriod()` (estático) / `isInEvaluationPeriod()` (instância) | `start_date_evaluations <= now() <= end_date_evaluations` |

**Invariantes-chave aplicadas nos controllers:**

- **Apenas UM período letivo pode estar com `status = 'Aberto'`** por vez. Criar/atualizar um período para `Aberto` quando existe outro aberto exibe um aviso e aborta (`SchoolTermController@store/update`).
- **Controles de inscrição e solicitação** (`EnrollmentController@index`, `RequisitionController@index`): o usuário deve estar logado com o papel correto, o período de inscrições/solicitações deve estar ativo, deve existir um período aberto e **o período aberto deve ser o mesmo que está no período de inscrições/solicitações** (caso contrário, exibe aviso "favor informar a secretaria de monitoria").
- **Mutators de atributos:** todos os campos de data de `SchoolTerm` são armazenados/recuperados no formato `d/m/Y`; os mutators convertem os valores enviados de `d/m/Y` (startOfDay para datas de início, endOfDay para datas de fim) e os accessors retornam `d/m/Y`.

---

## 4. Modelo de Dados (Dicionário de Dados)

Abaixo, cada tabela com colunas e restrições. Todas as tabelas possuem `created_at`/`updated_at`, salvo indicação.

### 4.1 `users`
`id` PK; `name`; `email` (única); `email_verified_at` nullable; `password` (nullable após migração senhaunica); `remember_token`; `codpes` (inteiro, adicionado depois); timestamps.

### 4.2 `school_terms`
`id` PK; `year` (uint); `period`; `status`; `max_enrollments` (int); `public_notice_file_path` (nullable); `started_at`; `finished_at`; `start_date_requisitions`; `end_date_requisitions`; `start_date_enrollments`; `end_date_enrollments`; `start_date_evaluations` (adicionado depois); `end_date_evaluations` (adicionado depois). **Única: `(year, period)`.** (Coluna `evaluation_period` removida.)

### 4.3 `departments`
`id` PK; `codset` (única); `nomabvset`; `nomset`; `sglund`; `nomund`.

### 4.4 `school_classes`
`id` PK; `school_term_id` FK→school_terms (cascade); `department_id` FK→departments (cascade); `codtur`; `tiptur` nullable; `nomdis` nullable; `coddis`; `dtainitur` nullable; `dtafimtur` nullable. **Única: `(codtur, coddis)`.**

### 4.5 `instructors`
`id` PK; `department_id` FK→departments (cascade); `codpes`; `nompes`; `codema`. **Única: `(codpes, nompes)`.**

### 4.6 `class_schedules`
`id` PK; `diasmnocp`; `horent`; `horsai`. **Única: `(diasmnocp, horent, horsai)`.**

### 4.7 Tabelas pivô (declaradas sem restrição de FK)
- `instructor_school_class` — `instructor_id`, `school_class_id`
- `class_schedule_school_class` — `class_schedule_id`, `school_class_id`
- `activity_requisition` — `activity_id`, `requisition_id`

### 4.8 `requisitions`
`id` PK; `instructor_id` FK→instructors (cascade); `school_class_id` FK→school_classes (cascade); `requested_number` (int); `priority` (string); `comments` (texto, adicionado depois). **Única: `(instructor_id, school_class_id)`.**

### 4.9 `activities`
`id` PK; `description` (única). Atividades padrão semeadas: `Atendimento a alunos`, `Correção de listas de exercícios`, `Fiscalização de provas`.

### 4.10 `students`
`id` PK; `codpes`; `nompes`; `codema`. **Única: `(codpes, nompes)`.**

### 4.11 `enrollments`
`id` PK; `school_class_id` FK→school_classes (cascade); `student_id` FK→students (cascade); `voluntario` (bool, default 0); `disponibilidade_diurno` (bool, default 0); `disponibilidade_noturno` (bool, default 0); `preferencia_horario`; `observacoes` (texto nullable, era string, alterada). **Única: `(school_class_id, student_id)`.**

### 4.12 `school_records`
`id` PK; `student_id` FK→students (cascade); `schoolterm_id` FK→school_terms (cascade); `file_path`. **Única: `(student_id, schoolterm_id)`.**

### 4.13 `recommendations`
`id` PK; `student_id` FK→students (cascade); `requisition_id` FK→requisitions (cascade). **Única: `(student_id, requisition_id)`.**

### 4.14 `selections`
`id` PK; `student_id` FK→students (cascade); `school_class_id` FK→school_classes (cascade); `enrollment_id` FK→enrollments (cascade); `requisition_id` FK→requisitions (cascade); `selecionado_sem_inscricao` (bool default 0); `codpescad` (int — codpes de quem cadastrou); `dtafimvin` (string nullable); `sitatl` (string nullable — `Ativo`/`Concluido`/`Desligado`); `motdes` (string(512) nullable — motivo do desligamento).

### 4.15 `frequencies`
`id` PK; `school_class_id` FK→school_classes (cascade); `student_id` FK→students (cascade); `month` (int); `registered` (bool default false). **Única: `(student_id, school_class_id, month)`.**

### 4.16 `scholarships` (sem timestamps)
`id` PK; `name`. Anexada via morfismo a `Enrollment` e `Requisition` através de `model_has_scholarships`.

### 4.17 `model_has_scholarships`
`scholarship_id` FK→scholarships (cascade); `model_type`; `model_id`. PK `(scholarship_id, model_id, model_type)`.

### 4.18 `courses`
`id` PK; `student_id`; `schoolterm_id` (sem FK); `nomcur`; `nomund`; `sglund`. Armazena o curso (graduação/pós-graduação) de cada aluno por período (oriundo do Replicado).

### 4.19 `mail_templates`
`id` PK; `name`; `mail_class`; `description` (varchar 256); `sending_frequency`; `sending_date` (string nullable); `sending_hour` (string nullable); `active` (bool default false); `subject` (varchar 256); `body` (varchar 8192).

### 4.20 `self_evaluations`
`id` PK; `selection_id` (sem FK); `student_amount` (uint); `homework_amount` (uint); `secondary_activity` (texto nullable); `workload` (uint); `workload_reason` (texto nullable); `comments` (texto nullable).

### 4.21 `instructor_evaluations`
`id` PK; `selection_id` (sem FK); `ease_of_contact`; `efficiency`; `reliability`; `overall` (todos uintTinyInt); `comments` (texto nullable).

### 4.22 Tabelas de apoio (framework)
`password_resets`, `failed_jobs`, `jobs`, `personal_access_tokens`, `queue_monitor` (romanzipp) e as tabelas de permissão do Spatie (`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`).

---

## 5. Arquitetura e Fluxo de Requisições

- **Rotas:** todas as rotas da aplicação estão em `routes/web.php` (grupo de middleware web). Existe uma única rota `/api/user` protegida por Sanctum em `routes/api.php`. Uma rota de console `inspire` (closure) em `routes/console.php`.
- **Controllers** validam via Form Requests, verificam autorização via `Gate::allows`/`hasRole` e renderizam views Blade ou retornam JSON.
- **Views** ficam em `resources/views`, baseadas em `parent.blade.php` (laravel-usp-theme). Subpastas espelham os recursos.
- **Jobs** para operações longas (importação de turmas, importação do banco antigo) com monitoramento de progresso via queue-monitor.
- **Comandos Artisan** para comparar/sincronizar dados com o Replicado.

### Mapa de Rotas → Controller (rotas web)

| Método | URI | Controller@método | Nome |
|--------|-----|-------------------|------|
| GET | `/` | MainController@index | home |
| GET | `/users/loginas` | UserController@loginas | users.loginas |
| GET | `/users/search` | UserController@search | users.search |
| RES | `/users` | UserController | users |
| RES | `/schoolterms` | SchoolTermController | schoolterms |
| POST | `/schoolterms/download` | SchoolTermController@download | schoolterms.download |
| GET | `/schoolclasses/{schoolclass}/enrollments` | SchoolClassController@enrollments | schoolclasses.enrollments |
| GET | `/schoolclasses/search` | SchoolClassController@search | schoolclasses.search |
| PATCH | `/schoolclasses/import` | SchoolClassController@import | schoolclasses.import |
| GET | `/schoolclasses/{schoolclass}/electedTutors` | SchoolClassController@electedTutors | schoolclasses.electedTutors |
| RES | `/schoolclasses` | SchoolClassController | schoolclasses |
| GET | `/instructors/evaluations` | InstructorEvaluationController@instructorIndex | instructorevaluations.instructorIndex |
| GET | `/instructors/{instructor}/requisitions` | InstructorController@requisitions | instructors.requisitions |
| GET | `/instructors/search` | InstructorController@search | instructors.search |
| RES | `/instructors` | InstructorController | instructors |
| RES | `/requisitions` | RequisitionController | requisitions |
| GET | `/students/selfevaluations` | SelfEvaluationController@studentIndex | selfevaluations.studentIndex |
| GET | `/students/test` | StudentController@test | students.test |
| RES | `/students` | StudentController | students |
| GET | `/enrollments/showAll` | EnrollmentController@showAll | enrollments.showAll |
| RES | `/enrollments` | EnrollmentController | enrollments |
| POST | `/schoolrecords/download` | SchoolRecordController@download | schoolrecords.download |
| RES | `/schoolRecords` | SchoolRecordController | schoolRecords |
| POST | `/selections/selectunenrolled` | SelectionController@selectUnenrolled | selections.selectunenrolled |
| GET | `/selections/{schoolclass}/enrollments` | SelectionController@enrollments | selections.enrollments |
| RES | `/selections` | SelectionController | selections |
| GET | `/monitor/getimportschoolclassesjob` | MonitorController@getImportSchoolClassesJob | — |
| GET | `/emails` | EmailController@index | emails.index |
| GET | `/emails/selections` | EmailController@indexSelections | emails.indexSelections |
| GET | `/emails/attendanceRecords` | EmailController@indexAttendanceRecords | emails.indexAttendanceRecords |
| GET | `/emails/selfEvaluations` | EmailController@indexSelfEvaluations | emails.indexSelfEvaluations |
| GET | `/emails/instructorEvaluations` | EmailController@indexInstructorEvaluations | emails.indexInstructorEvaluations |
| POST | `/emails/triggerSelections` | EmailController@triggerSelections | emails.triggerSelections |
| POST | `/emails/triggerAttendanceRecords` | EmailController@triggerAttendanceRecords | emails.triggerAttendanceRecords |
| POST | `/emails/triggerSelfEvaluations` | EmailController@triggerSelfEvaluations | emails.triggerSelfEvaluations |
| POST | `/emails/triggerInstructorEvaluations` | EmailController@triggerInstructorEvaluations | emails.triggerInstructorEvaluations |
| GET | `/frequencies/{schoolclass}/{tutor}` | FrequencyController@show | frequencies.show |
| GET | `frequencies/{frequency}` | FrequencyController@update | frequencies.update |
| GET | `frequencies` | FrequencyController@index | frequencies.index |
| GET | `/reports` | ReportController@index | reports.index |
| GET | `/reports/external` | ReportController@external | reports.external |
| POST | `/reports/make` | ReportController@make | reports.make |
| PATCH | `/tutors/turnintovolunteer/{selection}` | TutorController@turnIntoVolunteer | tutors.turnintovolunteer |
| PATCH | `/tutors/turnintononvolunteer/{selection}` | TutorController@turnIntoNonVolunteer | tutors.turnintononvolunteer |
| PATCH | `/tutors/revoke/{selection}` | TutorController@revoke | tutors.revoke |
| GET | `/tutors` | TutorController@index | tutors.index |
| GET | `/certificates` | CertificateController@index | certificates.index |
| GET | `/certificates/make/{selection}` | CertificateController@make | certificates.make |
| POST | `/mailtemplates/test` | MailTemplateController@test | mailtemplates.test |
| GET | `/mailtemplates/activate/{mailtemplate}` | MailTemplateController@activate | mailtemplates.activate |
| GET | `/mailtemplates/deactivate/{mailtemplate}` | MailTemplateController@deactivate | mailtemplates.deactivate |
| RES | `/mailtemplates` | MailTemplateController | mailtemplates |
| GET | `/olddb` | OldDBController@index | olddb.index |
| POST | `/olddb/import` | OldDBController@import | olddb.import |
| RES | `/selfevaluations` | SelfEvaluationController | selfevaluations |
| RES | `/instructorevaluations` | InstructorEvaluationController | instructorevaluations |
| GET | `/monitor/getImportOldDBJob` | MonitorController@getImportOldDBJob | — |

---

## 6. Referência Funcional por Módulo

### 6.1 Períodos Letivos — `SchoolTermController`

| Método | Permissão | Comportamento |
|--------|-----------|---------------|
| `index` | `visualizar periodo letivo` | Lista todos os períodos ordenados por `year`, `period` decrescente. |
| `create` | `criar periodo letivo` | Exibe formulário de criação (`new SchoolTerm`). |
| `store` | `criar periodo letivo` | Valida; se status `Aberto` e já houver outro aberto → aviso + back. Armazena o edital em PDF (`public_notice_file_path`) sob `{year}{period[0]}` no storage. Usa `SchoolTerm::updateOrCreate(['year','period'], ...)`. |
| `edit` | `editar periodo letivo` | Formulário de edição. |
| `update` | `editar periodo letivo` | Mesmo bloqueio de período único aberto (excluindo a si mesmo). Se nova `public_notice` for enviada, substitui o caminho. |
| `destroy` | `deletar periodo letivo` | (stub vazio) |
| `download` | qualquer (validado por `DownloadPublicNoticeRequest`) | Baixa o edital armazenado como `edital_monitoria.pdf`. |

### 6.2 Turmas — `SchoolClassController`

| Método | Permissão | Comportamento |
|--------|-----------|---------------|
| `index` | `visualizar turma` | Seleciona o período (dado `periodoId` ou período aberto, senão o mais recente). **Escopo por papel:** `Docente` (não Membro) → turmas em que é instrutor; `Membro Comissão` (não Secretaria) → turmas do próprio departamento; senão todas as turmas do período. |
| `create` | `criar turma` | Associa nova turma ao período escolhido; popula departamentos do Replicado (`Department::getFromReplicadoByInstitute(env('UNIDADE'))`). |
| `store` | `criar turma` | Busca turma por `(codtur, coddis)`; se não existir, cria e anexa instrutores (`Instructor::firstOrCreate(getFromReplicadoByCodpes)`) e horários. Se a turma existe **sem instrutores**, anexa instrutores + horários e atualiza. Se existe com instrutores → aviso "já cadastrada". |
| `edit` | `editar turma` | Formulário de edição. |
| `update` | `editar turma` | Desanexa e reanexa instrutores (usando `Pessoa::obterNome`) e horários; atualiza a turma. |
| `destroy` | `deletar turma` | Desanexa instrutores/horários e exclui a turma. |
| `import` | `importar turmas do replicado` | Se `IS_SUPERVISOR_CONFIG=true`, despacha o job `ProcessGetSchoolClassesFromReplicado`; senão, importa de forma síncrona via `SchoolClass::getFromReplicadoBySchoolTerm`. |
| `search` | `visualizar turma` | Filtra turmas de um período por `coddis`. |
| `enrollments` | `visualizar inscrição` | Renderiza a view de inscrições da turma. |
| `electedTutors` | `registrar frequencia` | Renderiza a view de monitores eleitos de uma turma. |

### 6.3 Docentes — `InstructorController`

| Método | Acesso | Comportamento |
|--------|--------|---------------|
| `index` | Caminho JSON: consulta o Replicado por `nompes` de um `codpes` e verifica vínculo "Docente" → retorna o nome em JSON ou `""`. Caminho web (`visualizar docente`): lista docentes com `SUM(requisitions.requested_number)` (com solicitações, ordenados por quantidade decrescente), mesclados com docentes sem solicitações. |
| `requisitions` | `visualizar docente` | Renderiza as solicitações do docente (`instructors.requisitions`). |
| `search` | `visualizar docente` | Filtra docentes por `codpes`. |
| create/store/show/edit/update/destroy | — | Stubs vazios (não ativos). |

### 6.4 Solicitações de Monitor — `RequisitionController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | Deve ser `Docente`; período aberto; período de solicitação ativo; período aberto == período em solicitação | Lista as turmas do docente no período de solicitação (turmas em que é instrutor). |
| `create` | `criar solicitação de monitor`; a turma deve ter `isInstructor(codpes do usuário)` e estar no período de solicitação | Exibe formulário. |
| `store` | `criar solicitação de monitor` | Define `instructor_id` = instrutor do usuário autenticado. Cria a solicitação; anexa atividades (`Activity::firstOrCreate`); cria recomendações (atualizando/criando alunos a partir do Replicado por `codpes`); anexa `others_scholarships`. |
| `edit` | `editar solicitação de monitor`; turma própria + período de solicitação | Formulário de edição. |
| `update` | `editar solicitação de monitor` | Desanexa/reanexa atividades; exclui e recria recomendações; desanexa/reanexa bolsas; atualiza a solicitação. |
| `destroy` | — | Stub vazio. |

### 6.5 Alunos — `StudentController`

- `index`: caminho JSON — dado `codpes`, retorna `Student::getFromReplicadoByCodpes` em JSON (ou `""`); dado `nompes`, retorna `getFromReplicadoByNompes` (apenas pessoas com vínculo `Aluno`). Retorna `""` quando nada encontrado.
- `create`/`store` guardam o papel `Aluno`, mas não contêm lógica (stubs). `show/edit/update/destroy` vazios.

### 6.6 Inscrições — `EnrollmentController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | `Aluno`; período de inscrições; existe período aberto; período aberto == período de inscrições | Se o aluno não possui histórico escolar para o período aberto → redireciona para `schoolRecords.create`. Lista as turmas do período de inscrições; mescla turmas já inscritas primeiro, depois as não inscritas ordenadas por `coddis`. |
| `create` | `Aluno`; período de inscrições | **Guarda de máximo:** conta `coddis` distintos em que o aluno está inscrito nas turmas do período; se `>= schoolterm->max_enrollments` → aviso "excedeu o número máximo de inscrições". |
| `store` | `Aluno`; período de inscrições | Define `student_id` = aluno do usuário autenticado. **Para cada turma da mesma `coddis` no período de inscrições** cria uma inscrição (inscrever-se numa disciplina inscreve em todas as suas turmas) e anexa bolsas. |
| `edit` | `Aluno`; período de inscrições | Formulário de edição. |
| `update` | `Aluno`; período de inscrições | Normaliza booleanos `voluntario`/`disponibilidade_*`. Atualiza **todas as inscrições do mesmo aluno + coddis no período**, ressincronizando as bolsas de cada uma. |
| `destroy` | `Aluno`; período de inscrições | Busca todas as inscrições do aluno+coddis no período. Se alguma possuir `Selection` → bloqueia a exclusão ("você foi selecionado... comunique a comissão"). Caso contrário, exclui todas. |
| `showAll` | `visualizar todos inscritos` | Lista alunos com inscrições no período escolhido, ordenados por nome. |

### 6.7 Histórico Escolar — `SchoolRecordController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `create` | `Aluno`; período de inscrições | Exibe formulário de upload. |
| `store` | `Aluno`; período de inscrições | Armazena o PDF sob `{year}{period[0]}`; cria `SchoolRecord` para o período de inscrições + aluno autenticado. Redireciona para inscrições. |
| `update` | `Aluno`; período de inscrições | Substitui o `file_path` no lugar. |
| `download` | `baixar histórico escolar` | Baixa o arquivo armazenado como `{primeirosegmentodocaminho}.pdf` (validado pela regra `StorageFileExists`). |
| index/show/edit/destroy | — | Stubs vazios. |

### 6.8 Seleção de Monitores — `SelectionController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | `Selecionar monitor` | Exige período aberto. `Secretaria/Admin/Presidente`: todas as solicitações de turmas do período aberto, ordenadas por departamento. `Membro Comissão`: solicitações apenas do próprio departamento. |
| `store` | `Selecionar monitor` | Dado `enrollment_id`: se a inscrição já possui seleção, ela é **silenciosamente deselecionada** primeiro (exclui suas frequências, autoavaliação, avaliação de docente e a seleção). Reconstrói a seleção com `sitatl=Ativo`, `codpescad`=usuário. Repopula `Course` do Replicado para o aluno+período. **Bloqueio:** aluno já eleito monitor de outra turma no período aberto → aviso. Guarda de papel para seleção por departamento (`Membro` apenas no próprio departamento). Cria a seleção e chama `Frequency::createFromSelection`. |
| `destroy` | `Preterir monitor` | **Bloqueio:** se existir frequência com `registered=true` → aviso "registre a presença... use o menu Monitores" (ou seja, usar desligamento). Senão, exclui as frequências associadas e a seleção (com guarda de departamento para `Membro`). |
| `enrollments` | `Selecionar monitor` | Lista as inscrições de uma turma, **ordenadas** (e depois invertidas): já selecionados por último, alunos com seleção no período aberto primeiro, alunos recomendados primeiro. |
| `selectUnenrolled` | implícita via papéis | Para eleger aluno **sem inscrição prévia**: valida que não está inscrito na turma, não está selecionado em outra turma do período aberto e possui histórico escolar no período aberto. Cria `Enrollment` com valores padrão e observação informando eleição sem inscrição; em seguida cria a seleção e as frequências. |

### 6.9 Frequência — `FrequencyController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | `Docente` | Lista seleções `Ativo` onde o instrutor da solicitação é o usuário autenticado, no período aberto/mais recente, ordenadas por nome do aluno. |
| `show` | URL assinada OU `Docente` autenticado instrutor da turma E o tutor pertence à turma | Exibe os registros de frequência mensal do tutor numa turma. |
| `update` | URL assinada OU instrutor autenticado da turma | Encontra a seleção ativa (turma+aluno). **Bloqueia se não `Ativo`.** **Janela de tempo:** a frequência de um mês só é liberada a partir do dia 20 daquele mês (bloqueada para meses futuros ou antes do dia 20). Alterna `registered`. |

### 6.10 Monitores — `TutorController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | Logado; baseado em papel | `Admin/Secretaria/Presidente`: todas as seleções do período. `Membro Comissão`: seleções do próprio departamento. `Docente`: seleções de suas solicitações. Outros → 403. |
| `revoke` | `Secretaria/Admin` | Bloqueia se a seleção não for `Ativo`. Exclui frequências **futuras não registradas** (não registradas e mês >= mês atual). Define `sitatl=Desligado`, `motdes` (do request validado), `dtafimvin=hoje`. |
| `turnIntoVolunteer` / `turnIntoNonVolunteer` | `Secretaria/Admin` | Bloqueia se não `Ativo`. Altera o flag `voluntario` da inscrição vinculada. |

### 6.11 Atestados — `CertificateController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | Papéis `Aluno`, `Secretaria`, `Admin`, ou qualquer usuário que já teve seleção | `Secretaria/Admin`: todas as seleções não `Desligado` do período. `Aluno`/monitor: suas próprias seleções (não Desligado), ordenadas da mais recente para a mais antiga. Se vazio → aviso "você não realizou nenhuma monitoria". |
| `make` | Dono da seleção (ou Secretaria/Admin) | Se `sitatl == Concluido` → renderiza LaTeX `certificates.completed`; se `Ativo` → `certificates.ongoing`. Baixa `atestado.pdf`. |

### 6.12 Modelos de E-mail — `MailTemplateController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index`/`create`/`edit` | `Editar E-mails` | CRUD de UI. |
| `store` | `Editar E-mails` | Decodifica o JSON combinado `description_and_mail_class` em `description` + `mail_class`. Rejeita nome duplicado. |
| `update` | `Editar E-mails` | Decodifica o campo combinado; rejeita nome duplicado (excluindo a si mesmo); **rejeita** ativar outro modelo ativo com envio Manual para a mesma mail_class; se `sending_frequency == Manual`, limpa `sending_date`/`sending_hour`. |
| `destroy` | `Editar E-mails` | Exclui o modelo. |
| `activate` | `Editar E-mails` | Bloqueia dois modelos ativos com envio Manual para a mesma mail_class; define `active=true`. |
| `deactivate` | `Editar E-mails` | Define `active=false`. |
| `test` | `Disparar emails` | Envia e-mail de exemplo para o endereço informado usando um registro real por mail_class (uma frequência com seleção `Ativo`, ou uma seleção recente). |

### 6.13 Autoavaliação — `SelfEvaluationController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | `Visualizar auto avaliações` | Lista autoavaliações do período (por ano+periodo). |
| `studentIndex` | `Aluno` | Lista as seleções do próprio aluno (não Desligado), da mais recente para a mais antiga. |
| `create` | URL assinada OU `Aluno` autenticado | A seleção deve existir; o período deve estar na janela de avaliação (`isInEvaluationPeriod`); se autenticado, a seleção deve pertencer ao usuário. |
| `store` | URL assinada (verificação por hash) OU `Aluno` autenticado dono | Verifica se a seleção pertence ao usuário, ou (não autenticado) verifica `Hash::check(json_encode(selection->toArray()), selection_hash)` — o fluxo do link assinado. Usa `updateOrCreate(['selection_id'])`. |
| `show` | Aluno dono, ou `Visualizar auto avaliações` | Exibe uma avaliação. |
| `edit`/`update` | `Aluno` dono | Edita/atualiza a própria avaliação. |
| `destroy` | — | Stub vazio. |

### 6.14 Avaliação do Docente — `InstructorEvaluationController`

Espelha a autoavaliação, mas para docentes:
- `index` exige `Visualizar avaliações dos docentes`.
- `instructorIndex` exige `Docente` e lista seleções onde o instrutor da solicitação é o usuário autenticado (não Desligado).
- `create` exige URL assinada ou `Docente` autenticado instrutor da solicitação da seleção; período na janela de avaliação.
- `store` verifica a propriedade (ou hash sobre o JSON da seleção para links assinados) via `updateOrCreate(['selection_id'])`.
- `show` restringe ao instrutor dono ou a quem tem a permissão.
- `edit`/`update` pelo instrutor dono.

### 6.15 Disparo de E-mails — `EmailController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | `Disparar emails` | Página inicial. |
| `indexSelections` | `Disparar emails` | Turmas do período com seleções (para escolher quais notificar). |
| `indexAttendanceRecords` | `Disparar emails` | Calcula meses válidos (`1°` → 3,4,5,6; `2°` → 8,9,10,11); valida o mês selecionado; se não informado, deriva do mês atual (>= dia 20) com clamp nos limites do período. Lista frequências não registradas do mês de monitores `Ativo`. |
| `indexSelfEvaluations` | `Disparar emails` | Seleções não `Desligado` sem autoavaliação. |
| `indexInstructorEvaluations` | `Disparar emails` | Seleções não `Desligado` sem avaliação de docente. |
| `triggerSelections` | `Disparar emails` | Exige modelo ativo **Manual** para `NotifyInstructorAboutSelectAssistant` e `NotifySelectStudent`. Para cada turma selecionada: envia e-mail para o instrutor + cada aluno selecionado. |
| `triggerAttendanceRecords` | `Disparar emails` | Exige modelo ativo Manual para `NotifyInstructorAboutAttendanceRecord`. Para cada frequência: envia e-mail ao instrutor da turma com **URL assinada** para a página de frequência. |
| `triggerSelfEvaluations` | `Disparar emails` | Exige modelo ativo Manual para `NotifyStudentAboutSelfEvaluation`. Envia e-mail a cada aluno selecionado com URL assinada para o formulário de autoavaliação. |
| `triggerInstructorEvaluations` | `Disparar emails` | Exige modelo ativo Manual para `NotifyInstructorAboutEvaluation`. Envia e-mail a cada instrutor de turma com URL assinada. |

### 6.16 Usuários — `UserController`

| Método | Guarda | Comportamento |
|--------|--------|---------------|
| `index` | `editar usuario` | Lista usuários com papéis "especiais" (Administrador, Secretaria, Membro Comissão, Presidente/Vice) primeiro e os demais depois. |
| `edit` | `editar usuario` | Formulário de edição com papéis. |
| `update` | `editar usuario` | Desanexa todos os papéis, atribui os papéis validados e atualiza o usuário. |
| `search` | `editar usuario` | Filtra por nome (like), codpes e papéis. |
| `loginas` | `editar usuario` | Renderiza a view "logar como" (UI auxiliar de impersonação). |
| create/store/show/destroy | — | Stubs vazios. |

### 6.17 Importação do Banco Antigo — `OldDBController`

- `index` exige `Administrador`.
- `import` exige `Administrador`; aceita arquivo CSV/TXT (separado por ponto-e-vírgula, 18 colunas), despacha o job `ProcessImportOldDB` com o conteúdo bruto do arquivo + `codpes` do usuário. Retorna à página imediatamente (progresso via queue-monitor).

### 6.18 Relatórios — `ReportController`

- `index` exige `gerar relatorio`; lista períodos.
- `make` exige `gerar relatorio`; executa o script Python de gráficos (`create_graphs.py` com `periodoId`), depois renderiza `reports.latex` via LaraTeX → baixa `relatorio.pdf`.
- `external` — **sem autenticação de sessão**; protegida por um parâmetro `token` comparado a `env('EXTERNAL_REPORT_TOKEN')`, além dos parâmetros `ano` e `periodo`. Encontra o período correspondente, executa o script de gráficos, renderiza `reports.latex-external` e retorna JSON `{status, message, report: <pdf em base64>}`. (Observação: no sucesso, `status` está definido como `false` no código atual.)

### 6.19 Monitor de Filas — `MonitorController`

- `getImportSchoolClassesJob` e `getImportOldDBJob` retornam o registro `Monitor` mais recente do respectivo job (max id, depois linha com max progress), usado para polling de progresso.

### 6.20 Controllers autônomos de Departamento / Atividade / Recomendação
Esses controllers de recurso existem, mas **todos os métodos são stubs vazios**; departamentos são criados implicitamente pelos fluxos de importação de turmas, atividades pelas solicitações e recomendações pelas solicitações.

---

## 7. Fluxos de Trabalho Detalhados

### 7.1 Ciclo anual de monitoria (ponta a ponta)
1. **Admin/Secretaria cria um Período Letivo aberto** (`schoolterms.create` → `store`): envia o edital em PDF, define as janelas de solicitação/inscrição/avaliação e define status `Aberto` (restrição de singleton).
2. **Turmas são importadas do Replicado** (`schoolclasses.import`): síncrona ou via fila; cria `SchoolClass`, `Department`, `Instructor`, `ClassSchedule` e seus pivôs a partir dos dados do Replicado.
3. **Professores solicitam monitores** (`requisitions.create` → `store`): escolhem uma turma que ministram, o número de monitores, a prioridade, as atividades permitidas, recomendações opcionais de alunos e bolsas externas opcionais.
4. **Alunos se inscrevem** (`enrollments`): devem primeiro enviar o **histórico escolar** (`schoolrecords.create`); depois se inscrevem nas disciplinas (o que os inscreve em **todas as turmas** daquela `coddis`), indicando disponibilidade (diurno/noturno), preferência por ser voluntário, horário preferido e bolsas opcionais.
5. **A comissão seleciona os monitores** (`selections`): para cada turma, visualiza as inscrições (com ordenação que destaca recomendações e seleções anteriores) e elege um aluno (`selections.store`), criando frequências para os meses ativos do período (3-6 ou 8-11). O aluno pode ser eleito sem inscrição prévia (`selections.selectunenrolled`) desde que tenha histórico escolar e não esteja selecionado em outra turma.
6. **Monitoria**: monitores eleitos (status `Ativo`) registram/confirmam a frequência mensal a partir do dia 20 de cada mês; instrutores alternam a presença; notificações de faltas podem ser enviadas.
7. **Avaliações** durante a janela de avaliação: autoavaliações dos alunos e avaliações dos docentes (uma por seleção), notificadas via links assinados.
8. **Fechamento**: em `finished_at` às 23:59, o agendador transforma todas as seleções `Ativo` do período em `Concluido`; Admin/Secretaria podem desligar um monitor a qualquer momento (definindo `Desligado` + motivo + data de fim).
9. **Pós-período**: atestados (`atestado.pdf`) para monitores `Concluido`/`Ativo`; relatórios agregados gerados em LaTeX.

### 7.2 Eleição de um monitor (criação de seleção)
```
SelectionController@store
 ├─ autorização: 'Selecionar monitor'
 ├─ encontrar Enrollment
 ├─ se a inscrição já tem seleção: deselecionar (excluir frequencias, autoavaliacao, avaliacao de docente, selecao)
 ├─ definir student_id, school_class_id, requisition_id, codpescad, sitatl=Ativo
 ├─ repopular Course do aluno+período (Replicado)
 ├─ guarda: aluno não pode ter outra seleção no período aberto (turma diferente)
 ├─ guarda de papel/departamento (Membro apenas no próprio departamento)
 └─ Selection::firstOrCreate + Frequency::createFromSelection(months ativos)
```

### 7.3 Desligamento de um monitor
```
TutorController@revoke (Secretaria/Admin)
 ├─ exigir sitatl == Ativo
 ├─ excluir frequências não registradas com mês >= mês atual
 ├─ definir sitatl=Desligado, motdes, dtafimvin=hoje
```
vs `SelectionController@destroy` (Preterir): bloqueia se houver frequência registrada; senão exclui frequências + seleção.

### 7.4 Alternar voluntário
`TutorController@turnIntoVolunteer/NonVolunteer` alterna o flag `voluntario` da inscrição vinculada (somente quando a seleção é `Ativo`).

### 7.5 Fluxo de frequência mensal
- Ao criar a seleção, são criadas frequências para os meses ativos do período.
- `FrequencyController@update` (GET `frequencies/{frequency}`) alterna `registered`, bloqueado por:
  - seleção `Ativo`;
  - mês não futuro e >= dia 20 do mês atual.
- Instrutores visualizam via `frequencies.show` (URL assinada ou instrutor autenticado da turma + tutor pertencente à turma).

### 7.6 Inscrição em uma disciplina (múltiplas turmas)
Quando um aluno se inscreve, uma `Enrollment` é criada **por turma** que compartilha a mesma `coddis` no período de inscrições. Edição/exclusão operam sobre esse conjunto completo como uma unidade.

### 7.7 Guarda ao desinscrever
Se a inscrição do aluno está associada a uma `Selection` (ou seja, foi selecionado na disciplina), a exclusão é bloqueada com mensagem para contatar a comissão.

---

## 8. Tarefas Automatizadas / Agendadas (Kernel)

`app/Console/Kernel.php::schedule()` define:

1. **Disparo agendado de e-mails** para cada `MailTemplate` com `active=true` e `sending_frequency != "Manual"`, por tipo de frequência:
   - `Única`: quando `now == sending_date` e `now == sending_hour`.
   - `Mensal`: `monthlyOn(sending_date, sending_hour)` (interpretado do modelo).
   - `Inicio do período de avaliação`: para cada período com `start_date_evaluations >= now - sending_date`, envia em `start_date_evaluations + sending_date` dias.
   - `Final do período de avaliação`: para cada período com `end_date_evaluations >= now + sending_date`, envia em `end_date_evaluations - sending_date` dias.
2. **Fechamento automático do período**: para cada período com `finished_at >= now`, em `finished_at` às 23:59, transforma todas as seleções `Ativo` do período em `Concluido`.

`sendEmail(MailTemplate)` despacha por `mail_class` (as mesmas cinco classes de e-mail do EmailController) usando os mesmos filtros de consulta; e-mails de frequência incluem URLs assinadas para as rotas de exibição.

---

## 9. Jobs e Filas em Segundo Plano

### 9.1 `ProcessGetSchoolClassesFromReplicado`
- Despachado por `SchoolClassController@import` quando `IS_SUPERVISOR_CONFIG=true`.
- Monitorado via `romanzipp` (progresso 0→100).
- `timeout = 3600`.
- Para cada turma de `SchoolClass::getFromReplicadoBySchoolTerm(periodo)`: find-or-create por `(codtur,coddis)`, desanexar + reanexar instrutores (`updateOrCreate` por nompes+codpes), desanexar + reanexar horários, salvar.

### 9.2 `ProcessImportOldDB`
- Despachado por `OldDBController@import` com o conteúdo bruto do arquivo + `codpescad`.
- Monitorado; `timeout = 9999`.
- Analisa o CSV (separado por `;`, espera exatamente 18 colunas) linha a linha:
  - layout das colunas: `monitor_codpes;professor_codpes;coddis;ano;semestre;frequencia_meses;voluntario;student_amount;homework_amount;secondary_activity;workload;workload_reason;comments;ease_of_contact;efficiency;reliability;overall;comments_ie`.
  - Para cada linha válida: encontra/cria `Instructor` e `Student` (via Replicado; senão, registra erro e pula), encontra/cria um `SchoolTerm` fechado, obtém a turma (`SchoolClass::getFromReplicadoOldDB`), cria a turma/anexa instrutores+horários, cria `Requisition` (requested_number=1, priority=1) com as 3 atividades padrão, `Enrollment`, `Selection` com `sitatl=Concluido`, registros de `Frequency` para cada mês em `frequencia_meses` (todos registrados) e `SelfEvaluation`/`InstructorEvaluation` opcionais (apenas quando todos os campos exigidos presentes).
  - Linhas com contagem de colunas incorreta são rastreadas como erros; erros são reportados via `queueData(["status"=>"failed","linhas_com_erros"=>"[...]"])`.

---

## 10. Comandos do Console

### 10.1 `report:compare-classes`
Compara turmas e professores entre o banco local e o Replicado para um período.
- **Opções:** `--format=table|json|csv`, `--schoolterm=ID`, `--output=caminho`, `--detailed`, `--only-instructor-diffs`, `--show-instructor-details`.
- Fluxo: validar ambiente (classe Replicado presente, env `UNIDADE`, conexão com o banco), resolver o período (opção ou aberto), coletar turmas locais e do Replicado, comparar pela chave `codtur_coddis`, separar diferenças em apenas-local / apenas-replicado / diferenças-de-professores / idênticos / outras-diferenças, calcular `instructor_sync_rate` e emitir o relatório no formato escolhido.
- Retorna 0 em sucesso, 1 em erro.

### 10.2 `sync:class-instructors`
Sincronização aditiva de professores do Replicado nas turmas locais.
- **Opções:** `--schoolterm=ID`, `--dry-run`, `--class=ID`.
- Fluxo: validar ambiente; coletar dados locais + Replicado; encontrar professores presentes no Replicado mas ausentes localmente por chave de turma correspondente; em `--dry-run` retorna JSON de pré-visualização (`{status:"preview", summary, changes}`); senão aplica dentro de transação no banco (get-or-create de cada professor, anexa relação apenas se ausente) e retorna `{status:"completed", summary, results}`. Nunca remove professores.

---

## 11. Sistema de E-mails (Modelos e Disparos)

### 11.1 Classes de e-mail (objetos Mailable)
Todas montam corpo/assunto renderizando o **template Blade armazenado na linha do banco** (`mailtemplate->subject`/`body`) com um array de contexto, estilizando com CSS inline de `public/css/mail.css` (`CssToInlineStyles`).

| Mailable | Destinatário | Variáveis de contexto |
|----------|--------------|-----------------------|
| `NotifySelectStudent` | aluno selecionado | `student`, `schoolclass` |
| `NotifyInstructorAboutSelectAssistant` | instrutor da solicitação | `schoolclass`, `instructor`, `plural` (tem >1 seleções) |
| `NotifyInstructorAboutAttendanceRecord` | instrutor da solicitação | `schoolclass`, `instructor`, `student`, `month`, `year`, `period`, `link` (assinado) — **retorna null (cancela o envio) se a seleção não for Ativo** (registra log) |
| `NotifyStudentAboutSelfEvaluation` | aluno selecionado | `student`, `instructor`, `schoolclass`, `selection`, `link` (assinado) |
| `NotifyInstructorAboutEvaluation` | instrutor da solicitação | `student`, `instructor`, `schoolclass`, `selection`, `link` (assinado) |

Mailables que implementam `ShouldQueue` usam a fila; vários chamam `afterCommit()`.

### 11.2 Frequências de envio
- `Manual`: disparado via `EmailController` (exige modelo ativo Manual por classe de e-mail) ou via `MailTemplateController@test` (envio de exemplo).
- `Única`, `Mensal`, `Inicio do período de avaliação`, `Final do período de avaliação`: agendadas pelo Kernel (ver Seção 8).

### 11.3 URLs assinadas
Os links de autoavaliação, avaliação de docente e frequência são gerados com `URL::signedRoute(...)`, permitindo que um destinatário não autenticado acesse a página de criação/exibição com uma assinatura válida. O controller valida com `$request->hasValidSignature()`.

---

## 12. Relatórios e Geração em LaTeX

- `config/laratex.php`: `binPath=/usr/bin/pdflatex`, `tempPath=app/`.
- **Script Python de gráficos** (`app/Scripts/Python/create_graphs.py`) conecta-se via credenciais do env (`DB_HOST/PORT/USERNAME/PASSWORD/DATABASE`), lê `school_terms`, `selections`, `school_classes`, `departments`, `courses` e gera dois gráficos em `storage/app/graphs/`:
  1. `monitorias_por_departamento.jpg` — barras empilhadas de monitorias por departamento ao longo dos semestres.
  2. `monitorias_pie_{ano}{sem}.jpg` — pizza de monitores por tipo de curso (Pós-Graduação vs Graduação) e localização (IME vs fora).
- **`reports.latex`** (interno) e **`reports.latex-external`** (externo) renderizam: visão geral global (turmas, monitores solicitados, inscrições, monitores eleitos) por departamento (MAC/MAP/MAE/MAT), tabela resumo geral, parágrafos de perfil dos monitores por curso com gráficos opcionais, e tabelas longas por departamento com monitores eleitos (disciplina, turma, professor solicitante, monitores com flags de voluntário e cursos).
- Relatórios são gerados sob demanda por quem tem a permissão (`make`) ou pelo endpoint externo protegido por token (`external` → PDF em base64 no JSON).

---

## 13. Integração com o Replicado (Banco Acadêmico da USP)

O pacote `uspdev/replicado` fornece acesso SQL bruto ao esquema da USP. As consultas relevantes estão nos models:

- `Student::getFromReplicadoByCodpes` / `getFromReplicadoByNompes` (join PESSOA + EMAILPESSOA; a variante por nomes filtra não-alunos).
- `Student::getTelefonesFromReplicado`, `Student::getSexo`, `Student::getVinculoFromReplicadoAtSchoolTerm` (determinação de graduação/pós-graduação).
- `Instructor::getFromReplicadoByCodpes` (com `tipfnc='Docente'`), `getFromReplicadoBySchoolClass` (join OCUPTURMA+MINISTRANTE), `getSexo`, `getPronounTreatment`.
- `SchoolClass::getDisciplinesFromReplicadoBySchoolTermAndInstructor`, `getDisciplinesFromReplicadoByInstitute(UNIDADE)`, `getFromReplicadoBySchoolTerm`, `getFromReplicadoOldDB`, `calcEstimadedEnrollment`.
- `ClassSchedule::getFromReplicadoBySchoolClass`, `Department::getFromReplicadoByInstitute/Nomabvset/Codset`, `Course::getCourseFromReplicado`.
- `User::getVinculosFromReplicadoByCodpes`.

Variáveis de ambiente usadas: `UNIDADE`, `REPLICADO_*`, `LOG_AS_ADMINISTRATOR`, `IS_SUPERVISOR_CONFIG`, `BASE_PATH`, `PYTHON_CMD`, `EXTERNAL_REPORT_TOKEN`.

---

## 14. Referência de Validação (Form Requests)

Todos os 59 Form Requests foram inspecionados. **Nenhum realiza autorização em `authorize()` (retornam literal `true`/`false`).** Regras de validação principais:

**SchoolTerm** (`Store`/`Update`): `year` numérico; `period` em `1° Semestre,2° Semestre`; `status` em `Aberto,Fechado`; `max_enrollments` numérico >0; `public_notice` PDF ≤1000KB (`sometimes` no update); todas as datas `date_format:d/m/Y` com restrições `before` pareadas.

**SchoolClass** (`Store`): `periodoId` numérico; `department_id` numérico; `codtur` numérico; `coddis`,`nomdis`,`tiptur` obrigatórias; `dtainitur`/`dtafimtur` `d/m/Y` com `before`; `horarios.*` array com `diasmnocp in seg,ter,qua,qui,sex,sab,dom`, `horent`/`horsai` `H:i` em ordem; `instrutores.*.codpes` numérico. (`Update` omite codtur/coddis/nomdis/periodoId/department_id.)

**Requisition** (`Store`/`Update`): `school_class_id` numérico; `requested_number` numérico >0; `priority` em `1,2,3`; `recommendations.*.codpes` numérico; `activities.*` nas 3 atividades padrão; `scholarships.*` exists em Scholarship.

**Enrollment** (`Store`): `school_class_id` numérico; booleanos; `preferencia_horario` obrigatória; `observacoes` max 65500; `scholarships.*` exists. (`Update` identico, sem school_class_id.)

**SchoolRecord**: arquivo obrigatório, PDF, ≤1000KB. **Selection**: `enrollment_id` numérico (store); `school_class_id`+`codpes` numéricos (selectUnenrolled). **Frequency** `UpdateFrequencyRequest`: regras vazias (autorização tratada no controller). **SelfEvaluation** store: exige `selection_id`, `selection_hash`, `student_amount`,`homework_amount`,`workload` inteiros, opcionais `secondary_activity`/`workload_reason`/`comments`. **InstructorEvaluation** store: `ease_of_contact`,`efficiency`,`reliability`,`overall` em `0,1,2`; `comments` ≤65536 (update ≤512).

**MailTemplate**: `name` obrigatória; `subject` ≤256; `body` ≤8192; `sending_date`/`sending_hour` obrigatórios exceto `Manual`. **User** `UserRequest`: `email` única (respeita o `id` atual); `roles` array min 1. **Importação do banco antigo**: arquivo `csv,txt` ≤1000KB. **Trigger requests**: arrays de IDs.

**Regra personalizada:** `StorageFileExists` — verifica `Storage::exists(caminho)` para caminhos de arquivo baixáveis; mensagem "O arquivo não foi encontrado no servidor. Entrar em contato com o administrador da pagina."

---

## 15. Matriz de Autorização

Permissões por papel (do `RolesAndPermissionsSeeder`; `Administrador` tem todas):

| Permissão | Secretaria | Docente | Aluno | Presidente | Vice Pres. | Membro Comissão |
|---|---|---|---|---|---|---|
| visualizar todos inscritos | ✔ | | | ✔ | ✔ | ✔ |
| visualizar menu de configuração | ✔ | ✔ | | | | |
| editar usuario | ✔ | | | | | |
| visualizar periodo letivo | ✔ | ✔ | ✔ | | | |
| criar/editar periodo letivo | ✔ | | | | | |
| visualizar turma | ✔ | ✔ | ✔ | | | |
| criar/editar turma | ✔ | ✔ | | | | |
| importar turmas do replicado | ✔ | | | | | |
| buscar turmas | ✔ | | | | | |
| visualizar docente | ✔ | | | | | |
| visualizar solicitação de monitor | | ✔ | | | | |
| criar/editar solicitação de monitor | | ✔ | | | | |
| visualizar inscrição | ✔ | | ✔ | | | |
| fazer/editar/deletar inscrição | | | ✔ | | | |
| Selecionar monitor | ✔ | | | | | ✔ |
| Preterir monitor | ✔ | | | | | ✔ |
| Disparar emails | ✔ | | | | | |
| registrar frequencia | ✔ | ✔ | | | | |
| gerar relatorio | ✔ | | | | | ✔ |
| visualizar monitores | ✔ | | | | | ✔ |
| Editar E-mails | ✔ | | | | | |
| Visualizar auto avaliações | ✔ | | | | ✔ | ✔ |
| Visualizar avaliações dos docentes | ✔ | | | | ✔ | ✔ |
| Emitir Atestado | ✔ | | ✔ | | | |
| baixar histórico escolar | ✔ | | | | | ✔ |

Muitas views também aplicam verificações de papel adicionais inline (ex.: `Membro Comissão` apenas vê/serve o próprio departamento; `Docente` apenas as próprias turmas; alunos apenas os próprios dados).

---

## 16. Fundação para Testes / Checklist de Cenários

Esta seção enumera cenários testáveis agrupados por domínio, para guiar a geração de testes baseados em cenários.

### 16.1 Período Letivo
- [ ] Criar período com status `Aberto` quando outro está aberto → bloqueado com aviso.
- [ ] Criar período com datas inválidas (início > fim) → erros de validação.
- [ ] Atualizar período para `Aberto` quando existe outro período aberto (id diferente) → bloqueado.
- [ ] Enviar/substituir edital em PDF; rejeitar não-PDF; rejeitar >1000KB.
- [ ] Baixar edital com `path` válido vs caminho inexistente (StorageFileExists falha).
- [ ] Invariante de único período aberto em create/update.

### 16.2 Turma
- [ ] Store de nova turma (codtur+coddis) — cria turma, anexa instrutores, horários.
- [ ] Store de codtur+coddis duplicado:
  - sem instrutores → anexa instrutores/horários e atualiza;
  - com instrutores → aviso, sem alteração.
- [ ] Update desanexa/reanexa instrutores e horários.
- [ ] Destroy desanexa pivôs e exclui.
- [ ] Import do Replicado síncrono vs em fila (conforme `IS_SUPERVISOR_CONFIG`).
- [ ] Index com escopo por papel: Docente vê as próprias turmas; Membro vê as do departamento; demais veem todas.
- [ ] Busca por coddis.

### 16.3 Solicitação de Monitor
- [ ] Não-Docente bloqueado; período fechado / janela de solicitação encerrada / período aberto divergente → redireciona com aviso.
- [ ] Create exige ser instrutor da turma + período de solicitação ativo.
- [ ] Store persiste atividades, recomendações (criando alunos do Replicado) e bolsas.
- [ ] Update substitui atividades/recomendações/bolsas.

### 16.4 Inscrição e Histórico Escolar
- [ ] Deve enviar o histórico escolar antes de se inscrever (redireciona para schoolRecords.create).
- [ ] Inscrição cria uma inscrição em **cada** turma da `coddis` do período.
- [ ] Guarda de máximo de inscrições (`max_enrollments`).
- [ ] Update propaga para todas as inscrições da mesma coddis e ressincroniza bolsas.
- [ ] Delete bloqueado se houver seleção na disciplina; senão exclui todas as inscrições da mesma coddis.
- [ ] showAll lista todos os alunos com inscrições num período.

### 16.5 Seleção
- [ ] Store com inscrição já eleita → deseleciona automaticamente a seleção anterior + exclui frequências/avaliações.
- [ ] Bloqueio quando aluno já selecionado em outra turma do período aberto.
- [ ] Membro Comissão só pode selecionar no próprio departamento (senão 403).
- [ ] Frequências criadas para os meses ativos (3-6 ou 8-11).
- [ ] Registro de curso repopulado do Replicado para o aluno+período.
- [ ] `selectUnenrolled` guardas: já inscrito na turma; já selecionado em outra; sem histórico escolar.
- [ ] Preterir (destroy) bloqueado com frequência registrada; exclui seleção+frequências caso contrário.

### 16.6 Frequência
- [ ] Criação na seleção (meses ativos).
- [ ] Alternância apenas quando a seleção é `Ativo`.
- [ ] Janela de mês: bloqueada antes do dia 20 do mês atual e para meses futuros.
- [ ] `show` acessível via URL assinada válida; instrutor autenticado da turma + tutor pertence à turma.

### 16.7 Monitores
- [ ] Revoke exige Secretaria/Admin + `Ativo`; exclui frequências futuras não registradas; define Desligado + motdes + dtafimvin.
- [ ] Alternância voluntário/não voluntário apenas quando `Ativo`.

### 16.8 Atestados
- [ ] `make` renderiza template concluído vs em andamento conforme sitatl; propriedade verificada.

### 16.9 Avaliações
- [ ] Autoavaliação create/store via link assinado (hash sobre JSON da seleção) vs dono autenticado; período deve estar na janela de avaliação.
- [ ] Avaliação de docente: propriedade (instrutor da solicitação) e janela de período.
- [ ] updateOrCreate garante uma avaliação por seleção.

### 16.10 E-mails
- [ ] Trigger exige modelo ativo Manual por classe de e-mail (senão aviso).
- [ ] Trigger de seleção envia e-mail ao instrutor + todos os alunos selecionados.
- [ ] Trigger de frequência envia e-mail ao instrutor com link assinado; e-mail cancelado se monitor não `Ativo`.
- [ ] Triggers de autoavaliação/avaliação de docente apenas para seleções sem avaliação.
- [ ] Validação de mês para registros de frequência (meses permitidos, clamp nos limites).

### 16.11 Modelos de E-mail
- [ ] Nome duplicado rejeitado (store e update excluindo a si mesmo).
- [ ] Dois modelos ativos com envio Manual para a mesma mail_class rejeitados (update e activate).
- [ ] Frequência Manual limpa sending_date/hour.
- [ ] E-mail de teste usa um exemplo real; falha graciosamente se nenhum existir.

### 16.12 Usuários
- [ ] update desanexa todos os papéis e depois atribui os selecionados.
- [ ] busca por nome/codpes/papéis.
- [ ] Sincronização automática de papéis `Aluno`/`Docente` por vínculos no save; `LOG_AS_ADMINISTRATOR` concede Admin.

### 16.13 Relatórios
- [ ] `make` exige permissão; executa o script de gráficos e retorna PDF.
- [ ] `external` exige token correto + ano + periodo; retorna PDF em base64; token/parâmetros errados → status false + mensagem.

### 16.14 Importação do Banco Antigo
- [ ] Somente Admin; despacha job; linhas inválidas registradas como erros; linhas de dados importadas criam a cadeia completa de registros (instrutor, aluno, período, turma, solicitação, inscrição, seleção=Concluido, frequências, avaliações opcionais).

### 16.15 Comandos
- [ ] `report:compare-classes` em table/json/csv; comportamentos das opções; falhas de validação de ambiente.
- [ ] `sync:class-instructors` JSON de dry-run vs transação commitada; garantia de só-aditivo; rollback em falha.

### 16.16 Agendamento
- [ ] E-mails por cada tipo de frequência (Única/Mensal/Inicio/Final da avaliação).
- [ ] Fechamento automático: em finished_at 23:59 transforma `Ativo` → `Concluido`.

---

*Fim do documento. Gerado a partir da análise direta do código-fonte em 2026-08-21.*