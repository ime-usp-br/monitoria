# Relatório de Segurança e Plano de Upgrade

**Projeto:** Sistema de Monitoria (`ime-usp-br/monitoria`)  
**Data da análise:** 2026-11-23  
**Commit analisado:** `ff94254` (`feat(e2e): adiciona infraestrutura e suíte Playwright`)  
**Escopo:** análise de dependências, runtimes, imagens de container, vulnerabilidades conhecidas, achados de segurança no código e plano de upgrade.  
**Alterações realizadas:** nenhuma alteração de código foi feita. Este relatório é o único artefato novo.

---

## 1. Resumo executivo

O projeto está funcional e com a suíte de testes verde, mas roda sobre uma pilha majoritariamente **fora de suporte**:

**Situação inicial (antes do plano):**

- **PHP 7.4.33** (EOL em 2022-11-28)
- **Laravel 8.83.23** (EOL em 2023-01-24; a série 8 não recebe mais correções de segurança)
- **Symfony 5.4** com patches antigos (há correções disponíveis na própria série 5.4)
- **Node.js 16** no build (EOL em 2023-09-11)
- **MariaDB 10.5.21** (produção; EOL em 2025-06-24) — **mantido por decisão de negócio** (banco legado; ver seção 10)
- **MariaDB 10.5.29** no stack E2E/local (mesma linha 10.5; EOL em 2025-06-24; apenas teste/desenvolvimento)
- Imagens `nginx:alpine` e `redis:alpine` sem versão/digest fixos — corrigido: **nginx 1.30.7** e **Redis 8.2** pinados

Resultados da análise inicial:

| Item | Resultado |
|---|---:|
| Testes PHPUnit/Feature | **247 testes, 1.796 asserções, 1 skipped, 0 falhas** (54,7 s) |
| Testes E2E Playwright | **31 testes, 31 passed, 0 falhas** (34,3 s) |
| Avisos `composer audit` | **49 avisos em 13 pacotes**: 1 critical, 15 high, 23 medium, 3 low, 7 sem severidade normalizada |
| Avisos `npm audit` | **32 avisos**: 2 critical, 15 high, 10 moderate, 5 low — todos em dependências de build/dev |
| Avisos `npm audit --omit=dev` | **0** (não há dependências JavaScript de runtime) |
| Pacotes Composer abandonados | 2 (`swiftmailer/swiftmailer`, `php-http/message-factory`) |
| Achados críticos no código | 2 (RCE autenticado via template de e-mail; escalada de privilégio via edição de usuário) |
| Fases de upgrade propostas | **9 fases** (0 a 8), aproximadamente **12 PRs** |
| Esforço estimado | **4 a 6 semanas** com 1 desenvolvedor experiente; 2 a 3 semanas com 2 desenvolvedores |
| Alvo recomendado | **Laravel 13.43.0 + PHP 8.5 + Symfony 8.1 + MariaDB 10.5 legado (exceção) + Node 22 LTS** |
| Mínimo suportado aceitável | **Laravel 12.73.0 + PHP 8.3 + Symfony 7.4 + MariaDB 10.5 legado (exceção)** |

Mensagem principal: **não existe uma versão segura de Laravel 8**. O patch para 8.83.29 corrige apenas uma das falhas conhecidas; a falha crítica `CVE-2026-114509` (bypass de autenticação no TokenGuard) e a `CVE-2025-27515` (bypass de validação de arquivo) não têm correção na série 8. O upgrade de framework é obrigatório, não opcional.

Além das dependências, foram encontrados dois problemas críticos que **não serão corrigidos pelo upgrade** e exigem correção de código: execução remota de código (RCE) por meio de templates de e-mail renderizados com `Blade::render` e escalada de privilégio de Secretaria para Administrador no `UserController@update`.

### 1.1 Status de execução (2026-11-23)

- **Fase 0 concluída:** testes determinísticos (sem skips por data), suíte de regressão de segurança e correções dos achados críticos/altos que não dependiam do upgrade.
- **Fase 1a concluída:** hotfix de dependências dentro do Laravel 8/PHP 7.4. O `composer audit` caiu de **49 avisos em 13 pacotes** para **7 avisos em 2 pacotes** (`laravel/framework` e `league/flysystem` 1.x, ambos sem correção na série atual).
- **Fase 1b (histórico):** Sentry atualizado para 4.31.0; `uspdev/senhaunica-socialite` e `uspdev/replicado` ficaram pinados em 4.3.2/1.13.2 no PHP 7.4 porque o Replicado 1.28.9 usa sintaxe PHP 8 e a Senhaunica 4.5 depende dele.
- **Fase 2 concluída:** Laravel **9.52.23**, PHP **8.2.36**, Symfony **6.4**, Flysystem **3.36.0**, Symfony Mailer, CORS nativo, Sanctum **3.3.3**, Spatie **5.11.1**, Replicado **1.28.9**, Senhaunica **4.5.12**, QueueMonitor **5.3.7**, Staudenmeir **2.12.1/1.17.3**. Dockerfile de produção e E2E migrados para PHP 8.2; Node 22 no build.
- **Fase 3 concluída:** Laravel **10.50.4**, Monolog **3.13.0**, PHPUnit **10.5.67**, Spatie **6.25.0**, QueueMonitor **5.4.5**, Staudenmeir **2.15.1/1.19.5**, Collision **7.12.0** e Ignition **2.9.1**. A `phpunit.xml` foi migrada para o schema do PHPUnit 10 (`<source>`). A critical `CVE-2026-114509` foi corrigida nesta versão.
- **Fase 4 concluída:** Laravel **11.57.1**, PHP **8.3.37**, Symfony **7.4.23**, Carbon **3.15.1**, Sanctum **4.4.0**, Tinker **3.1.0**, PHPUnit **11.5.58**, Collision **8.9.6**, Ignition **2.12.1**, LaraTeX **1.3.2**, Staudenmeir **2.16.4/1.20.7**, Spatie **6.25.0** e QueueMonitor **5.4.5**. Migração estrutural para `bootstrap/app.php`/`bootstrap/providers.php`; `Http\Kernel`, `Console\Kernel`, `Exceptions\Handler` e `RouteServiceProvider` foram removidos; o schedule foi para `app/Console/ScheduledTasks` + `routes/console.php`. Docker migrado para PHP 8.3.
- **Fase 5 concluída:** Laravel **12.73.0**, PHP **8.3.37**, Symfony **7.4.23**, PHPUnit **12.5.45**, Spatie **8.5.0**, Sanctum **4.5.0**, Tinker **3.2.0**, QueueMonitor **5.5.0**, Staudenmeir **2.17/1.21.3** e Socialite **5.33.0**. **Composer audit: 0 avisos de segurança.**
- **Fase 6 concluída:** Laravel **13.43.0**, PHP **8.5.13**, Symfony **8.1.11**, Staudenmeir **2.18/1.22.1**, Ignition **2.13.0**, Sanctum **4.5.0**, Spatie **8.5.0**, QueueMonitor **5.5.0** e PHPUnit **12.5.45**. Docker migrado para PHP 8.5; corrigida a depreciação `PDO::MYSQL_ATTR_SSL_CA` para `Pdo\Mysql::ATTR_SSL_CA`. **Composer audit: 0 avisos.**
- **Fase 7 concluída (MariaDB 10.5 fora de escopo):** nginx atualizado de **1.29.8** (vulnerável a `CVE-2026-123007` major, `CVE-2026-118899` major e `CVE-2026-113425` major) para **1.30.7-alpine** pinado por digest; Redis **8.2-alpine** pinado; `nginx` e `supervisor` removidos da imagem da aplicação. Frontend migrado de Laravel Mix para **Vite 8.3.8 + laravel-vite-plugin 3.2.1**; `axios`, `lodash`, `laravel-mix` e `webpack` removidos; o código de UI que existia apenas no bundle antigo foi recuperado para `resources/js/app-legacy.js`; `parent.blade.php` usa `@vite`; `package-lock.json` versionado e assets em `public/build`. E2E atualizado para pytest **9.2.0** e Playwright **1.65.2** pinado. **MariaDB 10.5 mantido por decisão de negócio** (risco aceito e mitigações na seção 10).
- **Fase 8 (parcial):** SEC-01 corrigido com **Twig 3.31 sandbox** e contexto sanitizado (apenas campos whitelisted; loops/condicionais/métodos documentados suportados via conversão Blade-like). SEC-10 aplicado: `SESSION_ENCRYPT=true`, cookie `Secure` automático em produção e HSTS em produção/HTTPS. `TrustHosts` habilitado com o host de `APP_URL` (localhost/127.0.0.1 apenas fora de produção). SEC-11 aplicado: rotas protegidas agrupadas em `auth`, com whitelist explícita de rotas públicas (home, edital, relatório por token e links assinados) e testes de baseline. SEC-03 restringiu `/students` aos papéis operacionais. SEC-06 passou a usar página de confirmação + PATCH com CSRF. SEC-08 aceita `Authorization: Bearer` e query string na transição, com `hash_equals` e rate limit. CSP em **Report-Only** e rate limit global/auth + login configurados. CI criado em `.github/workflows/ci.yml`; a primeira execução no GitHub detectou o banco de teste sem migrações e foi corrigida com `php artisan migrate --force`. CORS mantém wildcard por decisão.
- **Auditoria atual:** **0 avisos** no `composer audit` e **0 avisos** no `npm audit` completo (não apenas no `--omit=dev`). A árvore vulnerável do Laravel Mix/webpack-dev-server foi eliminada com a migração para Vite.
- **Correções de código aplicadas:** SEC-01 (RCE de template eliminado), SEC-02 (escalada de privilégio), SEC-03 (busca de alunos restrita), SEC-04 (XSS armazenado), SEC-05 (download arbitrário), SEC-06 (confirmação + PATCH), SEC-07 (substring de admin), SEC-08 (header/query + hash_equals + rate limit), SEC-10 (cookies/HSTS), SEC-11 (baseline de autenticação), SEC-14 (IDOR em autoavaliação), TrustHosts, CSP Report-Only, rate limit e CI.
- **Testes após as Fases 6/7/8:** **285 testes PHPUnit 12, 0 falhas, 0 skipped** contra **MariaDB 10.5.29** e **41 testes E2E aprovados** no PHP 8.5 com nginx 1.30.7, build Vite e Twig sandbox.
- **Regressão da UI legada:** 10 testes E2E novos em `e2e/tests/test_ui_legacy.py` cobrem datepicker, modal de horário (válido/inválido), busca e inclusão de docente, busca de aluno por codpes/nome/não encontrado, modal de indicação, DataTable com botões de exportação e modais de remoção/revogação.
- **Pendências:** mover o fork do `laravel-usp-theme` para a organização/tag (SEC-12), remover a query string do token externo após o consumidor migrar (SEC-08), migrar CSP de Report-Only para enforce, rotação de segredos, Trivy/gitleaks, teste de rollback, primeira execução do CI no GitHub e pinagem opcional do digest da imagem Node. CORS wildcard foi mantido por decisão.
- **Decisão de negócio:** o MariaDB 10.5 **não será atualizado** (banco legado). A decisão, os riscos aceitos e as mitigações estão registrados na seção 10; o `config/database.php` já foi preparado para não impedir uma futura migração.
- **Observação:** o Composer 2.10 bloqueia a instalação de qualquer versão do Laravel 8 por advisory de segurança. O hotfix usou `--no-blocking` de forma consciente e documentada; a dívida residual está listada na seção 5.

