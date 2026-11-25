# Segurança de deploy

O contrato operacional completo está em `docs/deploy/README.md`. Este documento
resume as decisões de segurança.

## Modelo

- A stack de aplicação é única (`docker-compose.yml`) para dev, homologação e
  produção. Não existe overlay de produção nem variante por ambiente.
- As variáveis vêm do **Portainer** (ou do shell local). Não existe `.env` no
  servidor.
- O banco é externo, acessado por domínio (`DB_HOST`/`DATABASE_URL`), fora da
  rede Docker da aplicação.
- O NPM é o único ponto HTTP e não expõe banco.
- A aplicação não publica portas, não monta o código-fonte e não sobe banco.

## Pré-requisitos

A imagem de deploy já contém:

- dependências de produção do Composer;
- assets compilados pelo Vite;
- runtime PHP com as extensões necessárias.

O container **não** instala dependências, não compila assets e não cria `.env`
em runtime. O entrypoint é fail-closed:

- exige `APP_KEY`, `APP_URL`, `DB_HOST`, `DB_PASSWORD` etc.;
- recusa `DB_HOST` igual a `db`, `mysql`, `mariadb`, `postgres`, `localhost`
  ou nome de serviço Compose;
- espera o banco externo por domínio com timeout;
- falha com mensagem clara se o banco não responder.

## Variáveis mínimas de produção

No Portainer, para `DEPLOY_ENV=prod`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` definida e rotacionável
- `APP_URL` com `https://`
- `DB_HOST` com o domínio do banco de produção
- `DB_PASSWORD` forte, definida pela infraestrutura
- `TRUSTED_PROXIES` restrito ao NPM
- `FORCE_HTTPS=true`
- `SESSION_ENCRYPT=true`
- `RUN_MIGRATIONS=true`, `RUN_SEEDERS=false`, `WARM_CACHE=true`
- `EXTERNAL_REPORT_TOKEN` forte
- `SENHAUNICA_KEY`/`SENHAUNICA_SECRET` válidos

O cookie seguro é ativado automaticamente quando `APP_ENV=production`.

## Proxy reverso

O NPM deve enviar `X-Forwarded-For`, `X-Forwarded-Proto`,
`X-Forwarded-Host` e `X-Forwarded-Port`, removendo esses headers de requisições
externas. A aplicação confia nos proxies configurados em `TRUSTED_PROXIES`
(`bootstrap/app.php`).

## Migrações

As migrações rodam no entrypoint do serviço `app` quando
`RUN_MIGRATIONS=true` e `CONTAINER_ROLE=web`. O worker nunca migra nem semeia.
Faça backup e teste o rollback antes de aplicar. Não use
`migrate --force || true`.

## Rotação de segredos

Atualize o valor no Portainer, reinicie `app` e `worker` e valide login, fila,
e-mail, relatório externo e healthcheck. Consulte `docs/SEGREDOS_ROTACAO.md`.

## Riscos residuais aceitos

- MariaDB 10.5 está em EOL e foi mantido por decisão de negócio.
- O tema `uspdev/laravel-usp-theme` permanece em fork pinado até migração para
  tag oficial.
- O token do relatório externo ainda aceita query string durante a transição.
