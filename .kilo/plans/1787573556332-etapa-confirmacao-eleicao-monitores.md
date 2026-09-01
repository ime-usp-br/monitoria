# Etapa de confirmação na eleição de monitores

## Contexto

Hoje, ao eleger um monitor (`SelectionController@store`/`@selectUnenrolled`), a seleção já nasce com `sitatl = "Ativo"` e as frequências mensais são criadas na hora. Muitos monitores abandonam a bolsa sem comunicar. A professora quer uma etapa intermediária:

1. A comissão elege → seleção fica **"Selecionado"** (eleito, aguardando confirmação), **sem frequências**.
2. A secretaria dispara **manualmente, em lote por turma**, um e-mail de confirmação com link (mesmo padrão do disparo atual de resultado).
3. O aluno acessa o link, entra com **SenhaÚnica** e clica em **Confirmar** (vira `Ativo`, com frequências) ou **Recusar** (vira `Recusado`).
4. Prazo de **3 dias a partir do disparo** — apenas **informativo** (a UI sinaliza "atrasado"); **não cancela nada**, pois a professora pode ir atrás do aluno.
5. O e-mail final de resultado (comunicação atual, `NotifySelectStudent` + docente) passa a ser disparado **somente para confirmados** (`Ativo`).

## Decisões de design (resolvidas)

- Novo status: `Selecionado` (eleito, aguardando confirmação). Status finais passam a ser: `Selecionado`, `Ativo`, `Recusado`, `Concluido`, `Desligado`.
- "Monitor efetivo" = `Ativo` ou `Concluido`. Todos os filtros que hoje usam `sitatl != "Desligado"` para contagem/listagem de monitores devem mudar para `whereIn(['Ativo','Concluido'])`, exceto telas da comissão que devem continuar exibindo pendentes/recusados com seu status.
- Confirmação: link **assinado** (padrão já usado em autoavaliação/avaliação) + login SenhaÚnica conferindo `student.codpes == Auth::user()->codpes`.
- Prazo: constante `Selection::CONFIRMATION_DEADLINE_DAYS = 3`, contado de `confirm_sent_at` (gravado no disparo em lote). Sem cancelamento automático.
- Recusa: `sitatl = "Recusado"` + `declined_at`. Aluno e vaga ficam **livres** para nova eleição (os guards `hasSelectionInOpenSchoolTerm`/`hasOtherSelectionInOpenSchoolTerm` passam a excluir `Recusado`). A comissão vê quem recusou e pode "Preterir" para remover.
- Frequências criadas **somente na confirmação** (`Frequency::createFromSelection`).

## Tarefas (ordem de execução)

### 1. Migration
Criar `database/migrations/2026_08_24_000000_add_confirmation_columns_to_selections_table.php`:
- `confirm_sent_at` (timestamp, nullable) — disparo do e-mail de confirmação.
- `confirmed_at` (timestamp, nullable).
- `declined_at` (timestamp, nullable).
`down()` remove as colunas.

### 2. Model `Selection` (app/Models/Selection.php)
- Constantes: `SELECTED`, `ACTIVE`, `REFUSED`, `CONCLUDED`, `DISMISSED`; `ACTIVE_STATUSES = ['Ativo','Concluido']`; `CONFIRMATION_DEADLINE_DAYS = 3`.
- Adicionar `confirm_sent_at`, `confirmed_at`, `declined_at` ao `$fillable`.
- Helper `isOverdueConfirmation()`: `sitatl === 'Selecionado' && confirm_sent_at && confirm_sent_at->copy()->addDays(3)->isPast()`.

### 3. `SelectionController` — eleição passa a criar pendente
- `store()`: `$validated['sitatl'] = "Selecionado";` (linha ~93) e **remover** `Frequency::createFromSelection($selecao);` (linhas 117 e 122). Manter o bloco de limpeza da seleção anterior.
- `selectUnenrolled()`: `$validated['sitatl'] = "Selecionado";` (linha ~282) e remover `Frequency::createFromSelection($selecao);` (linha 299).