## 2. Metodologia

Ferramentas e fontes consultadas em 2026-11-23:

- `composer audit` dentro do container `monitoria-app` (PHP 7.4.33, Composer 2.10.2).
- `npm audit` e `npm audit --omit=dev` com Node 22 em container isolado, usando o `package-lock.json` local.
- OSV.dev (`https://api.osv.dev`) para consulta em lote de todas as versões do `composer.lock`, do `package-lock.json` e das dependências Python do E2E.
- Packagist (`repo.packagist.org`) para versões publicadas e restrições de compatibilidade.
- `endoflife.date` para datas de EOL/suporte de PHP, Laravel, Symfony, Node.js, MySQL, MariaDB, nginx, Python e Redis.
- Leitura estática do código em `app/`, `routes/`, `config/`, `resources/views/`, `database/migrations/` e das suítes de teste.
- Execução real dos testes PHPUnit e E2E no ambiente Docker do projeto.

Limitações:

- As consultas refletem o estado das bases de vulnerabilidade em 2026-11-23. Novas CVEs podem surgir; a auditoria deve ser repetida em cada fase.
- A análise de exploitability é baseada no código e na configuração do repositório; configurações de produção (`.env`, proxy, WAF) podem reduzir ou ampliar a exposição.
- Não foi possível medir cobertura de código PHP porque a imagem não possui Xdebug nem PCOV.
- Os testes E2E usam Replicado falso (`E2E_REPLICADO_FAKE=true`) e MariaDB 10.5; portanto não validam a integração real com Sybase/Replicado nem o banco de produção.

---

## 3. Inventário da stack

### 3.1 Runtime e framework

| Componente | Versão atual | Situação | EOL / suporte |
|---|---|---|---|
| PHP | 7.4.33 | **EOL** | 2022-11-28 |
| Laravel Framework | 8.83.23 | **EOL** | 2023-01-24 |
| Symfony (componentes) | 5.4.11 a 5.4.53 | Série 5.4 ainda listada como suportada, mas patches antigos | 2029-02-28 (endoflife.date) |
| Laravel Sanctum | 2.15.1 | Desatualizado | — |
| Spatie Laravel Permission | 4.4.3 | Desatualizado | — |
| Node.js (build) | 16 | **EOL** | 2023-09-11 |
| Composer | 2.10.2 | OK | — |
| MariaDB (produção) | 10.5.21 | **EOL** | 2025-06-24 |
| MariaDB (E2E) | 10.5 | **EOL** | 2025-06-24 |
| nginx | `nginx:alpine` (sem pin) | Versão indeterminada | 1.27 (se for a usada) EOL em 2025-06-24 |
| Redis | `redis:alpine` (sem pin) | Versão indeterminada | — |
| Python (E2E) | 3.12 | Suportado | 2028-10-31 |
| Playwright (E2E) | 1.65.2 (transitiva, não pinada) | OK | — |
| pytest (E2E) | 8.4.2 | **Vulnerável** | corrigido em 9.0.3 |

### 3.2 Bibliotecas PHP relevantes

| Pacote | Versão instalada | Observação |
|---|---|---|
| `laravel/framework` | v8.83.23 | EOL; 7 avisos, incluindo 1 critical |
| `laravel/sanctum` | v2.15.1 | Sem aviso no OSV na versão atual; precisa subir junto com o framework |
| `laravel/socialite` | v5.5.5 | Dependência indireta da senhaunica |
| `laravel/tinker` | v2.7.2 | — |
| `guzzlehttp/guzzle` | 7.5.0 | 9 avisos (1 high, 8 medium) |
| `league/commonmark` | 2.3.5 | 15 avisos (9 high, 6 medium) |
| `league/flysystem` | 1.1.10 | 1 aviso low; **sem correção na série 1.x** |
| `nesbot/carbon` | 2.62.1 | 1 aviso medium |
| `monolog/monolog` | 2.8.0 | Sem aviso; sobe para 3.x no Laravel 10+ |
| `psy/psysh` | v0.11.8 | 1 aviso medium |
| `phpunit/phpunit` | 9.5.24 | 1 aviso high (dev) |
| `sentry/sentry-laravel` | 3.8.2 | Atualizar para 4.31.0 |
| `spatie/laravel-permission` | 4.4.3 | Sem aviso; versão major presa ao Laravel |
| `staudenmeir/belongs-to-through` | v2.11.2 | Última compatível com Laravel 8 |
| `staudenmeir/eloquent-has-many-deep` | v1.14.4 | Última compatível com Laravel 8 |
| `romanzipp/laravel-queue-monitor` | 2.3.0 | Atualizar para 2.3.4 no PHP 7.4 |
| `ismaelw/laratex` | `dev-main` | Dependência de branch, não reprodutível |
| `uspdev/replicado` | 1.13.2 | Manter 1.13.2 no PHP 7.4; **1.28.9 usa sintaxe PHP 8** (`string|null` em `Pessoa.php:153`) e quebra em 7.4. Atualizar na fase 2 (PHP 8.2) |
| `uspdev/senhaunica-socialite` | 4.3.2 | Manter 4.3.2 no PHP 7.4; 4.5.12 exige `Graduacao::obterCursoAtivo()` do Replicado novo e não funciona com 1.13.2. Atualizar junto com Replicado 1.28.9 na fase 2 |
| `uspdev/laravel-usp-theme` | `dev-master` de fork pessoal | **Risco de supply chain**; substituir por versão oficial/tag |
| `fruitcake/laravel-cors` | 2.2.0 | **Abandonado**; remover no Laravel 9+ (CORS nativo) |
| `swiftmailer/swiftmailer` | v6.3.0 | **Abandonado**; Laravel 9+ usa Symfony Mailer |
| `php-http/message-factory` | 1.1.0 | **Abandonado**; substituído por PSR-17 |

### 3.3 Dependências de frontend/build

| Pacote | Versão instalada | Observação |
|---|---|---|
| `vite` | 8.3.8 | Build atual; substitui Laravel Mix/webpack |
| `laravel-vite-plugin` | 3.2.1 | Integração com Laravel; `@vite` no Blade |
| `axios` | removido | Não era usado pelo código de UI; removido na migração |
| `lodash` | removido | Não era usado pelo código de UI; removido na migração |
| `laravel-mix` / `webpack` | removidos | Árvore antiga com avisos de dev server eliminada |
| `postcss` | via Vite | Vite 8 inclui o pipeline de PostCSS |

Observação: `package-lock.json` foi versionado e o build usa `npm ci`. O `npm audit` completo está em **0 avisos**.

### 3.4 Tamanho do código

| Métrica | Valor |
|---|---:|
| Arquivos PHP em `app/` | 138 |
| Controllers | 24 |
| Models | 19 |
| Migrations | 37 |
| Views Blade | 83 |
| Rotas web | 111 linhas |
| Dependências Composer (produção) | 103 |
| Dependências Composer (dev) | 36 |
| Pacotes npm no lock | 753 |

---

## 4. Baseline de testes

### 4.1 PHPUnit / Feature

Comando executado:

```bash
docker exec monitoria-app sh -lc 'cd /var/www && php vendor/bin/phpunit --colors=never'
```

Resultado:

```text
PHPUnit 9.5.24 #StandWithUkraine
247 / 247 (100%)
Time: 00:54.679, Memory: 62.50 MB
OK, but incomplete, skipped, or risky tests!
Tests: 247, Assertions: 1796, Skipped: 1.
```

O teste skipped foi:

```text
Tests\Feature\Scenario\FrequencyScenarioTest::test_cen_frequency_009_update_bloqueado_antes_do_dia_20_do_mes
Requer dia do mês < 20.
```

Esse teste depende da data corrente. Em outros dias do mês, outros testes da mesma classe também são pulados. A suíte não é totalmente determinística em relação ao calendário.

### 4.2 E2E Playwright

Comando executado:

```bash
./scripts/run_e2e.sh
```

Resultado:

```text
31 passed in 34.26s
```

A suíte E2E cobre 12 arquivos/módulos: autenticação, certificados, inscrições, avaliações, frequências, templates de e-mail, relatórios, requisições, períodos letivos, seleções, tutores e usuários.

---

## 5. Vulnerabilidades conhecidas em dependências

### 5.1 Composer (`composer audit`)

Foram encontrados **49 avisos de segurança em 13 pacotes**:

| Pacote | Versão atual | Avisos | Severidades | Versão mínima corrigida no major atual | Versão alvo recomendada |
|---|---:|---:|---|---:|---:|
| `laravel/framework` | 8.83.23 | 7 | 1 critical, 2 high, 2 medium, 2 low | 8.83.28 corrige só 1; **não há correção para a critical na série 8** | 13.43.0 |
| `guzzlehttp/guzzle` | 7.5.0 | 9 | 1 high, 8 medium | 7.15.2 | 7.15.2 |
| `league/commonmark` | 2.3.5 | 15 | 9 high, 6 medium | 2.10.2 (usar 2.10.7) | 2.10.7 |
| `symfony/http-foundation` | 5.4.50 | 4 | 1 high, 3 sem severidade normalizada | 5.4.56 | 7.4 LTS / 8.0 |
| `symfony/http-client` | 5.4.53 | 2 | 2 sem severidade normalizada | 5.4.56 | 7.4 LTS / 8.0 |
| `symfony/http-kernel` | 5.4.12 | 2 | 1 medium, 1 sem severidade normalizada | 5.4.56 | 7.4 LTS / 8.0 |
| `symfony/routing` | 5.4.11 | 3 | 2 medium, 1 sem severidade normalizada | 5.4.56 | 7.4 LTS / 8.0 |
| `symfony/mime` | 5.4.52 | 1 | medium | 5.4.55 | 7.4 LTS / 8.0 |
| `symfony/process` | 5.4.11 | 2 | 1 high, 1 medium | 5.4.54 | 7.4.5+ / 8.0.5+ |
| `league/flysystem` | 1.1.10 | 1 | low | **sem correção na série 1.x**; corrigido em 3.35.3+ | 3.36.0 |
| `nesbot/carbon` | 2.62.1 | 1 | medium | 2.72.6 (usar 2.73.0) | 3.15.1 |
| `psy/psysh` | 0.11.8 | 1 | medium | 0.11.23 | 0.12.24 |
| `phpunit/phpunit` | 9.5.24 | 1 | high | 9.6.33 (usar 9.6.39) | 12.5.45 |

