# Deploy — Monitoria (Portainer, NPM e banco externo)

Documento operacional único para dev, homologação e produção. A aplicação é a
mesma nos três ambientes; o que muda são as variáveis do Portainer.

## 1. Arquitetura

```
Internet
   │  HTTPS
   ▼
Nginx Proxy Manager (NPM)  ── rede externa `web`
   │  http://monitoria-<env>-web:${WEB_PORT}
   ▼
Serviço `web` (nginx dedicado, imagem monitoria-web)
   │  fastcgi 9000 (rede interna do compose)
   ▼
Serviço `app` (php-fpm, imagem monitoria-app) ── worker (mesma imagem)
   │  DNS/TCP
   ▼
Banco externo por domínio (db-dev / db-homolog / db.<dominio>)
```

Princípios:

- Linux + Docker + Docker Compose v2.
- Portainer é o mecanismo de deploy e de configuração de variáveis.
- NPM é o único ponto de exposição HTTP.
- Rede externa `web` compartilhada entre NPM e a stack.
- Banco sempre externo à stack, acessado por domínio próprio.
- Uma única stack de aplicação (`docker-compose.yml`) para os três ambientes.
- Dev, homologação e produção diferem apenas em valores de variáveis.
- Nenhum segredo no repositório; nenhuma porta publicada pela aplicação.
- Logs em stdout/stderr; healthcheck em `/up`; `restart: unless-stopped`.

## 2. Ambientes

| Ambiente | Branch alvo | Infra | Stack Portainer (sugerida) | Alias na rede `web` | Banco (domínio) | Domínio público |
|---|---|---|---|---|---|---|
| dev | `homolog/laravel-13-hardening` (branch atual) | Portainer + NPM + banco central de dev | `monitoria-dev` | `monitoria-dev-web` | `db-dev.<dominio>` — **não identificado** | `dev.<dominio>` — **não identificado** |
| homologação | `homolog/laravel-13-hardening` (branch atual) | Portainer + NPM + banco central de homologação | `monitoria-homolog` | `monitoria-homolog-web` | `db-homolog.<dominio>` — **não identificado** | `homolog.<dominio>` — **não identificado** |
| produção | `homolog/laravel-13-hardening` (alvo desta padronização) | Portainer + NPM + banco central de produção | `monitoria-prod` | `monitoria-prod-web` | `db.<dominio>` — **não identificado** | `https://monitoria.ime.usp.br` (README do projeto) |

Observações:

- `homolog/laravel-13-hardening` é a **branch atual** e, por regra, a branch
  alvo desta padronização. `main` é a referência estável.
- A produção atual pode estar em outra branch e/ou outra infraestrutura. Isso
  **não foi confirmado** e **não foi alterado**. Ver seção 11.
- Os nomes reais dos domínios de banco e de dev/homologação não estão no
  repositório. Substitua `<dominio>` pelos valores fornecidos pela
  infraestrutura.

### Mapeamento de ambiente

| Conceito | Variável | dev | homologação | produção |
|---|---|---|---|---|
| Ambiente canônico de deploy | `DEPLOY_ENV` | `dev` | `homolog` | `prod` |
| Ambiente nativo do Laravel | `APP_ENV` | `local` | `production` | `production` |
| Debug | `APP_DEBUG` | `true` | `false` | `false` |
| Migrations no boot | `RUN_MIGRATIONS` | `true` | `true` | `true` |
| Seeders no boot | `RUN_SEEDERS` | `true` | `false` | `false` |
| Aquecimento de cache | `WARM_CACHE` | `false` | `true` | `true` |

O entrypoint aceita também `APP_ENV=dev|homolog|prod` e normaliza para o valor
nativo do Laravel. Para comandos via `docker compose exec`, prefira o valor
nativo (`local`/`production`) para que o Laravel veja o ambiente correto.

## 3. Variáveis

O contrato completo, comentado e copiável para o Portainer, é `.env.example`.
Não crie `.env.dev`, `.env.homolog` ou `.env.prod`. O `.env` local é opcional e
não é usado no deploy.

### 3.1 Identidade, ambiente e rede