### 4. Novos endpoints de confirmação/recusa (no `SelectionController`)
- `confirm(Request, Selection)`: acessível por link assinado **ou** aluno autenticado dono. Se `sitatl != 'Selecionado'` → aviso + redirect `/`. Renderiza `selections.confirm`.
- `confirmStore(Request, Selection)`: exige auth + `codpes` do dono; bloqueia se não `Selecionado`; seta `sitatl='Ativo'`, `confirmed_at=now()`, salva e chama `Frequency::createFromSelection()`. Flash de sucesso + redirect `/`.
- `decline(Request, Selection)`: mesma guarda; seta `sitatl='Recusado'`, `declined_at=now()`, salva. Flash + redirect `/`.
- FormRequest `ConfirmSelectionRequest` (validação vazia, espelho do padrão `CreateSelfEvaluationRequest`).

### 5. Rotas (routes/web.php)
```
Route::get('/selections/{selection}/confirm', [SelectionController::class, 'confirm'])->name('selections.confirm');
Route::post('/selections/{selection}/confirm', [SelectionController::class, 'confirmStore'])->name('selections.confirmStore');
Route::post('/selections/{selection}/decline', [SelectionController::class, 'decline'])->name('selections.decline');
Route::get('/emails/selectionConfirmations', [EmailController::class, 'indexSelectionConfirmations'])->name('emails.indexSelectionConfirmations');
Route::post('/emails/triggerSelectionConfirmations', [EmailController::class, 'triggerSelectionConfirmations'])->name('emails.triggerSelectionConfirmations');
```

### 6. Novo Mailable
`app/Mail/NotifyStudentAboutSelectionConfirmation.php` (padrão dos demais: `ShouldQueue`, `afterCommit`). Contexto do Blade: `student`, `schoolclass`, `selection`, `link`. Link = `URL::signedRoute('selections.confirm', ['selection'=>$selection->id])`.

### 7. `EmailController`
- `indexSelectionConfirmations()`: permissão `Disparar emails`; período aberto (fallback mais recente); turmas com pelo menos uma seleção `Selecionado`. View nova destacando pendentes e vencidos (`isOverdueConfirmation()`).
- `triggerSelectionConfirmations(TriggerSelectionConfirmationsRequest)`: permissão; exige modelo ativo Manual para a nova `mail_class`; para cada turma marcada, para cada seleção `Selecionado`: seta `confirm_sent_at = now()`, salva e envia `NotifyStudentAboutSelectionConfirmation` ao aluno. Flash de fila.
- `indexSelections()`: trocar `whereHas('selections')` por `whereHas('selections', fn($q) => $q->where('sitatl','Ativo'))`.
- `triggerSelections()`: pular turma sem nenhuma seleção `Ativo` (não notifica o docente) e, no loop, enviar `NotifySelectStudent` apenas para seleções `sitatl === 'Ativo'`.
- FormRequest `TriggerSelectionConfirmationsRequest` (espelho de `TriggerSelectionsRequest`).

### 8. `MailTemplateController@test`
Adicionar branch para a nova `mail_class`: usar preferencialmente uma seleção `Selecionado` (fallback: `Selection::latest()`), enviando com o link assinado.

### 9. `mailtemplates/partials/form.blade.php`
Incluir no dropdown de aplicação: `"E-mail enviado aos monitores para confirmar a vaga de monitoria" => "NotifyStudentAboutSelectionConfirmation"`.

### 10. Views
- `emails/index.blade.php`: novo item de menu apontando para `emails.indexSelectionConfirmations`.
- Nova `emails/indexSelectionConfirmations.blade.php` (espelho da `indexSelections`): colunas checkbox, coddis, codtur, nomdis, departamento, professor, "Monitor(es) aguardando confirmação" (com badge "prazo vencido" quando `isOverdueConfirmation()`), botão "Disparar e-mails".
- Nova `selections/confirm.blade.php`: dados da vaga (disciplina, turma, professor(a) solicitante, período, situação), botões **Confirmar** (POST `selections.confirmStore`) e **Recusar** (POST `selections.decline`).
- `main.blade.php`: bloco para aluno logado com seleções `Selecionado` ("Vagas de monitoria aguardando sua confirmação") com links para `selections.confirm` — garante acesso mesmo se o link do e-mail se perder no fluxo de login.
- `selections/enrollments.blade.php`: coluna "Eleito" passa a tratar: `Ativo`/`Concluido` → "Sim"; `Selecionado` → "Sim (aguardando confirmação)"; `Recusado` → "Não"; `Desligado` → "Não".
- `selections/index.blade.php` (linha ~49): exibir "(aguardando confirmação)" ao lado do nome quando `sitatl == 'Selecionado'`.
- `tutors/index.blade.php`: "Desligar" e os botões de voluntário ficam **habilitados apenas para `Ativo`** (hoje usam `!= "Desligado"`). Lista passa a exibir o status `Selecionado`/`Recusado` na coluna "Situação" (já exibe `sitatl`).