Distribuição total: **1 critical, 15 high, 23 medium, 3 low, 7 sem severidade normalizada**.

### 5.2 Análise dos avisos críticos e altos

#### Laravel 8 — `CVE-2026-114509` (critical)

- **Título:** bypass de autenticação no `TokenGuard` por credenciais de API não-string.
- **Faixa afetada:** `<10.50.4`, incluindo toda a série 8, 9 e 10 antes do patch.
- **Correção:** 10.50.4, 11.56.1, 12.68.0, 13.27.0.
- **Exposição neste projeto:** o único endpoint de API é `GET /api/user` com `auth:sanctum` (`routes/api.php:17`). Não há guard `token` configurado em `config/auth.php`. A exposição direta é menor, mas a falha é de framework e deve ser corrigida.
- **Ação:** upgrade de framework; não existe correção para Laravel 8.

#### Laravel 8 — `CVE-2024-52301` (high)

- **Título:** manipulação de ambiente via query string.
- **Correção:** 8.83.28, 9.52.17, 10.48.23, 11.31.0.
- **Exposição:** a aplicação define `APP_ENV` no `.env`, o que reduz a explorabilidade, mas o patch é simples e deve entrar no hotfix da fase 1.

#### Laravel 8 — `CVE-2025-27515` (medium)

- **Título:** bypass de validação de arquivo.
- **Correção:** 10.48.29, 11.44.1, 12.1.1. **Sem correção na série 8.**
- **Exposição:** o sistema aceita uploads de PDF e CSV (`StoreSchoolRecordRequest`, `StoreSchoolTermRequest`, `ImportOldDBRequest`). O bypass de `mimetypes`/`mimes` é relevante. Os arquivos são gravados em `storage/app`, fora do webroot, o que reduz o impacto, mas a validação ainda pode ser burlada.

#### Laravel 8 — demais avisos sem correção na série 8

- `CVE-2026-48019` (high): injeção CRLF na regra de e-mail padrão; corrigido em 12.60.0/13.10.0.
- `PKSA-m5cs-t1y6-qpcs` (medium): confusão de caminho em URLs assinadas temporárias; corrigido em 12.61.1/13.12.0.
- `CVE-2026-128083` (low): injeção de saída de tarefas; corrigido em 12.71.0/13.40.0.
- `CVE-2026-102279` (low): XSS na página de debug; corrigido em 12.69.0/13.30.0.

Esses quatro últimos avisos também **não têm correção nas séries 9, 10 e 11**: as faixas do OSV começam em 12.60.0/12.61.1/12.69.0/12.71.0. Portanto, a primeira série totalmente corrigida é a **12.71.0+** (usar 12.73.0). As séries 9, 10 e 11 são apenas trampolins.

Conclusão: **Laravel 8 acumula falhas sem correção**, incluindo uma crítica. O hotfix para 8.83.29 é apenas paliativo.

#### Guzzle 7.5.0

Nove avisos, incluindo:

- `CVE-2026-69246` (high): host não canônico contorna verificações baseadas em host.
- `CVE-2026-69245`, `CVE-2026-67354`, `CVE-2026-67355`, `CVE-2026-67353`, `CVE-2026-59883`, `CVE-2026-67339`, `CVE-2026-55767`, `CVE-2026-55568`: cookies, proxy, downgrade HTTPS e DoS.

O Guzzle é usado indiretamente pelo Socialite/Senhaunica em chamadas OAuth. O endpoint é controlado por configuração (`SENHAUNICA_DEV`), então a explorabilidade por atacante externo é limitada, mas a correção é de baixo custo: **7.15.2**.

#### CommonMark 2.3.5

Quinze avisos, a maioria de DoS quadrático ou bypass de HTML. A aplicação não usa Markdown diretamente (não há `Str::markdown` nem renderização CommonMark no código), mas o pacote é dependência do framework. Ainda assim, atualizar para **2.10.7** elimina toda a lista.

#### Symfony 5.4

Componentes com patches antigos:

- `CVE-2026-108082` (high): ReDoS em `HeaderUtils::split()` pelo header `Cache-Control`, potencialmente alcançável por requisição não autenticada.
- `CVE-2026-118514`, `CVE-2026-118516`, `PKSA-b2gg-vk1g-6d5q`: problemas de host/caminho em geração de URL e regras de acesso. Relevante porque a aplicação gera URLs assinadas para avaliações e frequências.
- `CVE-2024-51736` (high) e `CVE-2026-24739`: problemas de escape/execução no `Process` (Windows/MSYS2); o servidor é Linux, o que reduz o impacto, mas recomenda-se corrigir.
- `CVE-2026-45070`: injeção de header em parâmetros MIME.

**Ação imediata:** subir todos os componentes Symfony 5.4 para os últimos patches da série (5.4.55/5.4.56). **Ação estrutural:** migrar para Symfony 6.4 LTS (Laravel 9/10) e depois 7.4 LTS/8.0 (Laravel 12/13).

#### Flysystem 1.1.10

`CVE-2026-102601` (low): bypass do `WhitespacePathNormalizer` com UTF-8 malformado. **Não há correção na série 1.x.** O Laravel 9 já usa Flysystem 3.8+, então o upgrade de framework resolve. Como a aplicação usa `Storage` para uploads e downloads, o upgrade deve ser acompanhado de testes de path traversal e nomes de arquivo malformados.

#### Carbon 2.62.1

`CVE-2025-22145`: include arbitrário via `Carbon::setLocale` com entrada não validada. A aplicação não chama `setLocale` com entrada de usuário, então a exposição é baixa. Atualizar para **2.73.0** (ou 3.8.4+ no Laravel 12+).

#### PsySH 0.11.8

`CVE-2026-25129`: escalada de privilégio local via `.psysh.php` no diretório corrente. Afeta `php artisan tinker` em ambiente local/servidor. Atualizar para **0.11.23** (ou 0.12.19+ no Tinker 3).

#### PHPUnit 9.5.24

`CVE-2026-24765`: desserialização insegura no tratamento de PHPT/code coverage. É dependência de desenvolvimento, mas pode ser explorada em CI que processa artefatos não confiáveis. Atualizar para **9.6.39**.

### 5.3 Pacotes abandonados

| Pacote | Substituto | Ação |
|---|---|---|
| `swiftmailer/swiftmailer` | `symfony/mailer` | Removido naturalmente no Laravel 9+; migrar configuração de e-mail e testar envio real |
| `php-http/message-factory` | `psr/http-factory` | Atualizar dependências que o trazem (Sentry/Socialite) e remover |
| `fruitcake/laravel-cors` | CORS nativo do Laravel 9+ | Remover no upgrade para Laravel 9; migrar `config/cors.php` para o middleware nativo |

### 5.4 npm (`npm audit`)

Resultado com o `package-lock.json` atual:

| Severidade | Quantidade |
|---|---:|
| critical | 2 |
| high | 15 |
| moderate | 10 |
| low | 5 |
| **Total** | **32** |

Destaques:

- **`axios` 0.21.4**: 23 avisos (SSRF, prototype pollution, vazamento de credenciais, DoS). Corrigido em `1.18.0` (ou `0.33.0` na série 0.x, mas a recomendação é 1.x).
- **`postcss` 8.5.16**: 2 avisos high; corrigido em `8.5.23`.
- **`follow-redirects` 1.16.0**: 3 avisos high; corrigido em `1.16.2`.
- **`json5` 1.0.2**: 3 avisos; corrigido em `2.2.3`.
- **`webpack-dev-server` / `webpack-dev-middleware`**: avisos high; afetam apenas o servidor de desenvolvimento.
- **`laravel-mix` 6.0.49**: aviso moderate na árvore transitiva.

**Importante:** `npm audit --omit=dev` retorna **0 vulnerabilidades**. Não há JavaScript de runtime servido ao navegador; os achados são de toolchain de build/dev. Ainda assim, o build roda em CI e na máquina de desenvolvedores, então há risco de supply chain e de execução de código no build.

**Status após a Fase 7:** o build foi migrado de Laravel Mix/webpack para **Vite 8.3.8 + laravel-vite-plugin 3.2.1**. `axios`, `lodash`, `laravel-mix` e `webpack` foram removidos; o código de UI que existia apenas no bundle antigo foi recuperado para `resources/js/app-legacy.js`. `package-lock.json` versionado e assets em `public/build`. **`npm audit` completo: 0 avisos** (e `npm audit --omit=dev`: 0). A árvore vulnerável de `webpack-dev-server`/`webpack-dev-middleware`/`svgo` foi eliminada.

### 5.5 Python / E2E

| Pacote | Versão | Situação |
|---|---|---|
| `pytest` | **9.2.0** | Atualizado; corrige `CVE-2025-71176` / `PYSEC-2026-1845` |
| `pytest-playwright` | 0.10.0 | Sem aviso no OSV |
| `playwright` | **1.65.2** (pinado) | Sem aviso no OSV |
| `PyMySQL` | 1.2.4 | Sem aviso no OSV |

O `e2e/requirements.txt` fixa apenas as dependências de topo. `playwright`, `greenlet`, `pluggy`, `packaging` etc. são resolvidos no build e não estão travados. Recomenda-se gerar um lock (`uv`, `pip-compile`) ou fixar todas as versões.

### 5.6 Imagens de container