| Variável | Obrigatória | dev | homologação | produção | Secreta | Observações |
|---|---|---|---|---|---|---|
| `APP_PROJECT` | sim | `monitoria` | `monitoria` | `monitoria` | não | Prefixo do alias e dos backups. |
| `DEPLOY_ENV` | sim | `dev` | `homolog` | `prod` | não | Usado por scripts, labels e retenção. |
| `APP_ENV` | sim | `local` | `production` | `production` | não | Nativo do Laravel. |
| `APP_DEBUG` | sim | `true` | `false` | `false` | não | Nunca `true` em homolog/prod. |
| `APP_KEY` | sim | própria do dev | própria de homolog | própria de prod | **sim** | `php artisan key:generate --show`. |
| `APP_URL` | sim | `https://dev.<dominio>` | `https://homolog.<dominio>` | `https://monitoria.ime.usp.br` | não | URL pública com HTTPS. |
| `TZ` | sim | `America/Sao_Paulo` | `America/Sao_Paulo` | `America/Sao_Paulo` | não | |
| `WEB_PORT` | não | `8080` | `8080` | `8080` | não | Porta interna do nginx; o NPM usa a mesma. |
| `TRUSTED_PROXIES` | sim | CIDRs do NPM/dev | CIDRs do NPM | CIDRs do NPM | não | Restrinja ao NPM quando possível. |
| `FORCE_HTTPS` | não | `false` | `true` | `true` | não | NPM termina TLS. |

### 3.2 Banco externo

| Variável | Obrigatória | dev | homologação | produção | Secreta | Observações |
|---|---|---|---|---|---|---|
| `DB_CONNECTION` | sim | `mysql` | `mysql` | `mysql` | não | MariaDB usa o driver `mysql`. |
| `DB_HOST` | sim | `db-dev.<dominio>` | `db-homolog.<dominio>` | `db.<dominio>` | não | **Domínio**, nunca `db`/`mysql`/`localhost`. |
| `DB_PORT` | sim | `3306` | `3306` | `3306` | não | Porta do banco externo. |
| `DB_DATABASE` | sim | banco do projeto | banco do projeto | banco do projeto | não | Definido pela infra. |
| `DB_USERNAME` | sim | usuário do projeto | usuário do projeto | usuário do projeto | não | Definido pela infra. |
| `DB_PASSWORD` | sim | senha do dev | senha de homolog | senha de prod | **sim** | Definida pela infra. |
| `DATABASE_URL` | não | vazio | vazio | vazio | **sim** | Alternativa ao conjunto `DB_*`. |
| `DB_WAIT_TIMEOUT` | não | `120` | `120` | `120` | não | Timeout do entrypoint. |
| `DEPLOY_READY_TIMEOUT` | não | `180` | `180` | `180` | não | Tempo que o worker espera o serviço web migrar/semear. |
| `MYSQL_ATTR_SSL_CA` | não | vazio | vazio | CA se exigido | não | TLS do banco. |

O projeto **não** cria banco, usuário, permissões, DNS ou backup central. Isso é
responsabilidade da infraestrutura de cada ambiente.

### 3.3 Aplicação, fila, cache e integrações

| Variável | Obrigatória | Observações |
|---|---|---|
| `LOG_CHANNEL` | sim | `stderr` para logs em stdout/stderr. |
| `LOG_LEVEL` | não | `debug` em dev; `info`/`warning` em homolog/prod. |
| `CACHE_DRIVER` | não | `file` (padrão do projeto). |
| `SESSION_DRIVER` | não | `file`. |
| `SESSION_ENCRYPT` | não | `true`. Cookie seguro é automático com `APP_ENV=production`. |
| `QUEUE_CONNECTION` | não | `database`; o worker usa a mesma conexão. |
| `BROADCAST_DRIVER` | não | `log`. |
| `FILESYSTEM_DRIVER` | não | `local`; mídia em `storage/app`. |
| `REDIS_URL` / `REDIS_HOST` / `REDIS_PORT` | não | Só se houver Redis externo. A stack não sobe Redis. |
| `MAIL_*` | não | SMTP por ambiente. |
| `SENHAUNICA_KEY` / `SENHAUNICA_SECRET` | sim para login USP | Segredos do cadastro da aplicação. |
| `SENHAUNICA_CALLBACK_ID` | sim para login USP | |
| `SENHAUNICA_DEV` | não | `no` em homolog/prod. |
| `REPLICADO_*` | sim para integração | Sybase externo; não é o banco do projeto. |
| `CERTIFICATE_SECRETARIA_EMAIL` | sim para certificados | Não é segredo. |
| `EXTERNAL_REPORT_TOKEN` | sim para relatório externo | **Segredo**. |
| `SENTRY_LARAVEL_DSN` / `SENTRY_ENVIRONMENT` | não | Observabilidade; DSN é segredo. |
| `BACKUP_DIR` | não | `storage/backups` no container. |
| `BACKUP_RETENTION_DAYS` | não | `14`. |

`CONTAINER_ROLE` é definido pelo compose (`web` para `app`, `worker` para
`worker`) e não deve ser configurado no Portainer.

## 4. Checklist do Portainer

1. Criar a rede externa `web` no host (se ainda não existir):
   ```sh
   docker network create web
   ```
