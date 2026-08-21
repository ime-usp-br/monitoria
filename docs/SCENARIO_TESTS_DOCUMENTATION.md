# Sistema de Monitoria — Documentação de Testes de Cenário (Completa)

**Projeto:** Sistema de Monitoria do IME (Instituto de Matemática e Estatística, USP)
**Fonte:** `docs/TECHNICAL_DOCUMENTATION.md` (derivada da análise do código-fonte)
**Data de geração:** 2026-08-21

Esta documentação define a suíte completa de testes baseados em cenários, derivada diretamente da documentação técnica. Cada cenário segue o formato **Dado / Quando / Então** (Given/When/Then) e está associado às regras de negócio, guardas e comportamentos documentados no código-fonte.

---

## Sumário

1. [Convenções, Pré-condições e Dados de Base](#1-convenções-pré-condições-e-dados-de-base)
2. [Cenários — Período Letivo (`SchoolTerm`)](#2-cenários--período-letivo-schoolterm)
3. [Cenários — Turmas (`SchoolClass`)](#3-cenários--turmas-schoolclass)
4. [Cenários — Docentes (`Instructor`)](#4-cenários--docentes-instructor)
5. [Cenários — Solicitações de Monitor (`Requisition`)](#5-cenários--solicitações-de-monitor-requisition)
6. [Cenários — Alunos e Histórico Escolar (`Student` / `SchoolRecord`)](#6-cenários--alunos-e-histórico-escolar-student--schoolrecord)
7. [Cenários — Inscrições (`Enrollment`)](#7-cenários--inscrições-enrollment)
8. [Cenários — Seleção de Monitores (`Selection`)](#8-cenários--seleção-de-monitores-selection)
9. [Cenários — Frequência (`Frequency`)](#9-cenários--frequência-frequency)
10. [Cenários — Monitores (`Tutor`)](#10-cenários--monitores-tutor)
11. [Cenários — Atestados (`Certificate`)](#11-cenários--atestados-certificate)
12. [Cenários — Autoavaliação (`SelfEvaluation`)](#12-cenários--autoavaliação-selfevaluation)
13. [Cenários — Avaliação do Docente (`InstructorEvaluation`)](#13-cenários--avaliação-do-docente-instructorevaluation)
14. [Cenários — Modelos de E-mail (`MailTemplate`)](#14-cenários--modelos-de-e-mail-mailtemplate)
15. [Cenários — Disparo de E-mails (`EmailController`)](#15-cenários--disparo-de-e-mails-emailcontroller)
16. [Cenários — Usuários (`User`)](#16-cenários--usuários-user)
17. [Cenários — Relatórios (`Report`)](#17-cenários--relatórios-report)
18. [Cenários — Importação do Banco Antigo (`OldDB`)](#18-cenários--importação-do-banco-antigo-olddb)
19. [Cenários — Jobs e Filas em Segundo Plano](#19-cenários--jobs-e-filas-em-segundo-plano)
20. [Cenários — Tarefas Agendadas (Kernel)](#20-cenários--tarefas-agendadas-kernel)
21. [Cenários — Comandos do Console](#21-cenários--comandos-do-console)
22. [Cenários — Integração com o Replicado](#22-cenários--integração-com-o-replicado)
23. [Matriz de Rastreabilidade Cenário → Regra](#23-matriz-de-rastreabilidade-cenário--regra)

---

## 1. Convenções, Pré-condições e Dados de Base

### 1.1 Convenções de escrita

- **Formato:** cada cenário é escrito como `CEN-<Módulo>-<NNN>` com `Dado`/`Quando`/`Então`.
- **Níveis de teste:** os cenários podem ser executados como testes funcionais (Feature) no Laravel 8 com PHPUnit + RefreshDatabase, testes de integração com o Replicado (stub dos models `Student`/`Instructor`/`SchoolClass` que consultam o banco acadêmico) ou testes de contrato ligados ao comportamento do controller.
- **Sinal `[N/D]`:** indica cenário que depende de dados externos do Replicado (stub necessário).
- **Sinal `[INTEGRAÇÃO]`:** cenário que depende de bibliotecas externas (LaraTeX/pdflatex, Python, fila).
- **Sinal `[AGENDADO]`:** cenário que depende do agendador do Kernel.

### 1.2 Dados de base (factories / seeders)

Para todos os cenários, assumir disponíveis:

1. **Papéis (RolesAndPermissionsSeeder):** `Administrador`, `Secretaria`, `Docente`, `Aluno`, `Monitor`, `Presidente de Comissão`, `Vice Presidente de Comissão`, `Membro Comissão` com as permissões da [Seção 15 da documentação técnica](#matriz-de-autorização).
2. **Atividades padrão:** `Atendimento a alunos`, `Correção de listas de exercícios`, `Fiscalização de provas`.
3. **Departamentos (Replicado):** ao menos MAC, MAP, MAE, MAT com `codset`, `nomabvset`, `nomset`, `sglund`, `nomund`.
4. **Período letivo padrão:** `year=2026`, `period="1° Semestre"`, `status="Aberto"`, `max_enrollments=5`, janelas de datas coerentes:
   - `start_date_requisitions` ≤ hoje ≤ `end_date_requisitions`
   - `start_date_enrollments` ≤ hoje ≤ `end_date_enrollments`
   - `start_date_evaluations` ≤ hoje ≤ `end_date_evaluations` (quando o cenário exigir)
5. **Usuários:** um `Admin`, uma `Secretaria`, um `Docente` instrutor da turma, um aluno `Aluno`, um `Membro Comissão` do mesmo departamento da turma e um `Membro Comissão` de departamento diferente.
6. **Turma:** `SchoolClass` com `codtur`, `coddis`, `nomdis`, `department_id`, no período padrão, com o docente como instrutor, com `ClassSchedule`.
7. **Solicitação de monitor:** `Requisition` do docente para a turma com `requested_number=1`, `priority=1`, com as 3 atividades padrão.
8. **Aluno cadastrado:** `Student` com codpes/nompes/codema; `SchoolRecord` do período enviado.
9. **Inscrição:** `Enrollment` do aluno na turma.

> **Nota:** a menos que o cenário diga o contrário, apenas UMA período letivo `Aberto` existe no banco (invariante de singleton).

---

## 2. Cenários — Período Letivo (`SchoolTerm`)

### CEN-SCHOOLTERM-001 — Criar período com status Aberto quando já existe outro aberto é bloqueado
- **Dado** que existe um `SchoolTerm` com `status="Aberto"` (período 2026/1° Semestre)
- **Quando** o Admin acessa `schoolterms.store` enviando um novo período com `status="Aberto"` (2026/2° Semestre)
- **Então** a operação é abortada com aviso
- **E** nenhum novo período é persistido
- **E** o usuário é redirecionado de volta (`back()`)

### CEN-SCHOOLTERM-002 — Criar período com status Fechado com outro aberto é permitido
- **Dado** um período `Aberto` existente
- **Quando** o Admin cria um novo período com `status="Fechado"`
- **Então** o período é criado com sucesso
- **E** o singleton de período aberto é preservado

### CEN-SCHOOLTERM-003 — Criar período com início depois do fim é rejeitado pela validação
- **Dado** um período sem períodos abertos
- **Quando** o Admin envia `start_date_requisitions` posterior a `end_date_requisitions` (formato `d/m/Y`)
- **Então** a validação falha com erros de `before` nas datas emparelhadas
- **E** o período não é persistido

### CEN-SCHOOLTERM-004 — Criar período com ano/periodo duplicado atualiza em vez de duplicar
- **Dado** que existe um período com `year=2026` e `period="1° Semestre"`
- **Quando** o Admin envia novamente `year=2026`, `period="1° Semestre"` com dados atualizados e status `Fechado`
- **Então** `SchoolTerm::updateOrCreate(['year','period'], ...)` atualiza o registro existente (nenhum registro duplicado)

### CEN-SCHOOLTERM-005 — Validações de formulário de criação
- **Dado** o endpoint `schoolterms.store` como Admin
- **Quando** enviados payloads inválidos:
  - `year` não numérico,
  - `period` fora de `1° Semestre/2° Semestre`,
  - `status` fora de `Aberto/Fechado`,
  - `max_enrollments` não numérico ou ≤ 0,
  - datas fora do formato `d/m/Y`
- **Então** cada caso retorna erros de validação correspondentes

### CEN-SCHOOLTERM-006 — Enviar edital em PDF válido é armazenado sob a pasta do período
- **Dado** um upload de `public_notice` PDF (≤ 1000KB)
- **Quando** o Admin cria o período 2026/1° Semestre
- **Então** o arquivo é armazenado sob o diretório derivado de `{year}{period[0]}` (ex.: `20261`)
- **E** `public_notice_file_path` é preenchido com o caminho do storage

### CEN-SCHOOLTERM-007 — Edital não-PDF é rejeitado
- **Dado** o upload de `public_notice` com extensão `.txt`
- **Quando** o Admin envia o formulário de criação
- **Então** a validação falha (mime não-PDF)
- **E** o período não é criado

### CEN-SCHOOLTERM-008 — Edital acima de 1000KB é rejeitado
- **Dado** o upload de `public_notice` PDF com mais de 1000KB
- **Quando** o Admin envia o formulário de criação
- **Então** a validação falha
- **E** o período não é criado

### CEN-SCHOOLTERM-009 — Atualizar período para Aberto quando outro aberto existe (id diferente) é bloqueado
- **Dado** que existe o período A com `status="Aberto"` e o período B com `status="Fechado"`
- **Quando** o Admin atualiza o período B para `status="Aberto"`
- **Então** a operação é abortada com aviso
- **E** o período B permanece `Fechado`
- **E** o período A permanece o único aberto

### CEN-SCHOOLTERM-010 — Atualizar o próprio período aberto mantendo Aberto é permitido
- **Dado** que o período A é `Aberto`
- **Quando** o Admin atualiza o próprio período A mantendo `status="Aberto"` (o bloqueio exclui a si mesmo do singleton)
- **Então** a atualização é aplicada com sucesso

### CEN-SCHOOLTERM-011 — Atualizar período de Fechado para Aberto quando nenhum outro aberto existe é permitido
- **Dado** que não existe nenhum período `Aberto`
- **Quando** o Admin atualiza um período `Fechado` para `Aberto`
- **Então** a atualização é aplicada com sucesso

### CEN-SCHOOLTERM-012 — Substituir edital na atualização
- **Dado** um período existente com `public_notice_file_path` antigo
- **Quando** o Admin atualiza o período enviando uma nova `public_notice` (PDF)
- **Então** `public_notice_file_path` passa a apontar para o novo arquivo
- **E** o arquivo antigo é substituído

### CEN-SCHOOLTERM-013 — Atualização sem novo edital mantém o caminho existente
- **Dado** um período existente com edital armazenado
- **Quando** o Admin atualiza o período sem enviar nova `public_notice` (`sometimes`)
- **Então** `public_notice_file_path` permanece inalterado

### CEN-SCHOOLTERM-014 — Baixar edital com caminho válido
- **Dado** um período com `public_notice_file_path` existente no storage
- **Quando** um usuário acessa `schoolterms.download` (validação por `DownloadPublicNoticeRequest`)
- **Então** o arquivo é baixado nomeado como `edital_monitoria.pdf`

### CEN-SCHOOLTERM-015 — Baixar edital com caminho inexistente falha
- **Dado** um período com `public_notice_file_path` que não existe no storage
- **Quando** um usuário acessa `schoolterms.download`
- **Então** a regra `StorageFileExists` falha
- **E** a mensagem exibida é "O arquivo não foi encontrado no servidor. Entrar em contato com o administrador da pagina."

### CEN-SCHOOLTERM-016 — Deletar período é stub vazio
- **Dado** um período existente
- **Quando** um usuário com `deletar periodo letivo` acessa `schoolterms.destroy`
- **Então** nenhuma alteração é feita no banco (método stub vazio)

### CEN-SCHOOLTERM-017 — Index lista períodos em ordem decrescente (ano, período)
- **Dado** períodos de anos/períodos variados
- **Quando** um usuário com `visualizar periodo letivo` acessa `schoolterms.index`
- **Então** os períodos são exibidos ordenados por `year` e `period` decrescentes

### CEN-SCHOOLTERM-018 — Acessos sem permissão nas telas de gerenciamento
- **Dado** um usuário sem a permissão respectiva
- **Quando** ele acessa `index` (`visualizar periodo letivo`), `create/store` (`criar periodo letivo`), `edit/update` (`editar periodo letivo`), `destroy` (`deletar periodo letivo`)
- **Então** o acesso é negado (403/redirect) para cada ação sem permissão

### CEN-SCHOOLTERM-019 — Persistência de datas normalizada (mutators)
- **Dado** o envio de datas no formato `d/m/Y`
- **Quando** um período é criado com `start_date_requisitions=01/02/2026` e `end_date_requisitions=28/02/2026`
- **Então** internamente a data de início é armazenada como `startOfDay` (2026-02-01 00:00:00) e a de fim como `endOfDay` (2026-02-28 23:59:59)
- **E** os accessors retornam os valores no formato `d/m/Y`

### CEN-SCHOOLTERM-020 — Auxiliar de período de solicitação
- **Dado** um período com `start_date_requisitions=01/02/2026` e `end_date_requisitions=28/02/2026`
- **Quando** a hora atual é 15/02/2026
- **Então** `isRequisitionPeriod()` retorna `true`
- **Quando** a hora atual é 01/03/2026
- **Então** `isRequisitionPeriod()` retorna `false`

### CEN-SCHOOLTERM-021 — Auxiliar de período de inscrição
- **Dado** um período com `start_date_enrollments=01/03/2026` e `end_date_enrollments=31/03/2026`
- **Quando** a hora atual é 10/03/2026
- **Então** `isEnrollmentPeriod()` retorna `true`
- **Quando** a hora atual é 01/04/2026
- **Então** `isEnrollmentPeriod()` retorna `false`

### CEN-SCHOOLTERM-022 — Auxiliar de período de avaliação
- **Dado** um período com `start_date_evaluations=01/07/2026` e `end_date_evaluations=31/07/2026`
- **Quando** a hora atual é 15/07/2026
- **Então** `isInEvaluationPeriod()` (instância) retorna `true`
- **Quando** a hora atual é 01/08/2026
- **Então** `isInEvaluationPeriod()` retorna `false`

---

## 3. Cenários — Turmas (`SchoolClass`)

### CEN-SCHOOLCLASS-001 — Store de nova turma cria a turma, instrutores e horários
- **Dado** um período de solicitação ativo e dados do Replicado para `codtur` e `coddis` ainda não existentes localmente
- **Quando** um usuário com `criar turma` envia `schoolclasses.store` com `codtur`, `coddis`, `nomdis`, `department_id`, `instrutores.*.codpes` e `horarios.*`
- **Então** uma `SchoolClass` é criada com a chave única `(codtur, coddis)`
- **E** os instrutores são criados via `Instructor::firstOrCreate(getFromReplicadoByCodpes)` e anexados ao pivô `instructor_school_class`
- **E** os horários são criados e anexados ao pivô `class_schedule_school_class`

### CEN-SCHOOLCLASS-002 — Store de turma duplicada sem instrutores anexa instrutores e horários
- **Dado** uma `SchoolClass` existente com `(codtur, coddis)` que ainda não possui instrutores
- **Quando** o usuário envia `schoolclasses.store` para a mesma chave com instrutores e horários
- **Então** a turma não é duplicada
- **E** os instrutores são anexados
- **E** os horários são anexados
- **E** a turma é atualizada

### CEN-SCHOOLCLASS-003 — Store de turma duplicada com instrutores exibe aviso
- **Dado** uma `SchoolClass` existente com `(codtur, coddis)` com instrutores já anexados
- **Quando** o usuário envia `schoolclasses.store` para a mesma chave
- **Então** a operação é abortada com o aviso "já cadastrada"
- **E** nenhuma alteração é feita

### CEN-SCHOOLCLASS-004 — Validações do formulário de criação de turma
- **Dado** o endpoint `schoolclasses.store` como usuário com `criar turma`
- **Quando** enviados payloads inválidos (campos obrigatórios ausentes: `coddis`, `nomdis`, `tiptur`; `codtur` não numérico; `dtainitur`/`dtafimtur` fora de `d/m/Y` ou com início após fim; `horarios.*.diasmnocp` fora de `seg,ter,qua,qui,sex,sab,dom`; `horent`/`horsai` fora de `H:i` ou invertidos; `instrutores.*.codpes` não numérico)
- **Então** cada caso retorna erros de validação correspondentes

### CEN-SCHOOLCLASS-005 — Update desanexa e reanexa instrutores e horários
- **Dado** uma turma com instrutores e horários existentes
- **Quando** o usuário com `editar turma` atualiza a turma com um novo conjunto de instrutores (resolvidos via `Pessoa::obterNome`) e horários
- **Então** os pivôs anteriores são desanexados
- **E** os novos instrutores e horários são anexados
- **E** os campos da turma são atualizados

### CEN-SCHOOLCLASS-006 — Update omite campos imutáveis na validação
- **Dado** a atualização de uma turma existente
- **Quando** o formulário `Update` é enviado
- **Então** campos como `codtur`, `coddis`, `nomdis`, `periodoId` e `department_id` não são exigidos pela validação (omissão deliberada)

### CEN-SCHOOLCLASS-007 — Destroy desanexa pivôs e exclui a turma
- **Dado** uma turma com instrutores e horários anexados
- **Quando** o usuário com `deletar turma` acessa `schoolclasses.destroy`
- **Então** os pivôs `instructor_school_class` e `class_schedule_school_class` são removidos
- **E** a `SchoolClass` é excluída (cascade nas dependências)

### CEN-SCHOOLCLASS-008 — Import síncrono quando IS_SUPERVISOR_CONFIG é falso
- **Dado** `IS_SUPERVISOR_CONFIG=false`
- **Quando** o usuário com `importar turmas do replicado` acessa `schoolclasses.import`
- **Então** a importação ocorre de forma síncrona via `SchoolClass::getFromReplicadoBySchoolTerm`
- **E** turmas, departamentos, instrutores e horários do Replicado são persistidos localmente

### CEN-SCHOOLCLASS-009 — Import em fila quando IS_SUPERVISOR_CONFIG é verdadeiro
- **Dado** `IS_SUPERVISOR_CONFIG=true` e fila configurada
- **Quando** o usuário com `importar turmas do replicado` acessa `schoolclasses.import`
- **Então** o job `ProcessGetSchoolClassesFromReplicado` é despachado para a fila
- **E** a página responde imediatamente sem aguardar a importação

### CEN-SCHOOLCLASS-010 — Index com escopo de papel: Docente vê apenas as próprias turmas
- **Dado** turmas T1 (do docente) e T2 (de outro docente) no período selecionado
- **Quando** o `Docente` acessa `schoolclasses.index`
- **Então** apenas T1 aparece na listagem

### CEN-SCHOOLCLASS-011 — Index com escopo de papel: Membro de Comissão vê apenas turmas do departamento
- **Dado** turmas no departamento MAC (do membro) e no departamento MAT
- **Quando** o `Membro Comissão` (que não é Secretaria) acessa `schoolclasses.index`
- **Então** apenas as turmas do departamento MAC aparecem

### CEN-SCHOOLCLASS-012 — Index sem escopo: Secretaria e Admin veem todas as turmas
- **Dado** turmas em múltiplos departamentos
- **Quando** `Secretaria` ou `Admin` acessa `schoolclasses.index`
- **Então** todas as turmas do período são listadas

### CEN-SCHOOLCLASS-013 — Index seleciona período aberto ou o mais recente
- **Dado** um período aberto existente
- **Quando** `schoolclasses.index` é acessado sem `periodoId`
- **Então** o período aberto é selecionado
- **Dado** que não há período aberto, mas há períodos antigos
- **Quando** `schoolclasses.index` é acessado sem `periodoId`
- **Então** o período mais recente é selecionado

### CEN-SCHOOLCLASS-014 — Busca de turmas por coddis
- **Dado** turmas com códigos distintos no período
- **Quando** o usuário com `visualizar turma` busca por um `coddis`
- **Então** apenas as turmas cujo código corresponde ao filtro são exibidas

### CEN-SCHOOLCLASS-015 — View de inscrições e de monitores eleitos
- **Dado** uma turma com inscrições e com seleções
- **Quando** um usuário com `visualizar inscrição` acessa `schoolclasses.enrollments`
- **Então** a view de inscrições da turma é renderizada
- **Quando** um usuário com `registrar frequencia` acessa `schoolclasses.electedTutors`
- **Então** a view de monitores eleitos da turma é renderizada

### CEN-SCHOOLCLASS-016 — Formulário de criação popula departamentos do Replicado
- **Dado** departamentos disponíveis no Replicado para a unidade `env('UNIDADE')`
- **Quando** o usuário com `criar turma` acessa `schoolclasses.create`
- **Então** `Department::getFromReplicadoByInstitute(env('UNIDADE'))` popula a seleção de departamentos

---

## 4. Cenários — Docentes (`Instructor`)

### CEN-INSTRUCTOR-001 — Busca JSON por codpes retorna nome de docente [N/D]
- **Dado** um `codpes` válido no Replicado com vínculo de Docente
- **Quando** o endpoint de busca consulta `Instructor::getFromReplicadoByCodpes`
- **Então** a resposta JSON contém o `nompes` do docente
- **E** o vínculo verificado é `tipfnc='Docente'`

### CEN-INSTRUCTOR-002 — Busca JSON por codpes sem vínculo docente retorna vazio [N/D]
- **Dado** um `codpes` sem vínculo Docente no Replicado
- **Quando** o endpoint de busca é consultado
- **Então** a resposta JSON é `""`

### CEN-INSTRUCTOR-003 — Index lista docentes ordenados pela quantidade de solicitações
- **Dado** docentes com diferentes `SUM(requisitions.requested_number)` e docentes sem solicitações
- **Quando** um usuário com `visualizar docente` acessa `instructors.index`
- **Então** os docentes com solicitações são listados primeiro, em ordem decrescente de quantidade solicitada
- **E** os docentes sem solicitações aparecem mesclados na listagem

### CEN-INSTRUCTOR-004 — View de solicitações do docente
- **Dado** um docente com solicitações
- **Quando** um usuário com `visualizar docente` acessa `instructors.requisitions`
- **Então** a view `instructors.requisitions` é renderizada com as solicitações do docente

### CEN-INSTRUCTOR-005 — Busca por codpes
- **Dado** docentes com códigos distintos
- **Quando** o usuário com `visualizar docente` busca por um `codpes`
- **Então** apenas o docente correspondente é exibido

### CEN-INSTRUCTOR-006 — Métodos CRUD de docente são stubs vazios
- **Dado** o controller `InstructorController`
- **Quando** são acessados `create/store/show/edit/update/destroy`
- **Então** nenhuma operação é realizada (stubs vazios)

---

## 5. Cenários — Solicitações de Monitor (`Requisition`)

### CEN-REQUISITION-001 — Acesso ao index requer papel Docente
- **Dado** um usuário autenticado sem o papel `Docente`
- **Quando** ele acessa `requisitions.index`
- **Então** o acesso é negado

### CEN-REQUISITION-002 — Index com período de solicitação inativo redireciona com aviso
- **Dado** um período aberto cuja janela de solicitação já encerrou (`isRequisitionPeriod() == false`)
- **Quando** um `Docente` acessa `requisitions.index`
- **Então** ele é redirecionado com aviso informando que o período de solicitação não está ativo

### CEN-REQUISITION-003 — Index sem período aberto redireciona com aviso
- **Dado** que não existe nenhum período com `status="Aberto"`
- **Quando** um `Docente` acessa `requisitions.index`
- **Então** ele é redirecionado com aviso

### CEN-REQUISITION-004 — Index com período aberto divergente do período de solicitação avisa a secretaria
- **Dado** que o período aberto é 2026/1° e o período em janela de solicitação é 2026/2°
- **Quando** um `Docente` acessa `requisitions.index`
- **Então** é exibido o aviso "favor informar a secretaria de monitoria"
- **E** o acesso é bloqueado

### CEN-REQUISITION-005 — Index lista as turmas do docente no período de solicitação
- **Dado** um `Docente` instrutor das turmas T1 e T2, e um segundo docente instrutor de T3
- **Quando** o primeiro docente acessa `requisitions.index` com a janela de solicitação ativa e o período aberto idêntico ao de solicitação
- **Então** apenas T1 e T2 são listadas para solicitação

### CEN-REQUISITION-006 — Create exige ser instrutor da turma
- **Dado** uma turma T2 cujo instrutor é outro docente
- **Quando** o `Docente` autenticado tenta criar solicitação para T2
- **Então** a guarda `isInstructor(codpes do usuário)` falha
- **E** o acesso é negado

### CEN-REQUISITION-007 — Create exige que a turma esteja no período de solicitação
- **Dado** uma turma fora do período de solicitação (período antigo ou janela encerrada)
- **Quando** o `Docente` instrutor da turma tenta criar solicitação
- **Então** o acesso é negado

### CEN-REQUISITION-008 — Store cria solicitação com instrutor do usuário autenticado
- **Dado** um `Docente` autenticado e uma turma própria no período de solicitação
- **Quando** ele envia `requisitions.store` com `school_class_id`, `requested_number`, `priority`, `activities`, `recommendations` e `scholarships`
- **Então** uma `Requisition` é criada com `instructor_id` correspondente ao instrutor do usuário autenticado
- **E** `requested_number` e `priority` são persistidos conforme enviado
- **E** `comments` é persistido se informado

### CEN-REQUISITION-009 — Store anexa atividades padrão
- **Dado** o envio de `activities` com as atividades `Atendimento a alunos` e `Correção de listas de exercícios`
- **Quando** `requisitions.store` é executado com sucesso
- **Então** as atividades são criadas via `Activity::firstOrCreate` (sem duplicação)
- **E** os registros estão ligados à solicitação pelo pivô `activity_requisition`

### CEN-REQUISITION-010 — Store cria recomendações atualizando/criando alunos do Replicado
- **Dado** recomendações com `codpes` de alunos no Replicado
- **Quando** `requisitions.store` é executado com sucesso
- **Então** alunos são criados/atualizados a partir do Replicado (`Student::getFromReplicadoByCodpes`)
- **E** registros de `Recommendation` são criados ligando aluno + solicitação

### CEN-REQUISITION-011 — Store anexa bolsas externas
- **Dado** o envio de `scholarships` com IDs existentes na tabela `scholarships`
- **Quando** `requisitions.store` é executado com sucesso
- **Então** as bolsas são anexadas via morfismo `model_has_scholarships`

### CEN-REQUISITION-012 — Validação do formulário de criação/edição
- **Dado** o endpoint `requisitions.store`/`update` como `Docente`
- **Quando** enviados payloads inválidos: `school_class_id` não numérico, `requested_number` não numérico ou ≤ 0, `priority` fora de `1,2,3`, `recommendations.*.codpes` não numérico, `activities.*` fora das 3 atividades padrão, `scholarships.*` inexistente
- **Então** cada caso retorna erros de validação correspondentes

### CEN-REQUISITION-013 — Update substitui atividades
- **Dado** uma solicitação existente com atividades A1 e A2
- **Quando** o docente envia `requisitions.update` com apenas A3
- **Então** A1 e A2 são desanexadas
- **E** A3 é anexada (firstOrCreate)
- **E** a solicitação é atualizada

### CEN-REQUISITION-014 — Update exclui e recria recomendações
- **Dado** uma solicitação com recomendações R1 e R2
- **Quando** o docente envia `requisitions.update` com apenas a recomendação R3 (`codpes` novo)
- **Então** R1 e R2 são excluídas
- **E** R3 é criada
- **E** alunos do Replicado são sincronizados para cada novo `codpes`

### CEN-REQUISITION-015 — Update ressincroniza bolsas
- **Dado** uma solicitação com as bolsas B1 e B2 anexadas
- **Quando** o docente envia `requisitions.update` com apenas B1
- **Então** B2 é desanexada e B1 permanece anexada

### CEN-REQUISITION-016 — Edit exige turma própria e período de solicitação
- **Dado** uma solicitação cuja turma não pertence ao docente autenticado (ou fora da janela de solicitação)
- **Quando** o docente tenta acessar `requisitions.edit`
- **Então** o acesso é negado

### CEN-REQUISITION-017 — Destroy é stub vazio
- **Dado** uma solicitação existente
- **Quando** `requisitions.destroy` é invocado
- **Então** nenhuma alteração é feita (stub vazio)

### CEN-REQUISITION-018 — Assinatura de instrutor correta na criação
- **Dado** um usuário `Docente` cujo registro de `Instructor` existe
- **Quando** `requisitions.store` é executado
- **Então** `instructor_id` é definido a partir do instrutor do usuário autenticado (nunca do valor enviado)

---

## 6. Cenários — Alunos e Histórico Escolar (`Student` / `SchoolRecord`)

### CEN-STUDENT-001 — Busca JSON por codpes retorna dados do aluno do Replicado [N/D]
- **Dado** um `codpes` de aluno válido no Replicado
- **Quando** o endpoint consulta `Student::getFromReplicadoByCodpes`
- **Então** a resposta JSON contém os dados do aluno

### CEN-STUDENT-002 — Busca JSON por codpes inexistente retorna vazio [N/D]
- **Dado** um `codpes` não encontrado no Replicado
- **Quando** o endpoint é consultado
- **Então** a resposta JSON é `""`

### CEN-STUDENT-003 — Busca JSON por nompes retorna somente pessoas com vínculo Aluno [N/D]
- **Dado** uma busca por `nompes`
- **Quando** o endpoint consulta `Student::getFromReplicadoByNompes`
- **Então** apenas pessoas com vínculo `Aluno` (ALUNOGR/ALUNOPOS/ALUNOPOSESP) são retornadas
- **Quando** a busca não encontra nenhum aluno
- **Então** a resposta JSON é `""`

### CEN-STUDENT-004 — Métodos de criação/edição de aluno são stubs
- **Dado** o controller `StudentController`
- **Quando** um usuário com papel `Aluno` acessa `create/store` (que exigem o papel, mas não têm lógica) e os demais métodos `show/edit/update/destroy`
- **Então** nenhuma operação de persistência é realizada (stubs vazios)

### CEN-SCHOOLRECORD-001 — Create exige Aluno no período de inscrições
- **Dado** um usuário sem papel `Aluno` (ou fora do período de inscrições)
- **Quando** ele acessa `schoolRecords.create`
- **Então** o acesso é negado

### CEN-SCHOOLRECORD-002 — Store persiste o histórico e redireciona para inscrições
- **Dado** um `Aluno` autenticado no período de inscrições
- **Quando** ele envia um PDF válido em `schoolRecords.store`
- **Então** o arquivo é armazenado sob `{year}{period[0]}` (ex.: `20261`)
- **E** um `SchoolRecord` é criado para o período de inscrições + aluno autenticado
- **E** o usuário é redirecionado para as inscrições

### CEN-SCHOOLRECORD-003 — Validação: arquivo obrigatório, PDF, ≤ 1000KB
- **Dado** o endpoint `schoolRecords.store` como `Aluno` no período de inscrições
- **Quando** enviado sem arquivo, com arquivo não-PDF, ou com mais de 1000KB
- **Então** a validação falha nos três casos

### CEN-SCHOOLRECORD-004 — Update substitui o arquivo no lugar
- **Dado** um `SchoolRecord` existente do aluno no período
- **Quando** o aluno envia um novo PDF em `schoolRecords.update`
- **Então** `file_path` é substituído pelo novo caminho
- **E** nenhum registro duplicado é criado

### CEN-SCHOOLRECORD-005 — Download valida existência do arquivo no storage
- **Dado** um `SchoolRecord` com `file_path` que existe no storage
- **Quando** um usuário com `baixar histórico escolar` acessa `schoolRecords.download`
- **Então** o arquivo é baixado com o nome `{primeirosegmentodocaminho}.pdf`
- **Dado** um `SchoolRecord` cujo `file_path` não existe no storage
- **Quando** o download é solicitado
- **Então** a regra `StorageFileExists` falha com a mensagem "O arquivo não foi encontrado no servidor. Entrar em contato com o administrador da pagina."

### CEN-SCHOOLRECORD-006 — Demais métodos CRUD são stubs
- **Dado** o controller `SchoolRecordController`
- **Quando** são acessados `index/show/edit/destroy`
- **Então** nenhuma operação é realizada (stubs vazios)

### CEN-SCHOOLRECORD-007 — Unicidade de histórico por aluno e período
- **Dado** um aluno com `SchoolRecord` já existente para o período
- **Quando** um novo upload é feito na mesma combinação (aluno, período de inscrições)
- **Então** a unicidade `(student_id, schoolterm_id)` é respeitada (update no lugar, sem duplicação)

---

## 7. Cenários — Inscrições (`Enrollment`)

### CEN-ENROLLMENT-001 — Index exige papel Aluno
- **Dado** um usuário autenticado sem o papel `Aluno`
- **Quando** ele acessa `enrollments.index`
- **Então** o acesso é negado

### CEN-ENROLLMENT-002 — Index fora do período de inscrições redireciona com aviso
- **Dado** um `Aluno` autenticado e um período aberto cuja janela de inscrições encerrou
- **Quando** ele acessa `enrollments.index`
- **Então** ele é redirecionado com aviso

### CEN-ENROLLMENT-003 — Index sem período aberto ou com período aberto divergente do de inscrições
- **Dado** que não existe período aberto, ou o período aberto difere do período em janela de inscrições
- **Quando** um `Aluno` acessa `enrollments.index`
- **Então** o acesso é bloqueado com aviso ("favor informar a secretaria de monitoria" no caso de divergência)

### CEN-ENROLLMENT-004 — Index redireciona para envio do histórico quando não enviado
- **Dado** um `Aluno` autenticado no período de inscrições com período aberto coerente
- **E** que o aluno ainda não possui `SchoolRecord` para o período aberto
- **Quando** ele acessa `enrollments.index`
- **Então** ele é redirecionado para `schoolRecords.create`

### CEN-ENROLLMENT-005 — Index lista turmas inscritas primeiro e depois as demais
- **Dado** um `Aluno` já inscrito na turma T1 (coddis A) e não inscrito nas turmas T2/T3 de outras disciplinas no período de inscrições
- **Quando** ele acessa `enrollments.index` com histórico enviado
- **Então** T1 (já inscrita) aparece primeiro
- **E** T2/T3 (não inscritas) aparecem depois, ordenadas por `coddis`

### CEN-ENROLLMENT-006 — Guarda de máximo de inscrições
- **Dado** um período com `max_enrollments=2`
- **E** um `Aluno` já inscrito em 2 `coddis` distintas nas turmas do período
- **Quando** ele tenta acessar `enrollments.create`
- **Então** é exibido o aviso "excedeu o número máximo de inscrições"
- **E** a criação é bloqueada

### CEN-ENROLLMENT-007 — Guarda de máximo respeitada no limite
- **Dado** um período com `max_enrollments=2`
- **E** um `Aluno` inscrito em apenas 1 `coddis`
- **Quando** ele acessa `enrollments.create`
- **Então** o acesso é permitido (contagem < máximo)

### CEN-ENROLLMENT-008 — Store inscreve em todas as turmas da mesma coddis
- **Dado** um período de inscrições com três turmas T1, T2, T3 da mesma `coddis` "MAC0110"
- **Quando** o `Aluno` envia `enrollments.store` para T1
- **Então** uma `Enrollment` é criada para T1, T2 e T3
- **E** `student_id` é o aluno do usuário autenticado
- **E** as bolsas são anexadas a cada inscrição criada

### CEN-ENROLLMENT-009 — Store persiste preferências e observações
- **Dado** um `Aluno` autenticado no período de inscrições
- **Quando** ele envia `enrollments.store` com `voluntario`, `disponibilidade_diurno`, `disponibilidade_noturno`, `preferencia_horario` e `observacoes`
- **Então** todos os campos são persistidos nas inscrições criadas

### CEN-ENROLLMENT-010 — Validação do formulário de criação
- **Dado** o endpoint `enrollments.store` como `Aluno` no período de inscrições
- **Quando** enviado payload inválido: `school_class_id` não numérico, `preferencia_horario` ausente, `observacoes` > 65500 caracteres, `scholarships.*` inexistente
- **Então** a validação falha nos casos correspondentes

### CEN-ENROLLMENT-011 — Update propaga para todas as inscrições da mesma coddis
- **Dado** um `Aluno` inscrito nas turmas T1, T2, T3 da mesma `coddis` no período
- **Quando** ele envia `enrollments.update` para a inscrição de T1 alterando `voluntario=false` e disponibilidades
- **Então** todas as inscrições do mesmo aluno + coddis no período são atualizadas
- **E** as bolsas de cada inscrição são ressincronizadas conforme o payload

### CEN-ENROLLMENT-012 — Update normaliza booleanos
- **Dado** o envio de `voluntario`/`disponibilidade_*` como strings `"0"`/`"1"` ou valores ausentes
- **Quando** `enrollments.update` é executado
- **Então** os valores são normalizados para booleanos antes da persistência

### CEN-ENROLLMENT-013 — Delete bloqueado quando há seleção na disciplina
- **Dado** um `Aluno` selecionado (possui `Selection`) em uma das turmas da sua `coddis` no período
- **Quando** ele tenta excluir sua inscrição
- **Então** a exclusão é bloqueada com a mensagem para contatar a comissão ("você foi selecionado... comunique a comissão")
- **E** nenhuma inscrição é removida

### CEN-ENROLLMENT-014 — Delete exclui todas as inscrições da mesma coddis
- **Dado** um `Aluno` inscrito nas turmas T1, T2 da mesma `coddis`, sem nenhuma `Selection` associada
- **Quando** ele exclui a inscrição de T1
- **Então** as inscrições de T1 e T2 são excluídas (conjunto como unidade)

### CEN-ENROLLMENT-015 — showAll lista alunos inscritos no período
- **Dado** um período com inscrições de vários alunos
- **Quando** um usuário com `visualizar todos inscritos` acessa `enrollments.showAll` para o período
- **Então** os alunos com inscrições no período são listados em ordem alfabética de nome

### CEN-ENROLLMENT-016 — ShowAll sem permissão é negado
- **Dado** um usuário sem a permissão `visualizar todos inscritos`
- **Quando** ele acessa `enrollments.showAll`
- **Então** o acesso é negado

---

## 8. Cenários — Seleção de Monitores (`Selection`)

### CEN-SELECTION-001 — Index exige período aberto e permissão Selecionar monitor
- **Dado** que não existe período aberto
- **Quando** um usuário com `Selecionar monitor` acessa `selections.index`
- **Então** o acesso é bloqueado (período aberto exigido)
- **Dado** um usuário sem a permissão `Selecionar monitor`
- **Quando** ele acessa `selections.index` com período aberto
- **Então** o acesso é negado

### CEN-SELECTION-002 — Index da Secretaria/Admin/Presidente lista solicitações de todas as turmas do período aberto
- **Dado** solicitações de turmas em múltiplos departamentos no período aberto
- **Quando** `Secretaria`, `Administrador` ou `Presidente` acessa `selections.index`
- **Então** todas as solicitações das turmas do período aberto são listadas
- **E** a listagem é ordenada por departamento

### CEN-SELECTION-003 — Index do Membro Comissão lista apenas o próprio departamento
- **Dado** solicitações nos departamentos MAC (do membro) e MAT
- **Quando** o `Membro Comissão` acessa `selections.index`
- **Então** apenas as solicitações do departamento MAC são listadas

### CEN-SELECTION-004 — Store com inscrição já eleita deseleciona a seleção anterior
- **Dado** uma inscrição que já possui `Selection` (de um processo de seleção anterior)
- **Quando** `selections.store` é chamado com `enrollment_id` dessa inscrição
- **Então** a seleção anterior é silenciosamente removida
- **E** as frequências, autoavaliação e avaliação de docente associadas são excluídas
- **E** uma nova seleção é criada com `sitatl="Ativo"` e `codpescad` = codpes do usuário atuante

### CEN-SELECTION-005 — Store preenche student, school_class e requisition corretamente
- **Dado** uma inscrição válida de um aluno na turma com solicitação
- **Quando** `selections.store` é executado com sucesso
- **Então** a seleção criada liga `student_id`, `school_class_id` e `requisition_id` provindos da inscrição/solicitação

### CEN-SELECTION-006 — Store repopula o curso do aluno no período [N/D]
- **Dado** um aluno com inscrição e período de seleção
- **Quando** `selections.store` é executado
- **Então** `Course` do aluno para o período é repopulado/sincronizado a partir do Replicado (`Course::getCourseFromReplicado`)

### CEN-SELECTION-007 — Bloqueio: aluno já eleito em outra turma do período aberto
- **Dado** que o aluno A já é selecionado (eleito) na turma T2 do período aberto
- **Quando** `selections.store` tenta elegê-lo na turma T1 (turma diferente)
- **Então** a operação é bloqueada com aviso
- **E** nenhuma nova seleção é criada

### CEN-SELECTION-008 — Membro Comissão só seleciona no próprio departamento
- **Dado** uma inscrição em uma turma do departamento MAT e um `Membro Comissão` do departamento MAC
- **Quando** o membro tenta `selections.store` para essa inscrição
- **Então** o acesso é negado (guarda de departamento)
- **E** quando a turma é do próprio departamento, a seleção é criada normalmente

### CEN-SELECTION-009 — Store cria frequências para os meses ativos do período
- **Dado** a criação de uma seleção no período 2026/1° Semestre
- **Quando** `selections.store` é executado
- **Então** `Frequency::createFromSelection` cria frequências para os meses 3, 4, 5, 6 (`registered=false`)
- **Dado** a criação de uma seleção no período 2026/2° Semestre
- **Quando** `selections.store` é executado
- **Então** as frequências são criadas para os meses 8, 9, 10, 11

### CEN-SELECTION-010 — Store usa firstOrCreate
- **Dado** a chamada a `selections.store` para uma inscrição sem seleção prévia
- **Quando** a operação é executada
- **Então** `Selection::firstOrCreate` cria exatamente uma seleção (sem duplicidade)

### CEN-SELECTION-011 — Destroy (Preterir) bloqueado com frequência registrada
- **Dado** uma seleção com pelo menos uma `Frequency` com `registered=true`
- **Quando** um usuário com `Preterir monitor` acessa `selections.destroy`
- **Então** a operação é bloqueada com aviso para registrar presença/desligar através do menu Monitores
- **E** a seleção permanece existente

### CEN-SELECTION-012 — Destroy sem frequências registradas exclui seleção e frequências
- **Dado** uma seleção cujas frequências são todas `registered=false`
- **Quando** um usuário com `Preterir monitor` acessa `selections.destroy`
- **Então** as frequências associadas são excluídas
- **E** a seleção é excluída

### CEN-SELECTION-013 — Destroy do Membro Comissão restrito ao departamento
- **Dado** uma seleção em turma de departamento diferente do membro da comissão
- **Quando** o `Membro Comissão` tenta destruir essa seleção
- **Então** o acesso é negado (guarda de departamento)

### CEN-SELECTION-014 — enrollments lista inscrições com ordenação especial
- **Dado** uma turma com inscrições: aluno já selecionado no período aberto, aluno recomendado, demais alunos
- **Quando** um usuário com `Selecionar monitor` acessa `selections.enrollments` da turma
- **Então** a listagem apresenta: já selecionados por último, alunos com seleção no período aberto primeiro, e alunos recomendados primeiro (ordem original após a inversão documentada)
- **E** alunos sem seleção/recomendação aparecem no meio

### CEN-SELECTION-015 — selectUnenrolled cria seleção sem inscrição prévia
- **Dado** um aluno válido com histórico escolar no período aberto, sem inscrição na turma e sem outra seleção no período
- **Quando** `selections.selectunenrolled` é chamado com `school_class_id` e `codpes`
- **Então** uma `Enrollment` é criada com valores padrão e observação informando a eleição sem inscrição
- **E** uma `Selection` com `sitatl="Ativo"` é criada
- **E** as `Frequency` dos meses ativos são criadas

### CEN-SELECTION-016 — selectUnenrolled bloqueia aluno já inscrito na turma
- **Dado** um aluno já inscrito na turma alvo
- **Quando** `selections.selectunenrolled` é chamado para esse aluno/turma
- **Então** a validação falha

### CEN-SELECTION-017 — selectUnenrolled bloqueia aluno já selecionado em outra turma do período aberto
- **Dado** um aluno já selecionado em outra turma do período aberto
- **Quando** `selections.selectunenrolled` é chamado
- **Então** a validação falha

### CEN-SELECTION-018 — selectUnenrolled bloqueia aluno sem histórico escolar no período
- **Dado** um aluno sem `SchoolRecord` no período aberto
- **Quando** `selections.selectunenrolled` é chamado
- **Então** a validação falha

---

## 9. Cenários — Frequência (`Frequency`)

### CEN-FREQUENCY-001 — Index lista seleções Ativo do docente autenticado
- **Dado** seleções `Ativo` de turmas do docente autenticado e seleções de outros docentes no período aberto/mais recente
- **Quando** o `Docente` acessa `frequencies.index`
- **Então** apenas as seleções `Ativo` onde o instrutor da solicitação é o usuário autenticado são listadas
- **E** a listagem é ordenada por nome do aluno

### CEN-FREQUENCY-002 — Index exige papel Docente
- **Dado** um usuário sem o papel `Docente`
- **Quando** ele acessa `frequencies.index`
- **Então** o acesso é negado

### CEN-FREQUENCY-003 — Show via URL assinada válida
- **Dado** uma frequência de um tutor numa turma e uma URL assinada válida para `frequencies.show`
- **Quando** qualquer usuário (inclusive não autenticado) acessa a URL assinada
- **Então** os registros de frequência mensal do tutor na turma são exibidos

### CEN-FREQUENCY-004 — Show via docente autenticado instrutor da turma
- **Dado** um `Docente` autenticado que é instrutor da turma e o tutor pertence à turma
- **Quando** ele acessa `frequencies.show`
- **Então** a página é exibida
- **Dado** um `Docente` autenticado que não é instrutor da turma
- **Quando** ele acessa `frequencies.show` sem URL assinada
- **Então** o acesso é negado

### CEN-FREQUENCY-005 — Show negado para tutor que não pertence à turma
- **Dado** uma URL assinada válida, porém o tutor da rota não pertence à turma da rota
- **Quando** o acesso é feito
- **Então** o acesso é negado

### CEN-FREQUENCY-006 — Update alterna registered e exige seleção Ativo
- **Dado** uma frequência cuja seleção possui `sitatl="Ativo"` no mês liberado
- **Quando** um usuário autorizado (URL assinada ou instrutor autenticado da turma) acessa `frequencies.update`
- **Então** o flag `registered` é alternado (false→true ou true→false)

### CEN-FREQUENCY-007 — Update bloqueado quando seleção não é Ativo
- **Dado** uma frequência cuja seleção possui `sitatl != "Ativo"` (ex.: `Desligado` ou `Concluido`)
- **Quando** `frequencies.update` é chamado
- **Então** a operação é bloqueada

### CEN-FREQUENCY-008 — Update bloqueado para mês futuro
- **Dado** a data atual com mês M e uma frequência de mês M+1
- **Quando** `frequencies.update` é chamado para a frequência do mês futuro
- **Então** a operação é bloqueada (mês futuro não permitido)

### CEN-FREQUENCY-009 — Update bloqueado antes do dia 20 do mês
- **Dado** a data atual = dia 15 do mês M e uma frequência do mês M
- **Quando** `frequencies.update` é chamado
- **Então** a operação é bloqueada (janela só libera a partir do dia 20)

### CEN-FREQUENCY-010 — Update liberado a partir do dia 20
- **Dado** a data atual = dia 20 do mês M e uma frequência do mês M
- **Quando** `frequencies.update` é chamado
- **Então** a operação é executada e `registered` é alternado

### CEN-FREQUENCY-011 — Update requer autorização (URL assinada OU instrutor da turma)
- **Dado** um requester sem URL assinada e sem vínculo de instrutor com a turma
- **Quando** `frequencies.update` é chamado
- **Então** o acesso é negado

---

## 10. Cenários — Monitores (`Tutor`)

### CEN-TUTOR-001 — Index por papel: Admin/Secretaria/Presidente veem todas as seleções
- **Dado** seleções em múltiplos departamentos e períodos
- **Quando** `Admin`, `Secretaria` ou `Presidente` acessa `tutors.index`
- **Então** todas as seleções do período são listadas

### CEN-TUTOR-002 — Index por papel: Membro Comissão vê apenas o departamento
- **Dado** seleções nos departamentos MAC (do membro) e MAT
- **Quando** o `Membro Comissão` acessa `tutors.index`
- **Então** apenas as seleções do próprio departamento são listadas

### CEN-TUTOR-003 — Index por papel: Docente vê seleções de suas solicitações
- **Dado** seleções de turmas do docente e de outros docentes
- **Quando** o `Docente` acessa `tutors.index`
- **Então** apenas as seleções das solicitações do próprio docente são listadas

### CEN-TUTOR-004 — Index com papel sem acesso retorna 403
- **Dado** um usuário logado com papel não coberto (ex.: `Aluno`)
- **Quando** ele acessa `tutors.index`
- **Então** a resposta é 403

### CEN-TUTOR-005 — Revoke exige Secretaria/Admin
- **Dado** um usuário sem os papéis `Secretaria`/`Admin`
- **Quando** ele acessa `tutors.revoke`
- **Então** o acesso é negado

### CEN-TUTOR-006 — Revoke bloqueia seleção não Ativo
- **Dado** uma seleção com `sitatl != "Ativo"` (ex.: `Concluido`)
- **Quando** `Secretaria`/`Admin` chama `tutors.revoke`
- **Então** a operação é bloqueada

### CEN-TUTOR-007 — Revoke exclui frequências futuras não registradas
- **Dado** uma seleção `Ativo` com frequências: mês atual não registrado, meses futuros não registrados, e um mês passado registrado
- **Quando** `tutors.revoke` é executado
- **Então** as frequências não registradas com mês >= mês atual são excluídas
- **E** a frequência do mês passado registrada (e de meses passados) permanece

### CEN-TUTOR-008 — Revoke define Desligado com motivo e data de fim
- **Dado** uma seleção `Ativo` válida e um `motdes` informado no request validado
- **Quando** `tutors.revoke` é executado
- **Então** `sitatl` passa a `Desligado`
- **E** `motdes` é preenchido com o motivo do request
- **E** `dtafimvin` é definido como a data de hoje

### CEN-TUTOR-009 — TurnIntoVolunteer exige Secretaria/Admin e seleção Ativo
- **Dado** uma seleção com `sitatl="Ativo"` e inscrição `voluntario=false`
- **Quando** `Secretaria`/`Admin` chama `tutors.turnintovolunteer`
- **Então** o flag `voluntario` da inscrição vinculada passa a `true`
- **Dado** uma seleção com `sitatl != "Ativo"`
- **Quando** `tutors.turnintovolunteer` é chamado
- **Então** a operação é bloqueada

### CEN-TUTOR-010 — TurnIntoNonVolunteer reverte o flag
- **Dado** uma seleção `Ativo` com inscrição `voluntario=true`
- **Quando** `Secretaria`/`Admin` chama `tutors.turnintononvolunteer`
- **Então** o flag `voluntario` da inscrição vinculada passa a `false`

### CEN-TUTOR-011 — Alternância não altera outras inscrições
- **Dado** um aluno com inscrição I1 (na turma selecionada) e I2 (em outra turma) 
- **Quando** `tutors.turnintovolunteer` altera a inscrição vinculada à seleção
- **Então** apenas a inscrição vinculada é alterada; I2 permanece inalterada

---

## 11. Cenários — Atestados (`Certificate`)

### CEN-CERTIFICATE-001 — Index da Secretaria/Admin lista todas as seleções não Desligado
- **Dado** seleções com `sitatl` em `Ativo`, `Concluido` e `Desligado` no período
- **Quando** `Secretaria` ou `Admin` acessa `certificates.index`
- **Então** apenas as seleções não `Desligado` do período são listadas

### CEN-CERTIFICATE-002 — Index do Aluno lista apenas as próprias seleções
- **Dado** um `Aluno`/monitor com seleções próprias (não `Desligado`) e seleções de outros alunos
- **Quando** ele acessa `certificates.index`
- **Então** apenas as suas seleções são listadas
- **E** a ordenação é da mais recente para a mais antiga

### CEN-CERTIFICATE-003 — Index exige papel Aluno ou histórico de seleção
- **Dado** um usuário logado sem papel `Aluno`, `Secretaria` ou `Admin`, e sem nunca ter tido seleção
- **Quando** ele acessa `certificates.index`
- **Então** o acesso é negado

### CEN-CERTIFICATE-004 — Index vazio exibe aviso de nenhuma monitoria
- **Dado** um `Aluno` sem nenhuma seleção não `Desligado`
- **Quando** ele acessa `certificates.index`
- **Então** é exibido o aviso "você não realizou nenhuma monitoria"

### CEN-CERTIFICATE-005 — Make verifica propriedade da seleção
- **Dado** uma seleção de outro aluno e um usuário `Aluno` não relacionado
- **Quando** ele acessa `certificates.make/{selection}`
- **Então** o acesso é negado (a menos que seja Secretaria/Admin)

### CEN-CERTIFICATE-006 — Make para seleção Concluido renderiza atestado concluído [INTEGRAÇÃO]
- **Dado** uma seleção com `sitatl="Concluido"` legítima
- **Quando** o usuário autorizado acessa `certificates.make`
- **Então** o template LaTeX `certificates.completed` é renderizado
- **E** o arquivo `atestado.pdf` é baixado

### CEN-CERTIFICATE-007 — Make para seleção Ativo renderiza atestado em andamento [INTEGRAÇÃO]
- **Dado** uma seleção com `sitatl="Ativo"` legítima
- **Quando** o usuário autorizado acessa `certificates.make`
- **Então** o template LaTeX `certificates.ongoing` é renderizado
- **E** o arquivo `atestado.pdf` é baixado

---

## 12. Cenários — Autoavaliação (`SelfEvaluation`)

### CEN-SELFEVAL-001 — Index exige permissão Visualizar auto avaliações
- **Dado** um usuário sem a permissão `Visualizar auto avaliações`
- **Quando** ele acessa `selfevaluations.index`
- **Então** o acesso é negado

### CEN-SELFEVAL-002 — Index lista autoavaliações do período
- **Dado** autoavaliações registradas referentes a seleções de um ano+período
- **Quando** um usuário com a permissão acessa `selfevaluations.index`
- **Então** as autoavaliações do período são listadas

### CEN-SELFEVAL-003 — studentIndex lista seleções do próprio aluno
- **Dado** um `Aluno` com seleções próprias (não `Desligado`) e seleções de outros
- **Quando** ele acessa `selfevaluations.studentIndex`
- **Então** apenas as suas seleções não `Desligado` são listadas
- **E** a ordenação é da mais recente para a mais antiga

### CEN-SELFEVAL-004 — studentIndex exige papel Aluno
- **Dado** um usuário autenticado sem papel `Aluno`
- **Quando** ele acessa `selfevaluations.studentIndex`
- **Então** o acesso é negado

### CEN-SELFEVAL-005 — Create fora da janela de avaliação bloqueia
- **Dado** uma seleção cujo período possui `isInEvaluationPeriod() == false` (fora da janela de avaliação)
- **Quando** `selfevaluations.create` é acessado (via URL assinada ou aluno autenticado)
- **Então** o acesso é bloqueado

### CEN-SELFEVAL-006 — Create na janela de avaliação por aluno dono
- **Dado** uma seleção pertencente ao aluno autenticado e o período na janela de avaliação
- **Quando** ele acessa `selfevaluations.create`
- **Então** a página de criação é exibida

### CEN-SELFEVAL-007 — Create via URL assinada para dono ou com seleção existente
- **Dado** uma seleção existente e uma URL assinada válida
- **Quando** `selfevaluations.create` é acessado por um usuário não autenticado
- **Então** a página é exibida (fluxo do link assinado)
- **Dado** uma seleção inexistente (ou URL inválida)
- **Quando** `selfevaluations.create` é acessado
- **Então** o acesso é bloqueado

### CEN-SELFEVAL-008 — Create por aluno cuja seleção não é sua (autenticado) é negado
- **Dado** um aluno autenticado tentando avaliar uma seleção de outro aluno
- **Quando** ele acessa `selfevaluations.create`
- **Então** o acesso é negado

### CEN-SELFEVAL-009 — Store por aluno dono cria/atualiza a autoavaliação
- **Dado** um aluno autenticado dono da seleção na janela de avaliação
- **Quando** ele envia `selfevaluations.store` com `student_amount`, `homework_amount`, `workload` e campos opcionais
- **Então** `updateOrCreate(['selection_id'])` persiste a autoavaliação
- **E** um segundo envio atualiza o mesmo registro (uma avaliação por seleção)

### CEN-SELFEVAL-010 — Store via link assinado valida hash do JSON da seleção
- **Dado** um payload com `selection_id` e `selection_hash`
- **Quando** o fluxo do link assinado executa `selfevaluations.store` sem autenticação
- **Então** `Hash::check(json_encode(selection->toArray()), selection_hash)` é verificado
- **E** com hash válido a avaliação é persistida
- **E** com hash inválido a operação é negada

### CEN-SELFEVAL-011 — Store exige seleção pertencer ao usuário autenticado
- **Dado** um aluno autenticado tentando avaliar a seleção de outro aluno
- **Quando** ele envia `selfevaluations.store`
- **Então** a operação é negada

### CEN-SELFEVAL-012 — Validação do store
- **Dado** o endpoint `selfevaluations.store`
- **Quando** enviados payloads inválidos: ausência de `selection_id`/`selection_hash`, `student_amount`/`homework_amount`/`workload` não inteiros
- **Então** a validação falha
- **Quando** `secondary_activity`, `workload_reason` e `comments` são enviados como texto
- **Então** são aceitos como opcionais

### CEN-SELFEVAL-013 — Show permite aluno dono ou quem tem a permissão
- **Dado** uma autoavaliação de um aluno
- **Quando** o próprio aluno ou um usuário com `Visualizar auto avaliações` acessa `selfevaluations.show`
- **Então** a avaliação é exibida
- **Quando** um terceiro sem permissão acessa
- **Então** o acesso é negado

### CEN-SELFEVAL-014 — Edit/Update restrito ao aluno dono
- **Dado** uma autoavaliação própria dentro da janela de avaliação
- **Quando** o aluno dono acessa `selfevaluations.edit` e envia `update`
- **Então** a avaliação é atualizada
- **Dado** um usuário que não é o dono
- **Quando** ele tenta editar/atualizar
- **Então** o acesso é negado

### CEN-SELFEVAL-015 — Destroy é stub vazio
- **Dado** `selfevaluations.destroy`
- **Quando** é invocado
- **Então** nenhuma alteração é feita (stub vazio)

---

## 13. Cenários — Avaliação do Docente (`InstructorEvaluation`)

### CEN-INSEVAL-001 — Index exige permissão Visualizar avaliações dos docentes
- **Dado** um usuário sem a permissão `Visualizar avaliações dos docentes`
- **Quando** ele acessa `instructorevaluations.index`
- **Então** o acesso é negado

### CEN-INSEVAL-002 — instructorIndex lista seleções do docente autenticado
- **Dado** um `Docente` que é instrutor da solicitação de algumas seleções (não `Desligado`) e seleções de outros docentes
- **Quando** ele acessa `instructorevaluations.instructorIndex`
- **Então** apenas as seleções não `Desligado` cujo instrutor da solicitação é ele são listadas

### CEN-INSEVAL-003 — Create por URL assinada ou docente instrutor
- **Dado** uma seleção cuja solicitação tem o docente autenticado como instrutor, na janela de avaliação
- **Quando** ele acessa `instructorevaluations.create`
- **Então** a página de criação é exibida
- **Dado** uma URL assinada válida para a mesma seleção
- **Quando** um usuário não autenticado acessa
- **Então** a página é exibida

### CEN-INSEVAL-004 — Create fora da janela de avaliação bloqueia
- **Dado** uma seleção cujo período está fora da janela de avaliação
- **Quando** `instructorevaluations.create` é acessado
- **Então** o acesso é bloqueado

### CEN-INSEVAL-005 — Create por docente não instrutor é negado
- **Dado** um docente que não é o instrutor da solicitação da seleção
- **Quando** ele acessa `instructorevaluations.create`
- **Então** o acesso é negado

### CEN-INSEVAL-006 — Store persiste avaliação via updateOrCreate
- **Dado** um docente instrutor da solicitação na janela de avaliação
- **Quando** ele envia `instructorevaluations.store` com `ease_of_contact`, `efficiency`, `reliability`, `overall` e `comments`
- **Então** a avaliação é criada (ou atualizada em novo envio) por `updateOrCreate(['selection_id'])`

### CEN-INSEVAL-007 — Store por link assinado valida hash
- **Dado** o fluxo de link assinado sem autenticação
- **Quando** `instructorevaluations.store` é executado
- **Então** é verificado o hash sobre o JSON da seleção (`Hash::check`)
- **E** com hash válido a avaliação é persistida; inválido nega

### CEN-INSEVAL-008 — Validação do store
- **Dado** o endpoint `instructorevaluations.store`
- **Quando** `ease_of_contact`/`efficiency`/`reliability`/`overall` estão fora de `0,1,2`
- **Então** a validação falha
- **Quando** `comments` excede 65536 caracteres (store)
- **Então** a validação falha
- **Quando** na atualização `comments` excede 512 caracteres
- **Então** a validação falha

### CEN-INSEVAL-009 — Show restringe ao instrutor dono ou à permissão
- **Dado** uma avaliação de docente
- **Quando** o instrutor da solicitação ou um usuário com a permissão acessa `instructorevaluations.show`
- **Então** a avaliação é exibida
- **Quando** outro usuário sem permissão acessa
- **Então** o acesso é negado

### CEN-INSEVAL-010 — Edit/Update restrito ao instrutor dono
- **Dado** uma avaliação própria (docente instrutor da solicitação)
- **Quando** ele acessa `edit` e envia `update`
- **Então** a avaliação é atualizada
- **Dado** um docente que não é o instrutor da solicitação
- **Quando** ele tenta editar/atualizar
- **Então** o acesso é negado

---

## 14. Cenários — Modelos de E-mail (`MailTemplate`)

### CEN-MAILTEMPLATE-001 — CRUD exige permissão Editar E-mails
- **Dado** um usuário sem a permissão `Editar E-mails`
- **Quando** ele acessa `index`, `create`, `store`, `edit`, `update` ou `destroy` de `mailtemplates`
- **Então** o acesso é negado

### CEN-MAILTEMPLATE-002 — Store decodifica o campo combinado description_and_mail_class
- **Dado** um payload com `description_and_mail_class` contendo JSON válido `{"description": "...", "mail_class": "..."}`
- **Quando** `mailtemplates.store` é executado
- **Então** `description` e `mail_class` são derivados do JSON e persistidos

### CEN-MAILTEMPLATE-003 — Store rejeita nome duplicado
- **Dado** um `MailTemplate` existente com `name="Template A"`
- **Quando** `mailtemplates.store` é chamado com `name="Template A"`
- **Então** a validação falha (nome duplicado rejeitado)

### CEN-MAILTEMPLATE-004 — Validação de campos do template
- **Dado** o endpoint `mailtemplates.store` com permissão
- **Quando** enviados payloads com `name` ausente, `subject` > 256, `body` > 8192, ou `sending_date`/`sending_hour` ausentes para frequência diferente de `Manual`
- **Então** a validação falha nos casos correspondentes

### CEN-MAILTEMPLATE-005 — Update rejeita nome duplicado excluindo a si mesmo
- **Dado** o template A com `name="Template A"` e o template B com `name="Template B"`
- **Quando** o template A é atualizado para `name="Template B"`
- **Então** a validação falha (duplicado)
- **Quando** o template A é atualizado mantendo seu próprio `name="Template A"`
- **Então** a validação passa

### CEN-MAILTEMPLATE-006 — Update rejeita ativar outro modelo Manual ativo da mesma mail_class
- **Dado** um template T1 ativo com `sending_frequency="Manual"` e `mail_class X`
- **Quando** o usuário tenta ativar (via update) o template T2 com `sending_frequency="Manual"` e `mail_class X`
- **Então** a operação é rejeitada

### CEN-MAILTEMPLATE-007 — Frequência Manual limpa sending_date e sending_hour
- **Dado** um template com `sending_date`/`sending_hour` preenchidos
- **Quando** `mailtemplates.update` é chamado com `sending_frequency="Manual"`
- **Então** `sending_date` e `sending_hour` são limpos (null)

### CEN-MAILTEMPLATE-008 — Activate bloqueia dois modelos Manual ativos da mesma mail_class
- **Dado** um modelo T1 ativo com `sending_frequency="Manual"` e `mail_class X`
- **Quando** `mailtemplates.activate` é chamado para T2 (também `Manual`, `mail_class X`)
- **Então** a operação é bloqueada
- **E** T2 permanece `active=false`

### CEN-MAILTEMPLATE-009 — Activate define active=true em condições válidas
- **Dado** um template T2 inativo com `sending_frequency="Manual"` e `mail_class Y` (sem outro Manual ativo em Y)
- **Quando** `mailtemplates.activate` é chamado
- **Então** `active=true`

### CEN-MAILTEMPLATE-010 — Deactivate define active=false
- **Dado** um template ativo
- **Quando** `mailtemplates.deactivate` é chamado
- **Então** `active=false`

### CEN-MAILTEMPLATE-011 — Test envia e-mail de exemplo com registro real
- **Dado** um modelo ativo e a permissão `Disparar emails`
- **Quando** `mailtemplates.test` é chamado com um endereço e a `mail_class` possui registro real (ex.: uma frequência com seleção `Ativo`, ou uma seleção recente)
- **Então** um e-mail de exemplo é enviado para o endereço informado

### CEN-MAILTEMPLATE-012 — Test sem registros reais falha graciosamente
- **Dado** uma `mail_class` sem nenhum registro real aplicável
- **Quando** `mailtemplates.test` é chamado
- **Então** a operação falha graciosamente (sem exceção para o usuário)

### CEN-MAILTEMPLATE-013 — Destroy exclui o modelo
- **Dado** um template existente
- **Quando** `mailtemplates.destroy` é executado
- **Então** o modelo é excluído do banco

---

## 15. Cenários — Disparo de E-mails (`EmailController`)

### CEN-EMAIL-001 — Áreas do email controller exigem permissão Disparar emails
- **Dado** um usuário sem a permissão `Disparar emails`
- **Quando** ele acessa `index`, `indexSelections`, `indexAttendanceRecords`, `indexSelfEvaluations`, `indexInstructorEvaluations` e os `trigger*`
- **Então** o acesso é negado

### CEN-EMAIL-002 — indexSelections lista turmas com seleções do período
- **Dado** turmas com seleções e turmas sem seleções no período
- **Quando** um usuário com `Disparar emails` acessa `emails.indexSelections`
- **Então** apenas as turmas com seleções são listadas para escolha de notificação

### CEN-EMAIL-003 — indexAttendanceRecords calcula meses válidos por período
- **Dado** um período 1° Semestre
- **Quando** `emails.indexAttendanceRecords` é acessado
- **Então** os meses válidos são `3,4,5,6`
- **Dado** um período 2° Semestre
- **Quando** o acesso é feito
- **Então** os meses válidos são `8,9,10,11`

### CEN-EMAIL-004 — indexAttendanceRecords valida o mês informado
- **Dado** o parâmetro `month` fora dos meses válidos do período
- **Quando** `emails.indexAttendanceRecords` é acessado
- **Então** o mês é rejeitado com erro

### CEN-EMAIL-005 — indexAttendanceRecords deriva o mês do atual quando não informado
- **Dado** a data atual e nenhum parâmetro `month`
- **Quando** `emails.indexAttendanceRecords` é acessado
- **Então** o mês é derivado do mês atual (apenas se dia >= 20), com clamp dentro dos limites do período

### CEN-EMAIL-006 — indexAttendanceRecords lista frequências não registradas do mês de monitores Ativo
- **Dado** frequências do mês M com `registered=false` de seleções `Ativo`
- **Quando** `emails.indexAttendanceRecords` lista o mês M
- **Então** essas frequências são exibidas (apenas de monitores `Ativo`)

### CEN-EMAIL-007 — indexSelfEvaluations lista seleções sem autoavaliação
- **Dado** seleções não `Desligado`, umas com autoavaliação e outras sem
- **Quando** `emails.indexSelfEvaluations` é acessado
- **Então** apenas as seleções não `Desligado` sem autoavaliação são listadas

### CEN-EMAIL-008 — indexInstructorEvaluations lista seleções sem avaliação de docente
- **Dado** seleções não `Desligado`, umas avaliadas pelo docente e outras não
- **Quando** `emails.indexInstructorEvaluations` é acessado
- **Então** apenas as seleções sem avaliação de docente são listadas

### CEN-EMAIL-009 — triggerSelections exige modelo ativo Manual por classe
- **Dado** que não existe modelo ativo Manual para `NotifyInstructorAboutSelectAssistant` ou `NotifySelectStudent`
- **Quando** `emails.triggerSelections` é chamado
- **Então** a operação é bloqueada com aviso

### CEN-EMAIL-010 — triggerSelections envia para instrutor e alunos selecionados por turma
- **Dado** modelos ativos Manual para `NotifyInstructorAboutSelectAssistant` e `NotifySelectStudent`
- **Quando** `emails.triggerSelections` é chamado com turmas selecionadas
- **Então** para cada turma: um e-mail é enviado para o instrutor
- **E** um e-mail é enviado para cada aluno selecionado da turma

### CEN-EMAIL-011 — triggerAttendanceRecords exige modelo ativo Manual
- **Dado** que não existe modelo ativo Manual para `NotifyInstructorAboutAttendanceRecord`
- **Quando** `emails.triggerAttendanceRecords` é chamado
- **Então** a operação é bloqueada com aviso

### CEN-EMAIL-012 — triggerAttendanceRecords envia ao instrutor com URL assinada
- **Dado** um modelo ativo Manual para `NotifyInstructorAboutAttendanceRecord`
- **Quando** `emails.triggerAttendanceRecords` é chamado para frequências do mês
- **Então** para cada frequência é enviado e-mail ao instrutor da turma
- **E** o e-mail contém URL assinada para a página de frequência

### CEN-EMAIL-013 — Mailable de frequência cancela envio se seleção não Ativo
- **Dado** uma frequência cuja seleção não é `Ativo`
- **Quando** `NotifyInstructorAboutAttendanceRecord` é construído/enviado
- **Então** o mailable retorna `null` (envio cancelado)
- **E** um log é registrado

### CEN-EMAIL-014 — triggerSelfEvaluations envia com URL assinada apenas para seleções sem avaliação
- **Dado** um modelo ativo Manual para `NotifyStudentAboutSelfEvaluation`
- **Quando** `emails.triggerSelfEvaluations` é chamado
- **Então** um e-mail é enviado a cada aluno selecionado sem autoavaliação
- **E** o e-mail contém URL assinada para o formulário de autoavaliação

### CEN-EMAIL-015 — triggerInstructorEvaluations envia com URL assinada
- **Dado** um modelo ativo Manual para `NotifyInstructorAboutEvaluation`
- **Quando** `emails.triggerInstructorEvaluations` é chamado
- **Então** um e-mail é enviado a cada instrutor de turma e o e-mail contém URL assinada

### CEN-EMAIL-016 — Payload de triggers valida arrays de IDs
- **Dado** o endpoint de trigger
- **Quando** IDs ausentes ou de formato inválido são enviados
- **Então** a validação falha (arrays de IDs exigidos)

---

## 16. Cenários — Usuários (`User`)

### CEN-USER-001 — Index exige permissão editar usuario
- **Dado** um usuário sem a permissão `editar usuario`
- **Quando** ele acessa `users.index`
- **Então** o acesso é negado

### CEN-USER-002 — Index ordena papéis especiais primeiro
- **Dado** usuários com os papéis `Administrador`, `Secretaria`, `Membro Comissão`, `Presidente de Comissão`, `Vice Presidente de Comissão` e demais usuários
- **Quando** um usuário com `editar usuario` acessa `users.index`
- **Então** os usuários com papéis especiais aparecem primeiro e os demais depois

### CEN-USER-003 — Update desanexa todos os papéis e atribui os selecionados
- **Dado** um usuário com os papéis `Docente` e `Aluno`
- **Quando** `users.update` é executado atribuindo apenas `Secretaria` (e o payload de roles validado)
- **Então** todos os papéis anteriores são desanexados
- **E** apenas `Secretaria` passa a ser atribuído
- **E** os dados do usuário são atualizados

### CEN-USER-004 — Validação do UserRequest
- **Dado** o endpoint `users.update`
- **Quando** um `email` duplicado de outro usuário é enviado sem respeitar o `id` atual
- **Então** a validação falha (regra de unicidade considera o `id` atual na edição)
- **Quando** `roles` está vazio (array sem nenhum papel)
- **Então** a validação falha (`roles` array com min 1)

### CEN-USER-005 — Search filtra por nome, codpes e papéis
- **Dado** usuários com nomes, codpes e papéis variados
- **Quando** um usuário com `editar usuario` busca por nome (like), por `codpes` ou por papel
- **Então** os resultados correspondem ao filtro aplicado

### CEN-USER-006 — Loginas renderiza a view auxiliar
- **Dado** um usuário com `editar usuario`
- **Quando** ele acessa `users.loginas`
- **Então** a view "logar como" é renderizada (UI auxiliar de impersonação)

### CEN-USER-007 — Métodos remanescentes são stubs
- **Dado** o controller `UserController`
- **Quando** são acessados `create/store/show/destroy`
- **Então** nenhuma operação é realizada (stubs vazios)

### CEN-USER-008 — Save sincroniza papéis Aluno/Docente pelos vínculos [N/D]
- **Dado** um usuário com `codpes` que possui vínculos `ALUNOGR`/`ALUNOPOS`/`ALUNOPOSESP` no Replicado
- **Quando** o usuário é salvo
- **Então** o papel `Aluno` é atribuído via `syncVinculoRoles()`
- **Dado** um usuário com vínculo `SERVIDOR` e `tipfnc='Docente'`
- **Quando** o usuário é salvo
- **Então** o papel `Docente` é atribuído
- **Dado** um usuário cujo vínculo de aluno foi removido
- **Quando** ele é salvo
- **Então** o papel `Aluno` é removido (sincronização idempotente)

### CEN-USER-009 — LOG_AS_ADMINISTRATOR concede Administrador automaticamente
- **Dado** a variável de ambiente `LOG_AS_ADMINISTRATOR` contendo o `codpes` do usuário
- **Quando** o usuário é salvo/criado
- **Então** ele recebe o papel `Administrador`
- **Dado** um usuário não listado em `LOG_AS_ADMINISTRATOR`
- **Quando** ele é salvo
- **Então** o papel `Administrador` não é atribuído automaticamente

### CEN-USER-010 — Login cria Student/Instructor correspondente [N/D]
- **Dado** um usuário com papel `Aluno` que não possui registro em `Student`
- **Quando** ele faz login (fluxo `MainController@index`)
- **Então** um `Student` é criado automaticamente a partir do Replicado por `codpes`
- **Dado** um usuário com papel `Docente` sem registro em `Instructor`
- **Quando** ele faz login
- **Então** um `Instructor` é criado automaticamente

---

## 17. Cenários — Relatórios (`Report`)

### CEN-REPORT-001 — Index exige permissão gerar relatorio
- **Dado** um usuário sem a permissão `gerar relatorio`
- **Quando** ele acessa `reports.index`
- **Então** o acesso é negado

### CEN-REPORT-002 — Index lista períodos
- **Dado** períodos existentes
- **Quando** um usuário com `gerar relatorio` acessa `reports.index`
- **Então** os períodos são listados

### CEN-REPORT-003 — Make executa o script de gráficos e gera o PDF [INTEGRAÇÃO]
- **Dado** um período com dados e o script Python `create_graphs.py` disponível
- **Quando** `reports.make` é chamado com `periodoId`
- **Então** o script de gráficos é executado com os parâmetros do período
- **E** `reports.latex` é renderizado via LaraTeX
- **E** o arquivo `relatorio.pdf` é baixado

### CEN-REPORT-004 — Make sem permissão é negado
- **Dado** um usuário sem `gerar relatorio`
- **Quando** `reports.make` é chamado
- **Então** o acesso é negado

### CEN-REPORT-005 — External exige token, ano e período corretos
- **Dado** o parâmetro `token` diferente de `env('EXTERNAL_REPORT_TOKEN')` (ou `ano`/`periodo` ausentes/inválidos)
- **Quando** `reports.external` é acessado
- **Então** a resposta retorna `status` de erro e `message` explicativa (sem PDF)
- **Dado** o `token` correto, `ano` e `periodo` que encontram um período
- **Quando** o endpoint é acessado
- **Então** o script de gráficos é executado
- **E** `reports.latex-external` é renderizado
- **E** a resposta é JSON `{status, message, report: <pdf em base64>}`

### CEN-REPORT-006 — External não usa autenticação de sessão
- **Dado** que `reports.external` não possui middleware de sessão
- **Quando** um cliente sem sessão acessa o endpoint com o token válido
- **Então** a resposta é gerada normalmente

### CEN-REPORT-007 — External com período inexistente retorna erro
- **Dado** `ano`/`periodo` que não correspondem a nenhum período
- **Quando** `reports.external` é acessado com token válido
- **Então** a resposta indica falha (`status` de erro)

---

## 18. Cenários — Importação do Banco Antigo (`OldDB`)

### CEN-OLDDB-001 — Index e Import exigem papel Administrador
- **Dado** um usuário sem o papel `Administrador`
- **Quando** ele acessa `olddb.index` ou `olddb.import`
- **Então** o acesso é negado

### CEN-OLDDB-002 — Validação do arquivo de importação
- **Dado** o endpoint `olddb.import` como Admin
- **Quando** é enviado arquivo com extensão fora de `csv,txt` ou com mais de 1000KB
- **Então** a validação falha

### CEN-OLDDB-003 — Import despacha job com conteúdo e codpes do usuário
- **Dado** um arquivo CSV/TXT válido (separado por `;`, 18 colunas)
- **Quando** `olddb.import` é executado como Admin
- **Então** o job `ProcessImportOldDB` é despachado com o conteúdo bruto + `codpes` do usuário
- **E** o usuário é retornado à página imediatamente (progresso via queue-monitor)

### CEN-OLDDB-004 — Linha válida cria a cadeia completa de registros [INTEGRAÇÃO][N/D]
- **Dado** uma linha CSV com `monitor_codpes`, `professor_codpes`, `coddis`, `ano`, `semestre`, `frequencia_meses`, `voluntario` e demais colunas
- **Quando** `ProcessImportOldDB` processa a linha
- **Então** o `Instructor` é encontrado/criado via Replicado (senão, erro registrado e linha pulada)
- **E** o `Student` é encontrado/criado via Replicado
- **E** um `SchoolTerm` fechado é encontrado/criado
- **E** a turma é obtida via `SchoolClass::getFromReplicadoOldDB`, criada e anexada a instrutores e horários
- **E** uma `Requisition` (`requested_number=1`, `priority=1`) com as 3 atividades padrão é criada
- **E** uma `Enrollment` é criada
- **E** uma `Selection` com `sitatl="Concluido"` é criada
- **E** registros de `Frequency` são criados para cada mês de `frequencia_meses` com `registered=true`
- **E** `SelfEvaluation` e `InstructorEvaluation` são criados apenas quando todos os campos obrigatórios estão presentes

### CEN-OLDDB-005 — Linha com contagem de colunas incorreta é rastreada como erro
- **Dado** uma linha com número de colunas diferente de 18
- **Quando** `ProcessImportOldDB` processa o arquivo
- **Então** a linha é contabilizada como erro
- **E** ao final o job reporta via `queueData(["status"=>"failed","linhas_com_erros"=>"[...]"])`

### CEN-OLDDB-006 — Erros por falta de dados no Replicado são reportados [N/D]
- **Dado** uma linha cujo `monitor_codpes` ou `professor_codpes` não é encontrado no Replicado
- **Quando** o job processa a linha
- **Então** o erro é registrado e a linha é pulada

---

## 19. Cenários — Jobs e Filas em Segundo Plano

### CEN-JOB-001 — ProcessGetSchoolClassesFromReplicado sincroniza turmas [N/D]
- **Dado** `IS_SUPERVISOR_CONFIG=true` e um período
- **Quando** o job `ProcessGetSchoolClassesFromReplicado` executa
- **Então** para cada turma de `getFromReplicadoBySchoolTerm(periodo)`:
  - a turma é find-or-create pela chave `(codtur, coddis)`
  - os instrutores são desanexados e reanexados (`updateOrCreate` por `nompes+codpes`)
  - os horários são desanexados e reanexados
  - a turma é salva
- **E** o progresso é registrado de 0→100 no queue-monitor

### CEN-JOB-002 — Configuração do job de turmas
- **Dado** o job `ProcessGetSchoolClassesFromReplicado`
- **Então** `timeout=3600` e está monitorado pelo `romanzipp`

### CEN-JOB-003 — ProcessImportOldDB é monitorado com timeout alto
- **Dado** o job `ProcessImportOldDB`
- **Então** `timeout=9999` e está monitorado

### CEN-JOB-004 — Progresso é consultável pelo MonitorController
- **Dado** um job monitorado em execução ou concluído
- **Quando** `MonitorController@getImportSchoolClassesJob` / `getImportOldDBJob` é consultado
- **Então** é retornado o registro mais recente (max id; entre idênticos, a linha com max `progress`)

---

## 20. Cenários — Tarefas Agendadas (Kernel)

### CEN-KERNEL-001 — Disparo agendado de e-mails por frequência Única [AGENDADO]
- **Dado** um `MailTemplate` ativo com `sending_frequency="Única"`, `sending_date` e `sending_hour`
- **Quando** `now == sending_date` e `now == sending_hour`
- **Então** o e-mail é disparado pela mail_class correspondente com os filtros de consulta padrão

### CEN-KERNEL-002 — Disparo agendado Mensal [AGENDADO]
- **Dado** um `MailTemplate` ativo com `sending_frequency="Mensal"`
- **Quando** o agendamento `monthlyOn(sending_date, sending_hour)` atingir a data
- **Então** o e-mail é disparado mensalmente

### CEN-KERNEL-003 — Início do período de avaliação [AGENDADO]
- **Dado** um `MailTemplate` ativo com `sending_frequency="Inicio do período de avaliação"` e um período com `start_date_evaluations >= now - sending_date`
- **Quando** `now == start_date_evaluations + sending_date`
- **Então** o e-mail é disparado

### CEN-KERNEL-004 — Final do período de avaliação [AGENDADO]
- **Dado** um `MailTemplate` ativo com `sending_frequency="Final do período de avaliação"` e um período com `end_date_evaluations >= now + sending_date`
- **Quando** `now == end_date_evaluations - sending_date`
- **Então** o e-mail é disparado

### CEN-KERNEL-005 — Fechamento automático do período [AGENDADO]
- **Dado** um período com `finished_at >= now`
- **Quando** `now` atinge `finished_at` às 23:59
- **Então** todas as seleções `Ativo` do período passam a `Concluido`

### CEN-KERNEL-006 — Fechamento não afeta seleções Desligado
- **Dado** um período em fechamento com seleções `Ativo` e `Desligado`
- **Quando** o agendador executa o fechamento
- **Então** apenas as seleções `Ativo` são transformadas em `Concluido`
- **E** as seleções `Desligado` permanecem `Desligado`

### CEN-KERNEL-007 — E-mails de frequência agendados usam URLs assinadas
- **Dado** um disparo agendado de uma mail_class de frequência
- **Quando** o e-mail é montado
- **Então** o contexto inclui URLs assinadas para as rotas de exibição

### CEN-KERNEL-008 — Modelos ativos não-Manual programados
- **Dado** um `MailTemplate` com `active=true` e `sending_frequency="Manual"`
- **Quando** o agendador processa os templates
- **Então** o template Manual não é agendado pelo Kernel (disparo manual apenas)

---

## 21. Cenários — Comandos do Console

### CEN-CMD-001 — report:compare-classes valida ambiente
- **Dado** um ambiente sem a classe do Replicado, sem `env('UNIDADE')` ou sem conexão com o banco
- **Quando** `report:compare-classes` é executado
- **Então** o comando falha (código de saída 1)

### CEN-CMD-002 — report:compare-classes resolve o período
- **Dado** a opção `--schoolterm` informada
- **Quando** o comando é executado
- **Então** o período informado é usado
- **Dado** nenhuma opção `--schoolterm`
- **Quando** o comando é executado
- **Então** o período aberto (ou mais recente) é resolvido

### CEN-CMD-003 — report:compare-classes classifica diferenças
- **Dado** turmas locais e do Replicado para um período [N/D]
- **Quando** o comando compara pela chave `codtur_coddis`
- **Então** as turmas são separadas em apenas-local, apenas-replicado, diferenças-de-professores, idênticos e outras-diferenças
- **E** `instructor_sync_rate` é calculado

### CEN-CMD-004 — report:compare-classes gera os formatos de saída
- **Dado** dados válidos
- **Quando** `report:compare-classes --format=table` executa
- **Então** a tabela é impressa no console
- **Quando** `--format=json` executa
- **Então** o JSON é emitido
- **Quando** `--format=csv --output=caminho` executa
- **Então** o CSV é escrito no caminho

### CEN-CMD-005 — report:compare-classes retorna código de saída
- **Dado** uma execução bem-sucedida
- **Então** o status de saída é 0
- **Dado** uma falha de validação/ambiente
- **Então** o status de saída é 1

### CEN-CMD-006 — sync:class-instructors com dry-run retorna preview
- **Dado** professores presentes no Replicado e ausentes localmente [N/D]
- **Quando** `sync:class-instructors --dry-run` é executado
- **Então** nenhuma alteração é feita no banco
- **E** a saída é JSON `{status:"preview", summary, changes}`

### CEN-CMD-007 — sync:class-instructors aplica mudanças em transação
- **Dado** professores ausentes localmente
- **Quando** `sync:class-instructors` (sem dry-run) é executado
- **Então** cada professor é get-or-create
- **E** a relação com a turma é anexada apenas quando ausente
- **E** a saída é `{status:"completed", summary, results}`
- **E** a operação é commitada em transação

### CEN-CMD-008 — sync:class-instructors é apenas aditivo
- **Dado** professores locais que não existem (ou não estão mais) no Replicado
- **Quando** `sync:class-instructors` é executado
- **Então** nenhum professor é removido

### CEN-CMD-009 — sync:class-instructors com falha faz rollback
- **Dado** uma falha durante a aplicação das mudanças
- **Quando** `sync:class-instructors` é executado
- **Então** a transação é revertida (rollback)

### CEN-CMD-010 — sync:class-instructors com --class limita o escopo
- **Dado** a opção `--class=ID`
- **Quando** o comando é executado
- **Então** apenas a turma informada é sincronizada

---

## 22. Cenários — Integração com o Replicado

### CEN-REPLICADO-001 — Student::getFromReplicadoByCodpes [N/D]
- **Dado** um `codpes` com registros em PESSOA e EMAILPESSOA
- **Quando** a consulta é executada
- **Então** os dados da pessoa (nome, email) são retornados

### CEN-REPLICADO-002 — Student::getFromReplicadoByNompes filtra não-alunos [N/D]
- **Dado** uma busca por nomes incluindo pessoas sem vínculo de aluno
- **Quando** a consulta é executada
- **Então** apenas pessoas com vínculo de aluno aparecem

### CEN-REPLICADO-003 — Instrutor obtido com tipfnc='Docente' [N/D]
- **Dado** um `codpes` com vínculo diferente de docente (ex.: `SERVIDOR` não-docente)
- **Quando** `Instructor::getFromReplicadoByCodpes` é chamado
- **Então** nenhum docente é retornado
- **Dado** um `codpes` com `tipfnc='Docente'`
- **Então** o docente é retornado

### CEN-REPLICADO-004 — Turmas do Replicado por escola/termo [N/D]
- **Dado** um período e a unidade `env('UNIDADE')`
- **Quando** `SchoolClass::getFromReplicadoBySchoolTerm(periodo)` é chamado
- **Então** as turmas com disciplinas, horários e ministrantes do período são retornadas

### CEN-REPLICADO-005 — Determinação de vínculo de aluno no período (graduação/pós) [N/D]
- **Dado** um aluno com vínculos no período
- **Quando** `Student::getVinculoFromReplicadoAtSchoolTerm` é chamado
- **Então** o vínculo (graduação ou pós-graduação) é determinado e usado para repopular `Course`

### CEN-REPLICADO-006 — Estimativa de matrículas por turma [N/D]
- **Dado** uma turma com dados de matrícula no Replicado
- **Quando** `SchoolClass::calcEstimadedEnrollment` é chamado
- **Então** a estimativa de matrícula é calculada

### CEN-REPLICADO-007 — Departments obtidos pelo instituto [N/D]
- **Dado** a unidade `env('UNIDADE')`
- **Quando** `Department::getFromReplicadoByInstitute` é chamado
- **Então** os departamentos da unidade são retornados (também com variantes por `nomabvset`/`codset`)

### CEN-REPLICADO-008 — Course::getCourseFromReplicado [N/D]
- **Dado** um aluno em um período
- **Quando** `Course::getCourseFromReplicado` é chamado
- **Então** o curso (graduação/pós), unidade e sigla são retornados para persistência em `courses`

### CEN-REPLICADO-009 — Vínculos de usuário [N/D]
- **Dado** um `codpes`
- **Quando** `User::getVinculosFromReplicadoByCodpes` é chamado
- **Então** os vínculos ativos são retornados e usados em `syncVinculoRoles()`

---

## 23. Matriz de Rastreabilidade Cenário → Regra

Esta matriz mapeia cada grupo de cenários às seções da [documentação técnica](TECHNICAL_DOCUMENTATION.md).

| Grupo de cenários | Regras-fonte (documentação técnica) |
|---|---|
| `CEN-SCHOOLTERM-*` (001–022) | §3 (períodos, invariante singleton, mutators), §6.1, §14 (validação) |
| `CEN-SCHOOLCLASS-*` (001–016) | §6.2, §4.4 (chave única), §14 |
| `CEN-INSTRUCTOR-*` (001–006) | §6.3, §13 (consulta Replicado com `tipfnc='Docente'`) |
| `CEN-REQUISITION-*` (001–018) | §6.4, §7.7 (guarda de período aberto == solicitação), §14 |
| `CEN-STUDENT-*` / `CEN-SCHOOLRECORD-*` | §6.5, §6.7, §13 (Student), §14 |
| `CEN-ENROLLMENT-*` (001–016) | §6.6, §7.6 (multi-turmas), §7.7 (guarda de desinscrição), §14 |
| `CEN-SELECTION-*` (001–018) | §6.8, §7.2 (fluxo de eleição), §8 (meses ativos), §4.14 |
| `CEN-FREQUENCY-*` (001–011) | §6.9, §7.5 (janela mensal), §11.3 (URLs assinadas) |
| `CEN-TUTOR-*` (001–011) | §6.10, §7.3 (desligamento), §7.4 (voluntário) |
| `CEN-CERTIFICATE-*` (001–007) | §6.11, §12 (LaTeX) |
| `CEN-SELFEVAL-*` (001–015) | §6.13, §11.3 (hash/link assinado), §14 |
| `CEN-INSEVAL-*` (001–010) | §6.14, §11.3, §14 |
| `CEN-MAILTEMPLATE-*` (001–013) | §6.12, §11.1, §14 |
| `CEN-EMAIL-*` (001–016) | §6.15, §11.1–11.3 |
| `CEN-USER-*` (001–010) | §2.3, §6.16, §14 |
| `CEN-REPORT-*` (001–007) | §6.18, §12 |
| `CEN-OLDDB-*` (001–006) | §6.17, §9.2, §14 |
| `CEN-JOB-*` (001–004) | §9.1–9.2, §6.19 |
| `CEN-KERNEL-*` (001–008) | §8 |
| `CEN-CMD-*` (001–010) | §10.1–10.2 |
| `CEN-REPLICADO-*` (001–009) | §13 |

**Totais:** 22 grupos de cenários, **244 cenários** (distribuídos por: 22 Período Letivo, 16 Turma, 6 Docente, 18 Solicitação, 4 Aluno, 7 Histórico, 16 Inscrição, 18 Seleção, 11 Frequência, 11 Monitor, 7 Atestado, 15 Autoavaliação, 10 Aval. Docente, 13 Modelo de E-mail, 16 Disparo de E-mails, 10 Usuário, 7 Relatório, 6 Importação, 4 Jobs, 8 Kernel, 10 Comandos, 9 Replicado).

---

*Fim do documento. Gerado a partir de `docs/TECHNICAL_DOCUMENTATION.md` em 2026-08-21.*