| Imagem | Situação atual | Recomendação |
|---|---|---|
| `php:8.5-fpm-bookworm` (Dockerfile) | Atualizada; tag sem digest | Pinar o digest no Dockerfile |
| `php:8.5-fpm-bookworm@sha256:bad1...` (E2E) | Atualizada, digest fixo | OK |
| `mariadb:10.5.29` (compose/E2E) | **EOL, mantido por decisão de negócio** | Risco aceito; não expor 3306; aplicar mitigações da seção 10 |
| `nginx:1.30.7-alpine@sha256:caf4...` | Atualizado e pinado | OK; residual `CVE-2026-132779` não aplicável (sem `ssl_early_data`) |
| `redis:8.2-alpine@sha256:da23...` | Pinado | OK; o serviço não é usado pela aplicação no momento |
| Node.js 22 no Dockerfile | Atualizado; tag sem digest | Pinar o digest da imagem Node |
| `python:3.12-slim` (E2E) | Suportado, digest fixo | Opcional: 3.14; fixar transitivas |

**nginx — falha crítica divulgada em 2026:** a tag flutuante `nginx:alpine` estava resolvendo para **1.29.8**, branch EOL desde 2026-05-13. Essa versão é vulnerável a:

- `CVE-2026-123007` — **memory corruption em HTTP/2 flow control** (severidade major; afetadas 1.9.5–1.31.8);
- `CVE-2026-118899` — **loop infinito ao ler corpo de requisição sobre TLS 1.3** (major; 1.13.0–1.31.8);
- `CVE-2026-113425` — **use-after-free no mail proxy** (major; 0.5.33–1.31.8);
- além de UAF em HTTP/2/HTTP/3, resolver e outras falhas corrigidas em 1.30.7+/1.31.9+.

A configuração interna usa apenas HTTP/1.1 na porta 80, sem TLS, HTTP/3 ou mail proxy, o que reduz a explorabilidade prática; ainda assim, o binário foi atualizado para **nginx 1.30.7-alpine**, pinado por digest, e o E2E foi revalidado. O residual `CVE-2026-132779` (CPU excessiva com TLS 1.3 + `ssl_early_data`, medium) só é corrigido em 1.30.8+/1.31.10+; essas imagens ainda não estavam publicadas no Docker Hub na data desta análise. Como o `ssl_early_data` não é usado e o TLS é terminado no proxy externo, o risco residual é baixo; monitorar e atualizar assim que a imagem corrigida estiver disponível.

---

## 6. Status de suporte (EOL) — situação atual

| Componente | Versão atual | EOL | Situação |
|---|---|---|---|
| PHP | 8.5.13 | 2029-12-31 | Suportado |
| Laravel | 13.43.0 | 2028-03-17 | Suportado |
| Symfony | 8.1.11 | — | Suportado |
| Node.js | 22.23.5 | 2027-04-30 | Suportado (24 LTS é opcional) |
| MariaDB | 10.5.21 (produção) / 10.5.29 (E2E) | 2025-06-24 | **EOL; mantido por decisão de negócio (risco aceito)** |
| MariaDB (E2E) | 10.5 | 2025-06-24 | EOL; apenas ambiente de teste |
| nginx | 1.30.7 | — | Suportado; residual `CVE-2026-132779` não aplicável |
| Redis | 8.2 | 2030-09-01 | Suportado; não usado pela aplicação |
| Python (E2E) | 3.12 | 2028-10-31 | Suportado |
| pytest | 8.4.2 | — | Atualizar para **9.0.3+** |

Notas:

- **Laravel 9 (EOL 2024-02-06), 10 (EOL 2025-02-04) e 11 (EOL 2026-03-12) não são destinos aceitáveis de produção.** Devem ser usados apenas como etapas intermediárias de compatibilidade.
- **Laravel 12** é o mínimo suportado hoje (até 2027-02-24), mas **Laravel 13** é o alvo recomendado (até 2028-03-17).
- **PHP 8.2** expira em 2026-12-31 (poucas semanas). Não deve ser o alvo final; usar 8.3+ e preferencialmente 8.5.
- **PHP 8.6** foi lançado em 2026-11-19 (4 dias antes desta análise) e ainda não deve ser adotado sem maturidade de ecossistema.

---

## 7. Achados de segurança no código (independentes de versão)

Estes problemas não são corrigidos por `composer update` e exigem mudanças de código. Eles devem ser tratados nas fases 0/8 do plano, com prioridade para os dois primeiros.

| ID | Severidade | Achado | Evidência | Impacto | Recomendação |
|---|---|---|---|---|---|
| SEC-01 | **Critical** | RCE autenticado via template de e-mail | `app/Mail/NotifySelectStudent.php` (antes `Blade::render` de `mailtemplate->body`); `MailTemplateController.php:63`; `RolesAndPermissionsSeeder.php` | Usuário com perfil Secretaria podia gravar `@php ... @endphp` em um template e executar comandos como `www-data` | **Corrigido:** Twig 3.31 sandbox + contexto sanitizado; `Editar E-mails` restrito a Administrador; testes de payload malicioso (`MailTemplateRendererTest`) |
| SEC-02 | **Critical** | Escalada de privilégio Secretaria → Administrador | `app/Http/Controllers/UserController.php:99-106`; `app/Http/Requests/UserRequest.php:32`; `RolesAndPermissionsSeeder.php:77` (Secretaria tem `editar usuario`) | Secretaria pode enviar `roles[]=Administrador` no `PUT /users/{id}` e elevar a própria conta ou outra conta | Criar Policy/Gate específico; impedir que não-admin atribua `Administrador`; validar roles com `exists`; impedir auto-atribuição; testar matriz de permissões |
| SEC-03 | **High** | Exposição não autenticada de dados de alunos | `StudentController.php`; rota `students` | Qualquer pessoa podia consultar `GET /students?codpes=...`/`?nompes=...` e obter dados do Replicado | **Corrigido:** auth + only operational roles (Admin/Secretaria/Docente/Comissão); aluno recebe 403; teste de regressão |
| SEC-04 | **High** | XSS armazenado em comentários | `resources/views/instructorevaluations/show.blade.php:114`; `resources/views/instructors/requisitions.blade.php:58` (`{!! $comments !!}`) | Docente/Secretaria pode injetar script que executa na sessão de Administrador/Secretaria, permitindo ações em nome da vítima | Usar `{{ }}` (escape) ou sanitizar HTML com allowlist; adicionar CSP; testar payloads |
| SEC-05 | Medium | Download público de arquivo arbitrário em `storage/app` | `routes/web.php:43`; `SchoolTermController.php:155-159`; `DownloadPublicNoticeRequest.php:28` | Endpoint sem autenticação aceita `path` arbitrário; com caminho conhecido, baixa qualquer arquivo de `storage/app` (PDFs de histórico, editais etc.) | Exigir autenticação; não aceitar caminho cru; usar `Storage::download` apenas de caminho obtido do banco e vinculado ao registro; URLs assinadas de curta duração |
| SEC-06 | Medium | Mudança de estado via GET e bug de variável | `FrequencyController.php`; rotas de frequência | `GET /frequencies/{frequency}` alterava `registered`; suscetível a CSRF/prefetch | **Corrigido:** GET abre página de confirmação; alteração por PATCH com CSRF; `$schoolclass` corrigido; testes |
| SEC-07 | Medium | Backdoor de administrador por substring | `app/Models/User.php:60` (`str_contains(env('LOG_AS_ADMINISTRATOR'), $codpes)`) | Se a variável contiver uma lista de códigos, qualquer `codpes` que seja substring de um deles recebe Administrador | Comparar tokens exatos (`in_array`), remover a variável em produção, exigir configuração explícita |
| SEC-08 | Medium | Token de relatório externo em query string e comparação não constante | `ReportController.php` | Token podia vazar em logs/Referer; comparação não timing-safe | **Parcial:** aceita `Authorization: Bearer` e query em transição; `hash_equals` + `throttle:20,1`; remover query após migração do consumidor |
| SEC-09 | Low/Medium | CORS com `allowed_origins: ['*']` | `config/cors.php:22` | Qualquer origem pode chamar `api/*`; CORS não protege bearer tokens, mas amplia a superfície | Allowlist explícita de domínios; revisar `supports_credentials` |
| SEC-10 | Low/Medium | Cookies de sessão sem `secure` obrigatório e sem criptografia | `config/session.php` | Em produção, cookie podia trafegar sem HTTPS ou ser lido/alterado se houver XSS | **Corrigido:** `SESSION_ENCRYPT=true`, `Secure` automático em produção, HSTS em produção/HTTPS, `SameSite=Lax` |
| SEC-11 | Medium | Autorização dispersa e ausência de `auth` global | `routes/web.php` | Novas rotas podiam nascer públicas por omissão; difícil garantir cobertura de autorização | **Corrigido:** rotas protegidas agrupadas em `auth`, whitelist explícita de rotas públicas e testes `AuthRoutesTest` |
| SEC-12 | Medium | Supply chain / reprodutibilidade | `.gitignore:2` ignora `package-lock.json`; `composer.json:11` (`laratex: dev-main`), `:21` (`usp-theme: dev-master` de fork pessoal), `minimum-stability: dev` | Builds não reprodutíveis; código de branch/fork pessoal pode ser alterado sem revisão e executado pela aplicação | Versionar `package-lock.json`; usar tags estáveis; mover o fork para a organização oficial ou revisar/assinar; remover `minimum-stability: dev` |
| SEC-13 | Low | Configuração de debug e endpoints de teste | `.env.example:4` (`APP_DEBUG=true`); `routes/web.php:59` (`/students/test` sem método); `E2E_MODE` | Debug em produção expõe stack traces; endpoint quebrado gera ruído; modo E2E pode mascarar Replicado se ativado por engano | Garantir `APP_DEBUG=false` em produção; remover rota quebrada; falhar o boot se `E2E_MODE=true` fora de testing |
| SEC-14 | **High** | IDOR em autoavaliação por atribuição acidental (`=!` em vez de `!=`) | `app/Http/Controllers/SelfEvaluationController.php:165` | Aluno conseguia visualizar a autoavaliação de outro aluno | Corrigido para comparação estrita de `codpes`, com teste de regressão `test_cen_selfeval_013b` |

### 7.1 Detalhamento dos achados críticos

#### SEC-01 — RCE via template de e-mail

O fluxo é:

1. A Secretaria possui a permissão `Editar E-mails` (`database/seeders/RolesAndPermissionsSeeder.php:96`).
2. `MailTemplateController@store` aceita `body` com até 8.192 caracteres, sem sanitização (`StoreMailTemplateRequest.php:29-30`).
3. As classes de e-mail, por exemplo `NotifySelectStudent@build`, executam `Blade::render(html_entity_decode($this->mailtemplate->body), [...])` (`app/Mail/NotifySelectStudent.php:51`).
4. `Blade::render` compila diretivas Blade para PHP. Um corpo como `@php system('id'); @endphp` executa código no servidor.
5. A própria Secretaria também possui `Disparar emails` e pode usar `POST /mailtemplates/test` para disparar a renderização imediatamente.

