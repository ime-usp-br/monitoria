# Arquivo legado de deploy

Este diretório guarda, apenas como referência histórica, artefatos que **não
fazem parte do contrato atual** de deploy. Nada aqui deve ser usado no
Portainer.

| Arquivo | Origem | Motivo do arquivamento |
|---|---|---|
| `docker-compose.monolith-with-db.yml` | `docker-compose.yml` anterior | Continha serviços `mysql` e `redis`, bind mount de código e `depends_on` de banco. Foi substituído pela stack única sem banco. |
| `docker-compose.prod.yml` | raiz | Overlay por ambiente (somente leitura). O contrato agora usa uma única stack, sem variantes por ambiente. |
| `docker-compose.ci.yml` | raiz | Overlay de CI que dependia dos serviços `mysql`/`redis` da stack antiga. O CI passou a usar `docker-compose.test.yml`. |
| `worker-entrypoint.sh` | `docker/worker-entrypoint.sh` | Entrypoint separado que lia `.env`. Foi consolidado em `docker/entrypoint.sh` com `CONTAINER_ROLE=worker`. |
| `php-local.ini` | `docker/php/local.ini` | Configuração PHP montada por bind mount. Foi substituída por `docker/php/php.ini`, copiada na imagem. |

## O que não foi feito

- Nenhum dado de banco foi apagado.
- Nenhuma stack de produção foi alterada.
- Nenhum arquivo `docker-compose.db.yml` existia no projeto; portanto nada
  precisou ser removido desse tipo.

O contrato vigente está em `docker-compose.yml`, `docker-compose.test.yml`,
`.env.example` e `docs/deploy/README.md`.
