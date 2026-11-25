SHELL := /bin/bash

COMPOSE ?= docker compose
COMPOSE_FILE ?= docker-compose.yml
TEST_COMPOSE_FILE ?= docker-compose.test.yml
COMPOSE_PROJECT_NAME ?= monitoria
CONFIG_ENV_FILE ?= .env.example

DEPLOY_COMPOSE = $(COMPOSE) -p $(COMPOSE_PROJECT_NAME) -f $(COMPOSE_FILE)
TEST_COMPOSE = $(COMPOSE) -f $(TEST_COMPOSE_FILE)

.PHONY: help build up down ps logs migrate seed test health config config-test \
        prod-check shell db-check backup-db restore-db

help:
	@echo "Alvos disponíveis:"
	@echo "  make up           Sobe a stack única de aplicação (docker-compose.yml)"
	@echo "  make down         Derruba a stack de aplicação"
	@echo "  make migrate      Roda migrations no serviço app"
	@echo "  make seed         Roda seeders (bloqueado em prod)"
	@echo "  make test         Executa a suíte isolada (docker-compose.test.yml)"
	@echo "  make logs         Acompanha logs dos serviços"
	@echo "  make ps           Status dos serviços"
	@echo "  make health       Valida /up através do nginx"
	@echo "  make config       Valida docker compose config (usa .env.example)"
	@echo "  make config-test  Valida docker-compose.test.yml"
	@echo "  make prod-check   Verifica o contrato de deploy"
	@echo "  make shell        Shell no container app"
	@echo "  make db-check     Valida a conexão com o banco externo"
	@echo "  make backup-db    Executa scripts/backup-db.sh"
	@echo "  make restore-db   Executa scripts/restore-db.sh (FILE=...)"
	@echo ""
	@echo "Variáveis: CONFIG_ENV_FILE=.env para validar o ambiente local."

build:
	$(DEPLOY_COMPOSE) build

up:
	$(DEPLOY_COMPOSE) up -d --build

down:
	$(DEPLOY_COMPOSE) down --remove-orphans

ps:
	$(DEPLOY_COMPOSE) ps

logs:
	$(DEPLOY_COMPOSE) logs -f --tail=200

migrate:
	$(DEPLOY_COMPOSE) exec -T app php artisan migrate --force

seed:
	$(DEPLOY_COMPOSE) exec -T app sh -c 'if [ "$${DEPLOY_ENV}" = "prod" ]; then echo "Seed bloqueado em produção." >&2; exit 1; fi; php artisan db:seed --force'

test:
	@$(TEST_COMPOSE) run --rm --build test; \
	rc=$$?; \
	$(TEST_COMPOSE) down -v --remove-orphans; \
	exit $$rc

health:
	$(DEPLOY_COMPOSE) exec -T web sh /usr/local/bin/healthcheck.sh

config:
	$(COMPOSE) --env-file $(CONFIG_ENV_FILE) -p $(COMPOSE_PROJECT_NAME) -f $(COMPOSE_FILE) config -q
	@echo "OK: $(COMPOSE_FILE) válido com $(CONFIG_ENV_FILE)."

config-test:
	$(TEST_COMPOSE) config -q
	@echo "OK: $(TEST_COMPOSE_FILE) válido."

prod-check:
	bash scripts/check-deploy-contract.sh $(COMPOSE_FILE)

shell:
	$(DEPLOY_COMPOSE) exec app sh

db-check:
	$(DEPLOY_COMPOSE) exec -T app sh -c 'DB_WAIT_TIMEOUT=$${DB_WAIT_TIMEOUT:-15} sh scripts/wait-for-db.sh'

backup-db:
	$(DEPLOY_COMPOSE) exec -T app bash scripts/backup-db.sh

restore-db:
	@if [ -z "$(FILE)" ]; then echo "Uso: make restore-db FILE=storage/backups/arquivo.sql.gz" >&2; exit 2; fi
	$(DEPLOY_COMPOSE) exec app bash scripts/restore-db.sh "$(FILE)"