Mitigação de curto prazo (antes do upgrade): restringir `Editar E-mails` a Administrador, revisar templates existentes no banco e, se possível, desabilitar o endpoint de teste. Mitigação definitiva: trocar `Blade::render` por substituição de placeholders conhecidos (ex.: `{{student.nome}}`) ou um sandbox de template sem PHP.

#### SEC-02 — Escalada de privilégio

O fluxo é:

1. A Secretaria possui `editar usuario` (`RolesAndPermissionsSeeder.php:77`).
2. `UserController@update` exige apenas esse Gate (`UserController.php:99`).
3. `UserRequest` valida `roles` apenas como `array|min:1`, sem `exists` e sem limite de quais roles podem ser atribuídas (`UserRequest.php:32`).
4. O controller faz `roles()->detach()` e `assignRole($validated['roles'])` (`UserController.php:105-106`).

Assim, uma Secretaria pode enviar `roles: ["Administrador"]` para o próprio `id` e obter todos os privilégios. A correção deve ser feita com Policy específica e validação server-side de que o ator só pode atribuir roles que ele mesmo possui (ou apenas Administrador pode atribuir Administrador).

#### SEC-03 — Exposição de dados de alunos

`GET /students` é um `Route::resource` sem middleware de autenticação. O método `index` responde JSON quando `Accept: application/json`, consultando o Replicado por `codpes` ou `nompes`. O teste `test_cen_student_001_busca_json_por_codpes_retorna_dados_do_aluno` executa a chamada **sem `actingAs`**, confirmando que o comportamento é público. Isso permite enumeração de alunos e e-mails.

#### SEC-04 — XSS armazenado

Os comentários de avaliação de docente e de requisição são renderizados com `{!! !!}`, sem escape. Um docente pode inserir `<script>` no comentário; ao abrir a tela, um Administrador/Secretaria executa o script. Combinado com SEC-02, um XSS pode ser usado para executar a escalada de privilégio sem interação do atacante.

---

## 8. Cobertura dos testes para o upgrade

### 8.1 O que está bem coberto

A suíte PHPUnit é relevante e cobre os principais domínios:

| Área | Testes |
|---|---:|
| SchoolTerm | 22 |
| Selection | 18 |
| Requisition | 18 |
| SchoolClass | 17 |
| Enrollment | 16 |
| EmailController | 16 |
| SelfEvaluation | 15 |
| Student/SchoolRecord | 13 |
| MailTemplate | 13 |
| Tutor | 11 |
| Frequency | 11 |
| User | 10 |
| InstructorEvaluation | 10 |
| Certificate | 10 |
| Replicado | 9 |
| ConsoleCommand | 9 |
| Report | 6 |
| OldDBImport | 6 |
| Instructor | 6 |
| Kernel | 5 |
| Job | 4 |
| **Total** | **247** |

Pontos fortes:

- Testes de Feature usam `RefreshDatabase`, seed de roles/permissões e stubs de Replicado.
- Há testes de autorização negativa (403) em vários módulos.
- Há testes de jobs, commands, kernel/schedule, importação de banco antigo e links assinados.
- A suíte E2E cobre jornadas críticas de navegador, incluindo adulteração de link assinado e negação de acesso por perfil.

### 8.2 Lacunas relevantes para um upgrade major

| Lacuna | Impacto no upgrade |
|---|---|
| **Sem métrica de cobertura** (não há Xdebug/PCOV) | Não é possível afirmar qual percentual de `app/` está coberto; adicionar driver e gate de cobertura |
| **E2E com 41 testes para 244 cenários planejados (+10 de UI legada)** | `e2e/coverage.yaml` tem 244 cenários planejados; os 10 cenários `CEN-UI-LEGACY-*` estão `implemented`; a cobertura real ainda é ~16% |
| **Testes dependentes de data** | `FrequencyScenarioTest` pula testes conforme o dia do mês; a suíte não roda 100% em qualquer data |
| **Sem testes de frontend/JS** | O upgrade de axios/postcss/webpack pode quebrar interações e não será detectado |
| **Sem teste com Replicado real** | Os testes usam `ReplicadoStubs`/fixtures; o upgrade de `uspdev/replicado`, PHP 8 e `pdo_dblib` pode quebrar SQL/Sybase sem ser detectado |
| **Sem teste com MariaDB 11.8** | Não aplicável no momento: o MariaDB 10.5 é mantido por decisão; o E2E usa a mesma linha 10.5 da produção |
| **PDF não é validado de verdade** | `CertificateScenarioTest` mocka `LaraTeX`; nenhum teste gera PDF real e verifica conteúdo |
| **E-mail não é renderizado de verdade** | `Mail::fake()` impede o `build()`; o E2E de templates não envia e-mail; RCE e regressões de Symfony Mailer passam despercebidos |
| **Sem testes de segurança** | Os testes atuais **validam comportamento inseguro** (ex.: `/students` sem login e `/schoolterms/download` sem login) e não cobrem escalada de privilégio, XSS ou RCE |
| **Redis/queue worker assíncrono** | `QUEUE_CONNECTION=sync` nos testes; o comportamento real de fila/worker não é exercitado |

### 8.3 Resposta direta: os testes garantem ausência de regressões?

**Não, ainda não.** Eles são uma boa base funcional (247 testes + 31 E2E, todos verdes), mas não são suficientes para um upgrade de Laravel 8 para 13 com segurança. Antes de cada fase, é necessário:

1. Adicionar cobertura PHP com Xdebug/PCOV e meta mínima (ex.: 70% global, 90% nos módulos críticos).
2. Congelar o tempo nos testes de frequência (`Carbon::setTestNow`) e remover skips por data.
3. Adicionar testes de segurança (não autenticado, matriz de roles, XSS, RCE de template, IDOR, CSRF).
4. Adicionar testes de integração reais: MariaDB 10.5 (produção), Replicado/Sybase de staging, geração de PDF, envio SMTP para Mailpit.
5. Adicionar testes de contrato para os endpoints JSON e para os links assinados.
6. Adicionar smoke tests pós-deploy em staging com dados anonimizados de produção.

Sem esses itens, o risco de regressão silenciosa é alto, especialmente em autenticação, geração de PDF, envio de e-mail, schedule e integração Replicado.

---

## 9. Plano de upgrade

### 9.1 Princípios

1. **Uma major de framework por PR/fase**, com testes completos entre fases.
2. **Nunca parar em versão EOL**: Laravel 9, 10 e 11 são apenas trampolins; o destino é 12 ou 13.
3. **Atualizar primeiro o que é compatível com a stack atual** e reduz risco imediato.
4. **Fixar versões e digests**; remover dependências de branch/fork pessoal.
5. **Banco de dados em fase separada**, com dump/restore e rollback testado.
6. **Nenhuma fase avança com `composer audit`/`npm audit` piorando** ou com testes vermelhos.
7. **Corrigir os achados SEC-01 e SEC-02 imediatamente**, independentemente do cronograma de upgrade.

### 9.2 Visão geral das fases

| Fase | Objetivo | Framework | PHP | Principais mudanças | Esforço | Critério de saída |
|---|---|---|---:|---|---:|---|
| 0 | Baseline e guardrails | 8.83.23 | 7.4 | CI, cobertura, pinagem, locks, auditoria | 1–2 d | Pipeline verde com auditoria e cobertura |
| 1 | Hotfix de segurança na stack atual | 8.83.29 | 7.4 | Guzzle, CommonMark, Carbon, Symfony 5.4.56, Sentry, Replicado, PHPUnit; Node 24; axios/postcss | 2–3 d | Testes verdes; avisos reduzidos; residual documentado |
| 2 | Laravel 8 → 9 | 9.52.23 | 8.2 | Symfony 6.4, Flysystem 3, Symfony Mailer, remove fruitcake, Sanctum 3, Spatie 5 | 3–5 d | Testes verdes em MariaDB 10.5 staging |
| 3 | Laravel 9 → 10 | 10.50.4 | 8.2 | Monolog 3, Sanctum 3.3, Spatie 6, QueueMonitor 5.4, PHPUnit 10 | 1–3 d | Testes verdes; Carbon 2.73 |
| 4 | Laravel 10 → 11 | 11.57.1 | 8.3 | Nova estrutura `bootstrap/app.php`, remove Kernel/Handler, Spatie 6, Sanctum 4, LaraTeX 1.3.2, Tinker 3, USP Theme oficial | 4–6 d | Testes verdes; schedule migrado; e2e p0 |
| 5 | Laravel 11 → 12 | 12.73.0 | 8.4 | Carbon 3, Spatie 8, QueueMonitor 5.5, Staudenmeir 12, Sanctum 4.5 | 2–3 d | Testes verdes; PHPUnit 12 |
| 6 | Laravel 12 → 13 | 13.43.0 | 8.5 | Symfony 7.4/8.0, Guzzle 7.15/8, Staudenmeir 13, ajustes finais | 2–3 d | Testes verdes; auditoria limpa |
| 7 | Infra final (parcial) | 13.43.0 | 8.5 | nginx 1.30.7, Redis 8.2, Node/axios, pytest 9; MariaDB 10.5 mantido por decisão | 2–3 d | Imagens pinadas; risco MariaDB aceito |
| 8 | Hardening e encerramento | 13.43.0 | 8.5 | Correções SEC-01..SEC-13, políticas, CORS, cookies, rate limit, secrets | 3–5 d | Checklist de segurança concluído |

**Total:** 9 fases, ~12 PRs, **21–35 dias ideais** (4–6 semanas com um desenvolvedor; 2–3 semanas com dois). Adicionar buffer para imprevistos de Replicado/LaTeX e para janelas de banco.

### 9.3 Ordem de atualização das bibliotecas

#### P0 — Fazer já, compatível com Laravel 8 / PHP 7.4

Estas atualizações reduzem a superfície imediata sem exigir upgrade de framework:

