# Segurança de deploy

## Pré-requisitos de produção

O entrypoint de produção é fail-closed. O container **não cria** `.env`, não instala dependências e não compila assets em runtime.

Antes do deploy:

```sh
composer install --no-dev --classmap-authoritative --no-interaction
npm ci
npm run build
```

O `.env` de produção deve conter, no mínimo:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` definida e rotacionável
- `APP_URL` com `https://`
- `DB_PASSWORD` forte e diferente de `root`
- `TRUSTED_PROXIES` com os IPs/CIDRs do reverse proxy
- `SESSION_SECURE_COOKIE=true`
- `SESSION_ENCRYPT=true`
- `EXTERNAL_REPORT_TOKEN` forte

O proxy reverso deve enviar `X-Forwarded-For`, `X-Forwarded-Proto` e
`X-Forwarded-Host`, removendo esses headers de requisições externas.

## Compose de produção

Use o overlay de produção para montar o código como somente leitura e manter
`storage` e `bootstrap/cache` em volumes graváveis:

```sh
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

O compose base mantém o bind mount gravável para desenvolvimento e CI. Nunca
use o compose base em produção.

## Desenvolvimento local

Como o entrypoint é fail-closed, ambientes locais com `APP_ENV=local` precisam
definir explicitamente:

```sh
ALLOW_DEV_ENV=true docker compose up -d
```

## Migrações

As migrações rodam no entrypoint e falham o boot se houver erro. Faça backup e
teste o rollback antes de aplicar. Não use `migrate --force || true`.

## Rotação de segredos

Depois do deploy, rotacione `APP_KEY`, senhas de banco, token do relatório
externo, credenciais SMTP e chaves da Senhaunica. Consulte
`docs/SEGREDOS_ROTACAO.md`.

## Riscos residuais aceitos

- MariaDB 10.5 está em EOL e foi mantido por decisão de negócio.
- O tema `uspdev/laravel-usp-theme` permanece em fork pinado até migração para
  tag oficial.
- O token do relatório externo ainda aceita query string durante a transição.