### 11. Ajuste dos filtros de "monitor efetivo" (`whereIn(['Ativo','Concluido'])`)
- `SchoolTerm::tutors()` — linha ~148.
- `EmailController::indexSelfEvaluations` / `indexInstructorEvaluations` (linhas ~139-167).
- `Console/Kernel::sendEmail()` — branches de autoavaliação e avaliação do docente.
- `SelfEvaluationController::studentIndex` (linha ~69).
- `CertificateController::index` (linhas 50 e 54).
- `resources/views/reports/latex.blade.php` e `latex-external.blade.php`: todas as ocorrências `where("sitatl","!=","Desligado")` → `whereIn("sitatl",["Ativo","Concluido"])`.

### 12. Liberar aluno que recusou
- `Student::getSelectionFromOpenSchoolTerm()`: adicionar `->where('sitatl','!=','Recusado')`.
- `Enrollment::hasOtherSelectionInOpenSchoolTerm()`: idem.

### 13. Fechamento do período (não altera)
O Kernel continua transformando apenas `Ativo` → `Concluido` em `finished_at`. `Selecionado`/`Recusado` permanecem como estão (auditoria na tela de Monitores).

### 14. Template de e-mail / seed
Adicionar no `FictitiousMonitorSeeder` (padrão do `NotifyCertificateRequest`) um `MailTemplate::firstOrCreate` para a nova `mail_class` com `active=false`, frequência `Manual`, e corpo/assunto de exemplo usando `{{ $student->nompes }}`, `{{ $schoolclass->coddis }}`, `{{ $schoolclass->nomdis }}` e `{{ $link }}`. (A secretaria ativa/edita pela tela de Modelos de E-mail como já é feito.)

### 15. Testes
- `tests/Feature/Scenario/SelectionScenarioTest.php`: atualizar `test_cen_selection_004` (frequências e `sitatl`) e `test_cen_selection_005`/outros que esperam `sitatl = 'Ativo'` logo após a eleição → passar a esperar `Selecionado` e **ausência de frequências**.
- `tests/Feature/Scenario/EmailControllerScenarioTest.php`: verificar casos que dependem de `indexSelections`/`triggerSelections` (criados com `createSelection` default `Ativo` continuam válidos; ajustar qualquer caso com pendente).
- Novo `tests/Feature/Scenario/SelectionConfirmationScenarioTest.php`:
  - eleição cria `Selecionado` sem frequências;
  - disparo em lote envia e-mail e grava `confirm_sent_at`;
  - confirmação (aluno dono autenticado) → `Ativo` + frequências criadas;
  - confirmação/recusa por aluno errado → bloqueada;
  - recusa → `Recusado` e aluno liberado (`hasSelectionInOpenSchoolTerm` falso);
  - link assinado inválido → 403;
  - pendente aparece na listagem de disparo e é excluído das listas de avaliação/certificado.

### 16. Documentação
Atualizar `docs/TECHNICAL_DOCUMENTATION.md`: novo status (`Selecionado`/`Recusado`), novos endpoints, novo e-mail (`NotifyStudentAboutSelectionConfirmation`) e o novo fluxo no item 7.1/6.8/6.15.

## Validação
1. `php artisan migrate`.
2. `vendor/bin/phpunit` (ou `php artisan test`) — suíte de cenários completa.
3. Fluxo manual (local): eleger candidato → aparece `Selecionado` sem frequências → disparar e-mail de confirmação por turma → abrir link → login → confirmar → `Ativo` + frequências → repetir com recusa → `Recusado` e liberado para nova eleição → verificar badge de prazo vencido após 3 dias de `confirm_sent_at` → disparar e-mail final de resultado e conferir que só foi aos confirmados.

## Riscos / observações
- Seleções já `Ativo` no banco não são afetadas (sem backfill).
- `SelectionController@store` mantém a limpeza de seleção anterior (incluindo frequências de uma eventual seleção confirmada) — comportamento atual preservado.
- O prazo de 3 dias é apenas indicativo: não há job agendado nem mudança de estado automática.
- Qualquer lugar novo que liste monitorias deve usar `ACTIVE_STATUSES` em vez de `!= "Desligado"`.

## Fora de escopo
- Desligamento (`Desligado`), atestados e fechamento de período permanecem como estão.