| Pacote | De | Para |
|---|---|---|
| `laravel/framework` | 8.83.23 | **8.83.29** |
| `symfony/http-foundation` | 5.4.50 | **5.4.56** |
| `symfony/http-kernel` | 5.4.12 | **5.4.56** |
| `symfony/http-client` | 5.4.53 | **5.4.56** |
| `symfony/routing` | 5.4.11 | **5.4.56** |
| `symfony/mime` | 5.4.52 | **5.4.55** |
| `symfony/process` | 5.4.11 | **5.4.54** |
| `symfony/console` | 5.4.12 | **5.4.55** |
| `guzzlehttp/guzzle` | 7.5.0 | **7.15.2** |
| `league/commonmark` | 2.3.5 | **2.10.7** |
| `nesbot/carbon` | 2.62.1 | **2.73.0** |
| `psy/psysh` | 0.11.8 | **0.11.23** |
| `phpunit/phpunit` | 9.5.24 | **9.6.39** |
| `sentry/sentry-laravel` | 3.8.2 | **4.31.0** |
| `uspdev/replicado` | 1.13.2 | **1.13.2** (pin; 1.28.9 exige PHP 8) |
| `romanzipp/laravel-queue-monitor` | 2.3.0 | **2.3.4** |
| `mockery/mockery` | 1.5.0 | **1.6.15** |
| `fakerphp/faker` | 1.20.0 | **1.24.1** |
| `facade/ignition` | 2.17.6 | **2.17.7** |
| `ismaelw/laratex` | `dev-main` | **`^1.3.0-p1`** (tag) |
| `axios` (npm) | 0.21.4 | **1.18.0** |
| `postcss` (npm) | 8.5.16 | **8.5.23** |
| `follow-redirects` (npm) | 1.16.0 | **1.16.2** |
| `json5` (npm) | 1.0.2 | **2.2.3** |
| Node.js | 16 | **24 LTS** |

`uspdev/senhaunica-socialite` 4.3.2 → 4.5.12 foi tentada e **revertida**: a 4.5.12 chama `Graduacao::obterCursoAtivo()`, que não existe no Replicado 1.13.2, e o Replicado 1.28.9 usa sintaxe PHP 8 incompatível com PHP 7.4. As duas ficam pinadas em 4.3.2/1.13.2 até a fase 2, quando o runtime será PHP 8.2. O Sentry 4.31.0 foi aplicado com sucesso no PHP 7.4.

O que **não** dá para corrigir na fase 1:

- `laravel/framework` critical `CVE-2026-114509` e `CVE-2025-27515` (sem correção na série 8).
- `league/flysystem` 1.x (correção só na 3.x).
- `swiftmailer` abandonado (correção só no Laravel 9+ com Symfony Mailer).

#### P1 — Nas fases de framework (2 a 6)

| Pacote | Laravel 9 | Laravel 10 | Laravel 11 | Laravel 12 | Laravel 13 |
|---|---|---|---|---|---|
| `laravel/framework` | 9.52.23 | 10.50.4 | 11.57.1 | 12.73.0 | 13.43.0 |
| `symfony/*` | 6.4 LTS | 6.4 LTS | 7.0/7.4 | 7.2/7.4 | 7.4/8.0 |
| `league/flysystem` | 3.36.0 | 3.36.0 | 3.36.0 | 3.36.0 | 3.36.0 |
| `laravel/sanctum` | 3.3.3 | 3.3.3 | 4.4.0 | 4.5.0 | 4.5.0 |
| `spatie/laravel-permission` | 5.11.1 | 6.25.0 | 6.25.0 | 8.5.0 | 8.5.0 |
| `romanzipp/laravel-queue-monitor` | 5.3.7 | 5.4.5 | 5.4.5 | 5.5.0 | 5.5.0 |
| `staudenmeir/belongs-to-through` | v2.12.1 | v2.15.1 | v2.16.4 | v2.17 | v2.18 |
| `staudenmeir/eloquent-has-many-deep` | v1.17.3 | 1.19.5 | v1.20.7 | v1.21.3 | v1.22.1 |
| `ismaelw/laratex` | 1.3.0-p1 | 1.3.0-p1 | 1.3.2 | 1.3.2 | 1.3.2 |
| `laravel/tinker` | 2.x | 2.x | 3.2.0 | 3.2.0 | 3.2.0 |
| `nesbot/carbon` | 2.73.0 | 2.73.0 | 2.73.0/3.8.4 | 3.15.1 | 3.15.1 |
| `monolog/monolog` | 2.x | 3.x | 3.x | 3.x | 3.10+ |
| `phpunit/phpunit` | 9.6.39 | 10.5.67 | 11.5.58 | 12.5.45 | 12.5.45 |
| `sentry/sentry-laravel` | 4.31.0 | 4.31.0 | 4.31.0 | 4.31.0 | 4.31.0 |
| `uspdev/replicado` | 1.28.9 | 1.28.9 | 1.28.9 | 1.28.9 | 1.28.9 |
| `uspdev/senhaunica-socialite` | 4.5.12 | 4.5.12 | 4.5.12 | 4.5.12 | 4.5.12 |
| `uspdev/laravel-usp-theme` | 2.8.37 (oficial) | 2.8.37 (oficial) | 2.8.44 | 2.8.44 | 2.8.44 |
| `laravel/socialite` | 5.31.0 | 5.31.0 | 5.31.0 | 5.33.0 | 5.33.0 |
| `guzzlehttp/guzzle` | 7.15.2 | 7.15.2 | 7.15.2 | 7.15.2 | 7.15.2/8.0.1 |

Nota: `uspdev/replicado` 1.28.9 e `uspdev/senhaunica-socialite` 4.5.12 só funcionam na prática com PHP 8.2+; por isso a fase 2 (Laravel 9) já sobe o runtime para PHP 8.2. No PHP 7.4, manter 1.13.2/4.3.2.

#### P2 — Fase 7 e 8

- MariaDB 10.5 mantido por decisão; aplicar mitigações da seção 10.
- nginx 1.30.7-alpine pinado por digest; monitorar 1.30.8+ para o residual `CVE-2026-132779`.
- Redis 8.2-alpine pinado (se usado).
- Python 3.14 e pytest 9.0.3+, com lock de dependências.
- Migração opcional de Laravel Mix para Vite (recomendada para eliminar a árvore de build vulnerável).
- Correções de código SEC-01 a SEC-13.

### 9.4 Fase 0 — Baseline e guardrails

Entregáveis:

1. Branch `chore/security-baseline` e tag do estado atual.
2. Pipeline de CI com:
   - `composer validate --strict`
   - `composer audit --locked`
   - `npm ci && npm audit --audit-level=high`
   - `phpunit` com cobertura (Xdebug/PCOV)
   - `./scripts/run_e2e.sh -m p0`
   - Trivy/Grype nas imagens Docker
   - gitleaks/detect-secrets
3. Versionar `package-lock.json` e usar `npm ci`.
4. Fixar digests de todas as imagens.
5. Criar `docs/` com este relatório e um checklist de release.
6. Medir cobertura inicial e congelar a data dos testes de frequência.

Critério de saída: pipeline verde, cobertura reportada, auditoria executando em PR.

### 9.5 Fase 1 — Hotfix de segurança na stack atual

Objetivo: reduzir risco imediato sem upgrade de framework.

Passos:

1. Aplicar o conjunto P0 (tabela acima) em um branch `security/hotfix-l8`.
2. Rodar `composer update` com `--with-all-dependencies` e revisar o diff do lock.
3. Atualizar o Dockerfile para Node 24 LTS; rodar `npm ci` após versionar o lock; `npm run prod`.
4. Atualizar `e2e/requirements.txt` para pytest 9.0.3+ e pinar `playwright`.
5. Rodar PHPUnit, E2E e auditorias.
6. Documentar riscos residuais: Laravel 8 critical, Flysystem 1.x, SwiftMailer abandonado.

Atenção:

- `guzzle 7.15.2` exige `guzzlehttp/promises ^2.5.1` e `psr7 ^2.13`; revisar compatibilidade com Socialite/Senhaunica.
- `commonmark 2.10.7` suporta PHP 7.4.
- `symfony 5.4.56` suporta PHP 7.4.
- `sentry-laravel 4.31.0` suporta Laravel 8–13 e PHP 7.2+; aplicado com sucesso.
- `replicado 1.28.9` **não funciona em PHP 7.4**: usa `string|null` em `Pessoa.php:153`, apesar de declarar `php ^7 || ^8`. Manter 1.13.2 até a fase 2 (PHP 8.2). A 1.28.9 também traz `uspdev/cache ^1`.
- `senhaunica 4.5.12` chama `Graduacao::obterCursoAtivo()` (inexistente no Replicado 1.13.2) e depende do Replicado novo. Manter 4.3.2 até a fase 2.

### 9.6 Fase 2 — Laravel 8 → 9

Mudanças estruturais:

- PHP 7.4 → **8.2** (Laravel 9 exige ^8.0.2; 8.2 é uma base melhor para as fases seguintes).
- Symfony 5.4 → **6.4 LTS**.
- Flysystem 1 → **3.36.0** (corrige `CVE-2026-102601`).
- SwiftMailer → **Symfony Mailer** (remover `swiftmailer/swiftmailer`).
- `fruitcake/laravel-cors` → CORS nativo do Laravel 9 (remover pacote; manter `config/cors.php`).
- `laravel/sanctum` → **3.3.3**.
- `spatie/laravel-permission` → **5.11.1**.
- `uspdev/replicado` → **1.28.9** (agora com PHP 8.2).
- `uspdev/senhaunica-socialite` → **4.5.12**.
- `laravel/socialite` → **5.31.0** e `firebase/php-jwt` → **7.x** (elimina o advisory low restante).
- `romanzipp/laravel-queue-monitor` → **5.3.7**.
- `staudenmeir/*` → **v2.12.1 / v1.17.3**.
- `laratex` → **1.3.0-p1**.
- `uspdev/laravel-usp-theme` → substituir o fork pessoal pela versão oficial **2.8.37** (ou a última sem `cadastros-auxiliares-client`).
- Ajustar `password_resets` → `password_reset_tokens` ou manter a tabela antiga explicitamente em `config/auth.php`.
- Remover `$namespace` do `RouteServiceProvider` se não estiver em uso.
- Revisar `config/mail.php` para o formato do Symfony Mailer (`MAIL_SCHEME`, `MAIL_ENCRYPTION`).

Testes obrigatórios: e-mail real via Mailpit, PDF real, uploads/downloads, fila/worker, Replicado staging, MariaDB 10.5.

**Resultado da execução (2026-11-23):** etapa concluída. Laravel 9.52.23, PHP 8.2.36, Symfony 6.4.50, Flysystem 3.36.0, Symfony Mailer, Sanctum 3.3.3, Spatie 5.11.1, Replicado 1.28.9, Senhaunica 4.5.12, QueueMonitor 5.3.7, Staudenmeir 2.12.1/1.17.3 e LaraTeX 1.3.0-p1. **264 testes PHPUnit + 31 E2E verdes**. Ajustes adicionais: migrations e config do QueueMonitor 5.x, remoção de `NO_AUTO_CREATE_USER` do `config/database.php`, CORS nativo (`Illuminate\Http\Middleware\HandleCors`) e `FakeReplicadoDb` com `getQuery()`/`obterCursoAtivo()`. O `uspdev/laravel-usp-theme` continua no fork pinado porque a versão oficial exige `cadastros-auxiliares-client`/Laravel 11+.