2. Criar a stack da aplicação usando o `docker-compose.yml` do repositório.
   Não use `docker-compose.prod.yml`; ele foi arquivado.
3. Colar no campo de variáveis da stack o conteúdo de `.env.example`,
   substituindo todos os placeholders. **Não** envie `.env` para o servidor.
4. Definir `DEPLOY_ENV`, `APP_ENV`, `APP_DEBUG`, `APP_KEY` e `APP_URL`.
5. Configurar `DB_HOST` com o domínio do banco daquele ambiente
   (`db-dev.<dominio>`, `db-homolog.<dominio>` ou `db.<dominio>`).
6. Confirmar que o domínio do banco resolve dentro do container (seção 7).
7. Configurar o Proxy Host no NPM (seção 5).
8. Fazer deploy e aguardar o healthcheck `/up`.
9. Validar `RUN_MIGRATIONS`; o serviço `app` migra uma única vez no boot.
10. Rodar smoke test e registrar stack, domínio, branch, imagem e tag.

Nome sugerido da stack: `monitoria-<env>`. O alias na rede `web` é
`monitoria-<env>-web` (via `APP_PROJECT` + `DEPLOY_ENV`).

## 5. Checklist do NPM

- Proxy Host por ambiente apontando para `http://monitoria-<env>-web:8080`
  (ou o valor de `WEB_PORT`).
- Nunca aponte para nome de container instável (`monitoria-app-1` etc.).
- TLS emitido/renovado no NPM; redirecionar HTTP → HTTPS.
- HSTS habilitado após validar TLS.
- `client_max_body_size` compatível com os uploads (atual: 4M no nginx da
  aplicação; ajuste no NPM se necessário).
- Timeouts coerentes com relatórios/LaTeX.
- Encaminhar `X-Forwarded-For`, `X-Forwarded-Proto`, `X-Forwarded-Host` e
  `X-Forwarded-Port`; remover esses headers de requisições externas.
- WebSocket habilitado se a aplicação passar a usá-lo.
- O NPM **não** expõe banco. O banco tem DNS e porta próprios, fora do NPM.

A aplicação confia nos proxies via `TRUSTED_PROXIES` (bootstrap/app.php). Em
produção, restrinja aos IPs/CIDRs do NPM.

## 6. Banco externo: solicitação e validação

A infraestrutura de cada ambiente é responsável por:

- criar o banco e o usuário do projeto;
- conceder permissões;
- publicar o DNS do banco;
- configurar TLS, quando exigido;
- executar o backup central.

Procedimento para solicitar à infra:

1. Informar projeto (`monitoria`), ambiente (`dev`/`homolog`/`prod`) e
   responsável.
2. Solicitar banco, usuário e senha (ou secret no gerenciador da infra).
3. Solicitar o domínio DNS interno do banco e a porta.
4. Confirmar que o container da aplicação resolve esse domínio.
5. Registrar host/porta/banco/usuário no Portainer; a senha vai como segredo.
6. Validar com `make db-check`.

Validação de DNS dentro do container:

```sh
docker compose exec -T app getent hosts "$DB_HOST"
docker compose exec -T app php -r 'var_dump(gethostbyname(getenv("DB_HOST")));'
make db-check
```

Se o domínio não resolver, a infra deve fornecer DNS interno ou endereço
acessível. **Não** use `extra_hosts` em produção nem rede Docker compartilhada
com o banco.

## 7. Migrations, healthcheck e smoke test

Migrations:

- O serviço `app` roda `php artisan migrate --force` no entrypoint quando
  `RUN_MIGRATIONS=true` e `CONTAINER_ROLE=web`.
- O worker nunca roda migrations nem seeders.
- Para rodar manualmente:
  ```sh
  make migrate
  # ou
  docker compose exec -T app php artisan migrate --force
  ```
- Seeders: `make seed` (bloqueado quando `DEPLOY_ENV=prod`).

Healthcheck:

```sh
make health
# ou, dentro do container web:
docker compose exec -T web sh /usr/local/bin/healthcheck.sh
```

O endpoint real é `/up` (Laravel). O healthcheck do compose consulta
`http://127.0.0.1:${WEB_PORT}/up` dentro do nginx; isso valida nginx + php-fpm +
aplicação.

Smoke test mínimo após deploy:

```sh
curl -fsS https://<dominio-publico>/up
curl -fsSI https://<dominio-publico>/ | head
```

## 8. Backup e restore

Scripts:

- `scripts/backup-db.sh`
- `scripts/restore-db.sh`

Alvos:

```sh
make db-check
make backup-db
make restore-db FILE=storage/backups/monitoria-<env>-<db>-YYYYmmdd-HHMMSS.sql.gz
```

Regras:

