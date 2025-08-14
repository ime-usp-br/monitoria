
<br />
<div align="center">
  <a href="https://monitoria.ime.usp.br">
    <img src="storage/app/images/logo_ime_vert.jpg" alt="Logo" width="150" height="150">
  </a>

  <h3 align="center">Sistema de Monitoria</h3>

</div>


## Sobre o Projeto

Sistema para gerenciar a distribuição das bolsas de monitoria do IME. 

<br />

## Implementação

Clone o repositório

    git clone https://github.com/ime-usp-br/monitoria.git
    
Instale as dependências

    composer install
    
Restaure o arquivo de configuração

    cp .env.example .env
    
Além de configurar o banco de dados e o serviço de e-mail, você precisara configurar <a href="https://github.com/uspdev/senhaunica-socialite">senhaunica-socialite</a>

    # SENHAUNICA-SOCIALITE ######################################
    # https://github.com/uspdev/senhaunica-socialite
    SENHAUNICA_KEY=
    SENHAUNICA_SECRET=
    SENHAUNICA_CALLBACK_ID=

    # URL do servidor oauth no ambiente de dev (default: no)
    #SENHAUNICA_DEV="https://dev.uspdigital.usp.br/wsusuario/oauth"

    # URL do servidor oauth para uso com senhaunica-faker
    #SENHAUNICA_DEV="http://127.0.0.1:3141/wsusuario/oauth"

    # Esses usuários terão privilégios especiais
    #SENHAUNICA_ADMINS=11111,22222,33333
    #SENHAUNICA_GERENTES=4444,5555,6666

    # Se os logins forem limitados a usuários cadastrados (onlyLocalUsers=true),
    # pode ser útil cadastrá-los aqui.
    #SENHAUNICA_USERS=777,888

    # Se true, os privilégios especiais serão revogados ao remover da lista (default: false)
    #SENHAUNICA_DROP_PERMISSIONS=true

    # Habilite para salvar o retorno em storage/app/debug/oauth/ (default: false)
    #SENHAUNICA_DEBUG=true

    # SENHAUNICA-SOCIALITE ######################################
    
Configure as variaveis do <a href="https://github.com/uspdev/replicado">replicado</a>

    REPLICADO_HOST=
    REPLICADO_PORT=
    REPLICADO_DATABASE=
    REPLICADO_USERNAME=
    REPLICADO_PASSWORD=
    REPLICADO_SYBASE=
    
Gere uma nova chave

    php artisan key:generate
    
Crie as tabelas do banco de dados

    php artisan migrate --seed
    
Instale o supervisor

    apt install supervisor
    
Copie o arquivo de configuração do supervisor, lembre-se de alterar o diretório do projeto

    cp supervisor.conf.example /etc/supervisor/conf.d/laravel-worker.conf
    

Indique ao supervisor que há um novo arquivo de configuração

    supervisorctl reread
    supervisorctl update
    
Informe no arquivo .env que o supervisor foi configurado

    IS_SUPERVISOR_CONFIG=true

Instale os pacotes LaTeX para gerar os relatórios

    sudo apt install texlive texlive-latex-extra texlive-lang-portuguese

## Comandos Artisan

### Comparação de Turmas

Compare os professores das turmas entre o banco local e o sistema Replicado:

    php artisan report:compare-classes

**Opções:**
- `--format=table|json|csv` - Formato de saída (padrão: table)
- `--schoolterm=ID` - ID do período letivo específico (padrão: período aberto)
- `--output=caminho` - Salvar relatório em arquivo
- `--detailed` - Mostrar diferenças detalhadas
- `--only-instructor-diffs` - Mostrar apenas diferenças de professores
- `--show-instructor-details` - Mostrar códigos e nomes completos dos professores

**Exemplos:**
```bash
# Relatório básico em tabela
php artisan report:compare-classes

# Relatório detalhado em JSON
php artisan report:compare-classes --format=json --detailed

# Apenas diferenças de professores
php artisan report:compare-classes --only-instructor-diffs

# Salvar em arquivo CSV
php artisan report:compare-classes --format=csv --output=relatorio.csv
```

### Sincronização de Professores

Sincronize os professores das turmas entre o banco local e o sistema Replicado de forma aditiva (apenas adiciona, nunca remove):

    php artisan sync:class-instructors

**Opções:**
- `--dry-run` - Visualizar mudanças sem aplicá-las (retorna JSON para frontend)
- `--schoolterm=ID` - ID do período letivo específico (padrão: período aberto)
- `--class=ID` - Sincronizar apenas uma turma específica

**Exemplos:**
```bash
# Visualizar mudanças antes de aplicar (modo dry-run)
php artisan sync:class-instructors --dry-run

# Aplicar sincronização no período aberto
php artisan sync:class-instructors

# Sincronizar período específico
php artisan sync:class-instructors --schoolterm=21

# Sincronizar apenas uma turma
php artisan sync:class-instructors --class=123
```

**Resposta JSON do comando de sincronização:**

*Modo dry-run (--dry-run):*
```json
{
  "status": "preview",
  "school_term": {
    "id": 21,
    "period": "2° Semestre",
    "year": 2025
  },
  "summary": {
    "classes_analyzed": 204,
    "instructors_to_add": 47,
    "classes_affected": 46
  },
  "changes": [
    {
      "class_id": 123,
      "class_code": "2025222",
      "class_name": "Introdução à Computação",
      "current_instructors": [...],
      "instructors_to_add": [...]
    }
  ],
  "errors": []
}
```

*Modo produção (aplicar mudanças):*
```json
{
  "status": "completed",
  "school_term": {
    "id": 21,
    "period": "2° Semestre",
    "year": 2025
  },
  "summary": {
    "classes_processed": 46,
    "instructors_created": 8,
    "relationships_created": 47,
    "execution_time": "2.5s"
  },
  "results": [...],
  "errors": []
}
```

**⚠️ Importante:** O comando de sincronização é **aditivo apenas** - ele adiciona novos professores encontrados no Replicado, mas nunca remove professores existentes no banco local, mesmo que não estejam mais listados no Replicado.