### 9.7 Fase 3 — Laravel 9 → 10

Mudanças:

- `laravel/framework` **10.50.4** (corrige a critical `CVE-2026-114509`).
- PHP 8.2.
- Monolog 3.
- `spatie/laravel-permission` **6.25.0**.
- `laravel/sanctum` **3.3.3**.
- `romanzipp/laravel-queue-monitor` **5.4.5**.
- `staudenmeir/*` **v2.15.1 / 1.19.5**.
- `phpunit/phpunit` **10.5.67**.
- Carbon 2.73.0.
- Revisar `$dates` (não encontrado no código), casts e `Model::load`.

Esta é uma fase relativamente curta; o objetivo é chegar ao Laravel 10.50.4, que corrige a critical `CVE-2026-114509` e as falhas com patch disponível para a série 10, mas **não** corrige os quatro avisos mais recentes (`CVE-2026-48019`, `CVE-2026-128083`, `CVE-2026-102279` e `GHSA-crmm-hgp2-wgrp`), cujas correções começam na série 12. A primeira versão com todas as CVEs conhecidas corrigidas é **12.71.0+** (usar 12.73.0). **Não parar no L10**, pois ele está EOL.

**Resultado da execução (2026-11-23):** etapa concluída. Laravel 10.50.4, Monolog 3.13.0, PHPUnit 10.5.67, Spatie 6.25.0, QueueMonitor 5.4.5, Staudenmeir 2.15.1/1.19.5, Collision 7.12.0 e Ignition 2.9.1. `phpunit.xml` migrada para o schema do PHPUnit 10. **264 testes PHPUnit 10 + 31 E2E verdes**. Auditoria: 5 avisos em 1 pacote (Laravel 10).

### 9.8 Fase 4 — Laravel 10 → 11

Esta é a fase de maior esforço estrutural:

- `laravel/framework` **11.57.1**; PHP **8.3**.
- Migrar `app/Http/Kernel.php`, `app/Console/Kernel.php`, `app/Exceptions/Handler.php` e `app/Providers/RouteServiceProvider.php` para `bootstrap/app.php` (`withMiddleware`, `withExceptions`, `withRouting`).
- Mover o schedule de `app/Console/Kernel.php` para `routes/console.php` ou `bootstrap/app.php`.
- `spatie/laravel-permission` **6.25.0** (suporta L8.12–13).
- `laravel/sanctum` **4.4.0**.
- `staudenmeir/*` **v2.16.4 / v1.20.7**.
- `romanzipp/laravel-queue-monitor` **5.4.5**.
- `ismaelw/laratex` **1.3.2** (exige Symfony Process 7.4/8.0; ajustar `symfony/process`).
- `laravel/tinker` **3.1.0** (a 3.2 exige L12+).
- `phpunit/phpunit` **11.5.58**.
- `uspdev/laravel-usp-theme` **2.8.44** + `uspdev/cadastros-auxiliares-client ^1` (avaliado e adiado: o fork pinado foi mantido nesta fase para evitar nova dependência externa).
- Revisar `config/*` que deixaram de existir ou mudaram de formato.

Testes obrigatórios: schedule, commands, middleware, autenticação, e2e p0 completo.

**Resultado da execução (2026-11-23):** etapa concluída. Laravel 11.57.1, PHP 8.3.37, Symfony 7.4.23, Carbon 3.15.1, Sanctum 4.4.0, Tinker 3.1.0, PHPUnit 11.5.58, Collision 8.9.6, Ignition 2.12.1, LaraTeX 1.3.2, Staudenmeir 2.16.4/1.20.7. Estrutura migrada para `bootstrap/app.php`/`bootstrap/providers.php`; kernels/handler/RouteServiceProvider removidos; schedule consolidado em `ScheduledTasks` com execução a cada minuto e consultas em runtime (evita acesso ao banco no boot). **264 testes PHPUnit 11 + 31 E2E verdes**. Auditoria: 5 avisos em 1 pacote (Laravel 11).

### 9.9 Fase 5 — Laravel 11 → 12

- `laravel/framework` **12.73.0**; PHP **8.4**.
- Carbon **3.15.1** (atenção a breaking changes de Carbon 3).
- `spatie/laravel-permission` **8.5.0**.
- `laravel/sanctum` **4.5.0**.
- `romanzipp/laravel-queue-monitor` **5.5.0** (Carbon 3).
- `staudenmeir/*` **v2.17 / 1.21.3**.
- `laravel/socialite` **5.33.0**.
- `phpunit/phpunit` **12.5.45**.

Laravel 12 é o mínimo suportado hoje (EOL 2027-02-24). Se o tempo apertar, este é o ponto de parada aceitável, mas o recomendado é seguir para 13.

**Resultado da execução (2026-11-23):** etapa concluída. Laravel 12.73.0, PHP 8.3.37, Symfony 7.4.23, PHPUnit 12.5.45, Spatie 8.5.0, Sanctum 4.5.0, Tinker 3.2.0, QueueMonitor 5.5.0, Staudenmeir 2.17/1.21.3 e Socialite 5.33.0. **Composer audit: 0 avisos. 264 PHPUnit 12 + 31 E2E verdes.**

### 9.10 Fase 6 — Laravel 12 → 13

- `laravel/framework` **13.43.0**; PHP **8.5** (fallback 8.4).
- Symfony **7.4 LTS/8.0**.
- `staudenmeir/*` **v2.18 / 1.22.1**.
- `romanzipp/laravel-queue-monitor` **5.5.0**.
- `spatie/laravel-permission` **8.5.0**.
- `laravel/sanctum` **4.5.0**.
- Guzzle **7.15.2** (ou 8.0.1, se todos os pacotes suportarem).
- `ismaelw/laratex` **1.3.2**.
- Remover definitivamente `swiftmailer/swiftmailer`, `php-http/message-factory` e qualquer resquício de `fruitcake/laravel-cors`.

**Resultado da execução (2026-11-23):** etapa concluída. Laravel 13.43.0, PHP 8.5.13, Symfony 8.1.11, Staudenmeir 2.18/1.22.1, Ignition 2.13.0, Sanctum 4.5.0, Spatie 8.5.0, QueueMonitor 5.5.0 e PHPUnit 12.5.45. Corrigida a depreciação `PDO::MYSQL_ATTR_SSL_CA` (`Pdo\Mysql::ATTR_SSL_CA`). **Composer audit: 0 avisos. 264 PHPUnit 12 + 31 E2E verdes.**

### 9.11 Fase 7 — Infraestrutura final

#### Banco de dados — MariaDB 10.5 mantido por decisão

1. **Decisão de negócio:** não atualizar o MariaDB 10.5 (banco legado).
2. O `NO_AUTO_CREATE_USER` já foi removido do `config/database.php`; a configuração atual funciona no MariaDB 10.5 e não bloqueia uma futura migração.
3. Aplicar as mitigações da seção 10: porta 3306 não exposta, usuário de aplicação com privilégios mínimos, backups com restore testado, monitoramento e segmentação de rede.
4. Registrar a exceção no inventário de riscos e revisar periodicamente.
5. O E2E continua usando MariaDB 10.5 por compatibilidade com o dump; isso é aceitável apenas como ambiente de teste.

#### Frontend/build

1. Node 22 LTS no Dockerfile (já aplicado); pinar o digest da imagem.
2. Vite **8.3.8** + `laravel-vite-plugin` **3.2.1** (aplicado); `package-lock.json` versionado e `npm ci` no build.
3. `@vite` no `parent.blade.php`; assets em `public/build`; Laravel Mix, webpack, axios e lodash removidos.
4. O Dockerfile de E2E builda os assets em um estágio Node antes de copiar `public/build`.
5. pytest **9.2.0** e Playwright **1.65.2** pinados no E2E.

#### Containers

1. `php:8.5-fpm-bookworm` (já aplicado); pinar o digest no Dockerfile.
2. `nginx:1.30.7-alpine` pinado por digest (já aplicado); monitorar 1.30.8+ para o residual `CVE-2026-132779`.
3. `mariadb:10.5` mantido por decisão; não expor a porta e aplicar mitigações da seção 10.
4. `redis:8.2-alpine` pinado por digest (já aplicado).
5. `python:3.12-slim` no E2E (ou 3.14) com digest; fixar transitivas.
6. Trivy/Grype sem vulnerabilidades critical/high não mitigadas.

**Resultado da execução (2026-11-23):** nginx 1.30.7-alpine pinado; Redis 8.2-alpine pinado; frontend migrado para Vite 8.3.8, `package-lock.json` versionado, `public/build` gerado e código legado recuperado; pytest 9.2.0 e Playwright 1.65.2; **`npm audit` completo = 0**; **31 E2E verdes** com Vite e nginx 1.30.7. MariaDB 10.5 mantido por decisão de negócio (seção 10). A imagem Node ainda usa a tag 22.x do NodeSource (pinar digest é opcional).

### 9.12 Fase 8 — Hardening e encerramento