- O backup lê `DB_*`/`DATABASE_URL` e usa o banco externo por domínio.
- A senha vai por `MYSQL_PWD`/`PGPASSWORD`, nunca por argumento de linha de
  comando.
- O arquivo é compactado (`.sql.gz`) e recebe checksum `.sha256`.
- Retenção por `BACKUP_RETENTION_DAYS` (padrão 14).
- O restore exige confirmação; em produção exige também `--confirm-prod`.
- O restore não apaga o backup.
- O backup do projeto **complementa** o backup central da infraestrutura; não o
  substitui.

Política sugerida (a confirmar com a infra):

- RPO: backup central diário + backup manual antes de migrations de risco.
- RTO: até 4 horas para restaurar e validar a aplicação.
- Teste de restore: trimestral, em ambiente de homologação, a partir do
  checksum validado.

Os valores de RPO/RTO **não foram formalmente identificados** no repositório;
os acima são referência operacional.

## 9. Rotação de segredos

1. Atualizar o valor no Portainer (não no repositório).
2. Reiniciar `app` e `worker` (e `web` se necessário).
3. Validar login, fila, e-mail, relatório externo e healthcheck.
4. Registrar data, responsável e segredo rotacionado.

`APP_KEY`: rotacionar invalida sessões/cookies e dados criptografados com a
chave antiga; fazer em janela de manutenção. Consulte
`docs/SEGREDOS_ROTACAO.md`.

## 10. Branches

- A branch atual é a branch alvo: `homolog/laravel-13-hardening`.
- `main` é a referência estável.
- As demais branches são temporárias.
- Não existe `docs/deploy/BRANCHES.md` nem documento vivo de mapeamento.

## 11. Produção temporária e cutover

**Produção temporária não alterada.** A URL pública conhecida é
`https://monitoria.ime.usp.br`, mas a branch e a infraestrutura exatas da
produção atual **não foram identificadas** apenas pelo repositório. Nenhuma
alteração foi feita nessa produção.

Se a produção estiver em outra branch e/ou outra infraestrutura, trate-a como
temporária. Plano de cutover (somente após autorização da infra):

1. Congelar escrita e gerar backup do banco de produção.
2. Validar migrations e smoke test em homologação com a branch alvo.
3. Publicar a imagem/tag da branch alvo e criar a stack `monitoria-prod` no
   Portainer com as variáveis de produção.
4. Validar DNS do banco, healthcheck e `/up` na nova stack.
5. Alterar o Proxy Host do NPM para `monitoria-prod-web:8080`.
6. Rodar smoke test completo (login USP, filas, e-mail, relatórios).
7. Rollback: reapontar o NPM para a stack antiga; se necessário, restaurar o
   backup de produção com `scripts/restore-db.sh --confirm-prod`.

Não trate a produção temporária como produção canônica.

## 12. Troubleshooting

| Sintoma | Causa provável | Ação |
|---|---|---|
| `ERRO: DB_HOST='...' não é permitido` | `DB_HOST` é `db`/`mysql`/`localhost`/nome de serviço | Usar o domínio do banco externo. |
| `banco ... não respondeu` | DNS, firewall, credenciais ou banco não criado | Validar `getent hosts`, `make db-check`, pedir à infra. |
| `/up` retorna 502 | `app` não subiu (migration/config) | `docker compose logs app`; corrigir e reiniciar. |
| Healthcheck falha | nginx sem `app` saudável ou porta errada | Conferir `WEB_PORT` e logs de `app`/`web`. |
| NPM retorna 502 | alias/porta errados ou rede `web` ausente | Conferir alias `monitoria-<env>-web` e `docker network inspect web`. |
| Permissão em `storage` | volume com dono incorreto | Conferir o volume `app-storage`; o container roda como `www-data`. |
| Configuração antiga após trocar variável | cache de config do Laravel | Reiniciar `app` (com `WARM_CACHE=true` o cache é regerado). |
| Worker não processa | fila/DB ou worker parado | `docker compose logs worker`; validar `QUEUE_CONNECTION`. |

## 13. O que é manual

- Criação das stacks no Portainer.
- Preenchimento das variáveis/segredos no Portainer.
- Proxy Hosts, TLS e HSTS no NPM.
- DNS dos domínios públicos e do banco.
- Banco, usuário, permissões e backup central na infraestrutura.
- Monitoramento e alertas.
- Autorização e execução do cutover de produção.

## 14. Não identificado no repositório

- Domínios reais de dev e homologação.
- Domínios reais dos bancos por ambiente.
- Branch e infraestrutura exatas da produção atual.
- RPO/RTO formais da infraestrutura.
- Responsáveis e janela de cutover.

Substitua esses itens pelas informações fornecidas pela infraestrutura antes
do deploy.
