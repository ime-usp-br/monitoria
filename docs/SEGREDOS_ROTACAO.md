# Rotação de segredos e verificação de vazamentos

**Escopo:** procedimento para desenvolvimento local, homologação e produção.  
**Última verificação:** 2026-11-24, com gitleaks v8.21.2 em 223 commits — nenhum vazamento encontrado.

## Segredos que precisam ser avaliados

| Segredo | Onde fica | Impacto da rotação |
|---|---|---|
| `APP_KEY` | Portainer (variável da stack) | Invalida sessões, cookies e dados criptografados com a chave antiga |
| `DB_PASSWORD` | Portainer (variável da stack) | Exige atualizar a senha no MariaDB e reiniciar app/worker |
| `REDIS_PASSWORD` | Portainer (variável da stack) | Exige atualizar Redis e reiniciar app/worker |
| `SENHAUNICA_KEY` / `SENHAUNICA_SECRET` | Portainer (variável da stack) | Exige atualizar o cadastro da aplicação no SenhaÚnica |
| `EXTERNAL_REPORT_TOKEN` | Portainer (variável da stack) | Consumidores do relatório externo precisam do novo token |
| `SENTRY_DSN` | Portainer (variável da stack) | Troca simples; não afeta dados da aplicação |
| `MAIL_PASSWORD` | Portainer (variável da stack) | Troca simples; validar envio real após a mudança |
| `CERTIFICATE_SECRETARIA_EMAIL` | Portainer | Não é segredo, mas deve ser conferido no deploy |

## Ordem recomendada

1. Fazer backup do banco e exportar as variáveis atuais da stack no Portainer.
2. Rodar `gitleaks detect --source . --redact` no histórico completo.
3. Rotacionar primeiro os segredos de menor impacto (`SENTRY_DSN`, `MAIL_PASSWORD`, `EXTERNAL_REPORT_TOKEN`).
4. Rotacionar banco e Redis, atualizando a infraestrutura antes de reiniciar a aplicação.
5. Rotacionar `SENHAUNICA_KEY`/`SENHAUNICA_SECRET` em conjunto com o cadastro no SenhaÚnica.
6. Rotacionar `APP_KEY` por último, em janela de manutenção, ciente de que todos os usuários serão deslogados.
7. Reiniciar `app`, `worker` e `web` e validar login, fila, e-mail e relatório externo.
8. Registrar data, responsável e segredos rotacionados.

## Comandos úteis

```bash
# Nova APP_KEY (não grava .env; copie o valor para o Portainer)
docker compose exec -T app php artisan key:generate --show

# Token externo aleatório
openssl rand -hex 32

# Confirmar que nenhum .env local está versionado
git check-ignore -v .env

# Varredura de segredos no histórico
docker run --rm -v "$PWD":/repo zricethezav/gitleaks:v8.21.2 \
  detect --source=/repo --verbose --redact --exit-code 1
```

## Estado atual

- `.env` local está no `.gitignore` e não é versionado; no deploy não existe `.env` no servidor.
- As variáveis e segredos de cada ambiente ficam no Portainer.
- gitleaks não encontrou segredos no histórico completo.
- Nenhum segredo real foi rotacionado automaticamente por este repositório; a execução exige acesso aos ambientes e janela de manutenção.
- Após remover o token por query string (SEC-08), rotacionar `EXTERNAL_REPORT_TOKEN` e atualizar o consumidor.