1. Corrigir SEC-01 e SEC-02 (críticos) antes de qualquer exposição adicional.
2. Corrigir SEC-03 a SEC-13.
3. Adicionar `TrustHosts` ao stack global e configurar `APP_URL`/`URL::forceRootUrl` para evitar host header injection em URLs assinadas.
4. Aplicar `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, HSTS e CSP.
5. Restringir CORS a domínios conhecidos.
6. Adicionar rate limit em endpoints públicos e de autenticação.
7. Rotacionar segredos que possam ter sido expostos (`MAIL_PASSWORD`, `EXTERNAL_REPORT_TOKEN`, `SENHAUNICA_SECRET`, `APP_KEY` se houver suspeita).
8. Auditar templates de e-mail existentes no banco.
9. Remover rotas/métodos de debug (`/students/test`, endpoints de monitor sem auth).
10. Rodar auditoria final (`composer audit`, `npm audit`, Trivy, gitleaks) e anexar o resultado ao release.

**Resultado (2026-11-23):** SEC-01 (Twig sandbox), SEC-02, SEC-03, SEC-04, SEC-05, SEC-06, SEC-07, SEC-08 (transição header/query), SEC-10 (cookies/HSTS), SEC-11 (auth baseline), SEC-13, SEC-14, `TrustHosts`, CSP Report-Only, rate limit e CI concluídos. Pendentes: SEC-09/CORS wildcard (decisão), remoção da query do token externo, CSP enforce, rotação de segredos, Trivy/gitleaks, teste de rollback e mover o fork do tema para a organização/tag.

---

## 10. Banco de dados: decisão de manter o MariaDB 10.5 (legado)

**Decisão de negócio (2026-11-23):** a produção usa **MariaDB 10.5.21** e o banco **não será atualizado** no momento. O banco é legado e não há possibilidade de migração agora. Esta é uma **exceção formal** ao plano de upgrade; o risco de EOL (2025-06-24, sem novos patches de segurança) é aceito pelos responsáveis. O stack local/E2E usa **MariaDB 10.5.29**, mesma linha 10.5, compatível com o dump de produção.

Mitigações obrigatórias enquanto o MariaDB 10.5 for mantido:

1. **Não expor a porta 3306** fora da rede interna; o compose atual não publica a porta.
2. Apenas `app` e `worker` devem conseguir alcançar o banco; restringir a rede/firewall.
3. Usar usuário de aplicação com privilégios mínimos no schema (sem `FILE`, `SUPER`, `PROCESS`).
4. Manter backups automáticos e **testar restore** periodicamente.
5. Monitorar logs de erro, tentativas de acesso e CVEs novas para MariaDB 10.5; aplicar mitigações compensatórias (WAF, rate limit, segmentação).
6. Não introduzir funcionalidades que dependam de recursos exclusivos de MariaDB 11+.
7. Registrar a exceção no inventário de riscos e revisá-la se houver mudança de compliance, incidente ou possibilidade de janela de migração.

Preparação para uma futura migração (sem executar agora):

- O `config/database.php` já teve `NO_AUTO_CREATE_USER` removido; esse modo é específico do MySQL e não é necessário no MariaDB 10.5. A remoção é segura e não bloqueia o runtime atual.
- O schema usa `utf8mb4_unicode_ci` explícito; manter assim. O collation `utf8mb4_0900_ai_ci` é específico do MySQL 8 e não deve ser usado no MariaDB.
- O `sql_mode` atual não habilita `ONLY_FULL_GROUP_BY`; validar consultas se o modo mudar.
- O roteiro futuro seria: dump lógico (`--routines --triggers --events`) → restore em MariaDB 11.8 LTS (EOL 2028-06-04) ou 12.3 LTS → `php artisan migrate` → testes de regressão → janela de manutenção com rollback.
- O E2E usa MariaDB 10.5.29 por compatibilidade com o dump e com a produção 10.5.21; ele **não** valida MariaDB 11.8. A migração futura deve incluir um teste dedicado com a versão alvo.
- Ao trocar o compose local de MySQL 5.7 para MariaDB 10.5, o volume `mysql-data` antigo não é compatível; recrie o volume (`docker compose down -v`) ou faça dump/restore.

---

## 11. Riscos e mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|---|---|---|---|
| Upgrade de Laravel quebrar autenticação/roles | Alta | Alto | Testes de matriz de acesso; staging com dados reais; canário |
| `pdo_dblib`/FreeTDS não funcionar em PHP 8.5 | Média | Alto | Testar Replicado real em staging antes da fase 6; manter fallback PHP 8.4 |
| PDF/LaTeX quebrar com LaraTeX 1.3.2 | Média | Alto | Teste de geração real de PDF; comparar conteúdo; manter versão anterior |
| Symfony Mailer alterar formatação/entrega | Média | Médio | Teste com Mailpit; validar DKIM/SPF/TLS; comparar e-mails |
| MariaDB 10.5 EOL sem patches | Alta | Alto | Risco aceito formalmente (seção 10); não expor 3306; backups; monitoramento; segmentação |
| nginx desatualizado (1.29.8) | Alta | Alto | Corrigido para 1.30.7 pinado; residual `CVE-2026-132779` não aplicável; monitorar 1.30.8+ |
| Testes não cobrirem regressões | Alta | Alto | Implementar lacunas da seção 8 antes das fases 4–6 |
| RCE por template | Baixa | Crítico | Twig sandbox + contexto sanitizado + permissão restrita; monitorar novos filtros/tags |
| Escalada de privilégio continuar aberta | Alta | Crítico | Corrigir SEC-02 imediatamente |
| Dependência de fork pessoal ser comprometida | Baixa/Média | Crítico | Migrar para repositório oficial/tag; revisar diff; 2FA |
| Janela de upgrade longa | Média | Médio | Fases pequenas, branches separados, rollback testado |

---

## 12. Checklist de aceite

Antes de considerar o upgrade concluído:

- [ ] Nenhum runtime EOL (PHP, Laravel, Node, nginx); MariaDB 10.5 é exceção formal aprovada.
- [x] MariaDB 10.5 mantido com as mitigações da seção 10 e exceção registrada.
- [x] nginx em 1.30.7+ pinado por digest.
- [x] `composer audit` sem critical/high não mitigados (0 avisos).
- [x] `npm audit --omit=dev` = 0 e `npm audit` = 0 (Vite; sem árvore de build vulnerável).
- [x] `composer.lock` e `package-lock.json` versionados; builds com `npm ci`.
- [ ] Dependências de branch/fork substituídas por tags oficiais.
- [x] PHPUnit completo verde e determinístico (sem skips por data).
- [x] E2E completo verde (41 verdes); matriz `coverage.yaml` atualizada com os 10 cenários de UI legada.
- [ ] Cobertura PHP medida e acima da meta.
- [ ] Teste com Replicado real em staging.
- [x] PHPUnit e E2E executados contra MariaDB 10.5 (mesma linha da produção 10.5.21).
- [ ] Geração real de PDF e envio real de e-mail validados.
- [x] SEC-01 e SEC-02 corrigidos e com testes de regressão.
- [ ] SEC-03 a SEC-13 corrigidos ou aceitos formalmente com prazo (SEC-03/04/05/06/07/10/11/14 e TrustHosts concluídos; SEC-08 em transição; SEC-09 aceito; SEC-12 fork pendente).
- [x] Cookies `Secure`/`HttpOnly`/`SameSite` e HSTS configurados; CSP em **Report-Only** (enforce pendente).
- [ ] CORS restrito (mantido wildcard por decisão).
- [x] Rate limit global/auth, login e endpoints sensíveis configurado.
- [x] `TrustHosts` habilitado com o host de `APP_URL`.
- [x] Baseline de autenticação por grupo de rotas e testes de acesso.
- [x] Guarda de `APP_DEBUG=false` em produção e `E2E_MODE` restrito a testing.
- [x] Workflow de CI (GitHub Actions com Docker, PHPUnit, auditorias e E2E) criado.
- [ ] Segredos rotacionados e fora do repositório/artefatos.
- [ ] Imagens Docker pinadas por digest e sem critical/high no Trivy.
- [ ] Plano de rollback documentado e testado.

---

## 13. Referências

- OSV.dev — base de vulnerabilidades consultada em 2026-11-23.
- `composer audit` — saída completa no ambiente do projeto.
- `npm audit` — saída completa com Node 22.
- Packagist — versões e restrições de compatibilidade dos pacotes.
- endoflife.date — ciclos de suporte de PHP, Laravel, Symfony, Node.js, MySQL, MariaDB, nginx, Python e Redis.
- Documentação oficial do Laravel — guias de upgrade 8.x → 9.x → 10.x → 11.x → 12.x → 13.x.
- `docs/E2E_TESTS_DOCUMENTATION.md` e `e2e/coverage.yaml`.
- Código do projeto: `app/`, `routes/`, `config/`, `database/migrations/`, `tests/`.

---

## Apêndice A — Lista completa de avisos Composer

| Pacote | Avisos (CVE/GHSA) |
|---|---|
| `guzzlehttp/guzzle` | CVE-2026-69246, CVE-2026-69245, CVE-2026-67354, CVE-2026-67355, CVE-2026-67353, CVE-2026-59883, CVE-2026-67339, CVE-2026-55767, CVE-2026-55568 |
| `laravel/framework` | CVE-2026-114509, CVE-2026-128083, CVE-2026-102279, CVE-2026-48019, CVE-2025-27515, CVE-2024-52301, GHSA-crmm-hgp2-wgrp |
| `league/commonmark` | CVE-2026-71478, CVE-2026-71488, CVE-2025-46734, CVE-2026-105830, CVE-2026-30838, CVE-2026-33347, CVE-2026-86428, CVE-2026-105829, CVE-2024-58382, CVE-2026-86430, CVE-2026-86429, CVE-2026-86434, GHSA-g2gp-3wwq-f4ph, GHSA-jfm3-95jq-q3rf, GHSA-mj63-m3rc-8ppr |
| `symfony/http-foundation` | CVE-2026-108082, CVE-2026-118514, PKSA-pr6q-2w5s-kzzf, PKSA-b2gg-vk1g-6d5q |
| `symfony/http-client` | CVE-2026-121971, CVE-2026-123119 |
| `symfony/http-kernel` | CVE-2022-24894, CVE-2026-123339 |
| `symfony/routing` | CVE-2026-45065, CVE-2026-48784, CVE-2026-118516 |
| `symfony/mime` | CVE-2026-45070 |
| `symfony/process` | CVE-2024-51736, CVE-2026-24739 |
| `league/flysystem` | CVE-2026-102601 |
| `nesbot/carbon` | CVE-2025-22145 |
| `psy/psysh` | CVE-2026-25129 |
| `phpunit/phpunit` | CVE-2026-24765 |

## Apêndice B — Comandos reproduzíveis

```bash
# Testes PHPUnit
docker exec monitoria-app sh -lc 'cd /var/www && php vendor/bin/phpunit --colors=never'

# Testes E2E
./scripts/run_e2e.sh

# Auditoria Composer
docker exec monitoria-app sh -lc 'cd /var/www && composer audit --format=json'

# Auditoria npm (com lock local)
docker run --rm -v "$PWD":/app -w /app node:22-alpine \
  sh -c 'npm audit --json'

# Auditoria npm somente runtime
docker run --rm -v "$PWD":/app -w /app node:22-alpine \
  sh -c 'npm audit --omit=dev --json'

# Versões instaladas
jq -r '.packages[] | "\(.name) \(.version)"' composer.lock
jq -r '.packages | to_entries[] | select(.key|test("node_modules/")) | "\(.key) \(.value.version)"' package-lock.json
```
