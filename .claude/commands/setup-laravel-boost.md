# Setup Laravel Boost com Claude

Você vai instalar e configurar o Laravel Boost, um servidor MCP (Model Context Protocol) com mais de 15 ferramentas especializadas para desenvolvimento Laravel com Claude.

## O que é Laravel Boost?

Laravel Boost é um servidor MCP oficial do Laravel, desenvolvido especificamente para integração com Claude (Claude Code), equipado com ferramentas especializadas para otimizar workflows de codificação assistida por Claude, incluindo:

- **15+ ferramentas MCP especializadas** para consultar banco de dados, inspecionar config/rotas/models, executar código via Tinker e buscar logs
- **API de Documentação** com mais de 17.000 informações específicas do Laravel com busca semântica
- **Guidelines para Claude** personalizados para Laravel, Livewire, Filament e outros pacotes do ecossistema
- **Contexto com versão** que permite ao Claude entender sua versão do PHP, engine de banco de dados e pacotes instalados
- **Suporte a guidelines customizados** através de arquivos `.blade.php` ou `.md` em `.ai/guidelines/` que são carregados automaticamente pelo Claude

## Instruções

Execute as seguintes etapas em ordem:

### 1. Verificação Inicial

- Confirme que estamos em um projeto Laravel (verifique se existe `artisan`, `composer.json` com Laravel)
- Confirme que estamos executando no Claude Code (você é o Claude Code)
- Pergunte ao usuário se ele quer prosseguir com a instalação do Laravel Boost

### 2. Instalação do Laravel Boost

Instale o Laravel Boost como dependência de desenvolvimento:

```bash
composer require laravel/boost --dev
```

### 3. Executar o Instalador

Execute o comando de instalação que configura o servidor MCP e guidelines para Claude:

```bash
php artisan boost:install
```

Este comando vai:

- Configurar o servidor MCP para integração com Claude
- Criar os guidelines otimizados para Claude entender o projeto
- Detectar pacotes instalados (Laravel, Livewire, Filament, etc.)
- Configurar automaticamente a integração com Claude Code

### 4. Verificar Instalação do MCP Server

Verifique se o servidor MCP foi registrado corretamente:

```bash
claude mcp list
```

Você deve ver `laravel-boost` na lista de servidores MCP.

**Se o servidor NÃO aparecer**, registre manualmente:

```bash
claude mcp add -s local -t stdio laravel-boost php artisan boost:mcp
```

### 5. Configurar Auto-Update no composer.json

Adicione o comando de atualização automática para manter as guidelines sincronizadas com os pacotes instalados.

Leia o `composer.json` e adicione na seção `scripts`:

```json
"scripts": {
    "post-update-cmd": [
        "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
        "@php artisan vendor:publish --tag=laravel-assets --ansi --force",
        "@php artisan boost:update --ansi"
    ]
}
```

**Importante:** Se já existir `post-update-cmd`, apenas adicione `"@php artisan boost:update --ansi"` ao array existente.

### 6. Instalar Guidelines Padrão (Automático)

Informe ao usuário que você vai instalar os **guidelines padrão** para Laravel:

```
📥 Instalando guidelines essenciais para desenvolvimento Laravel...

Estes guidelines ensinam o Claude sobre:
- Arquitetura (Actions, Services, DTOs)
- Estilo de código e convenções (PSR-12, Laravel Pint)
- Padrões para Models Eloquent
- Padrões do Filament (Resources, Forms, Tables)
- Regras de validação (Form Requests SEMPRE obrigatórios)
- API Resources (SEMPRE usar, exceto respostas muito simples)
- Testes com Pest (expectations encadeadas, factories)
- Commits (Conventional Commits)

Total: ~30 KB de guidelines essenciais e otimizados para performance.
```

**Crie o diretório e faça download dos guidelines automaticamente:**

```bash
mkdir -p .ai/guidelines

# URLs do repositório com guidelines
REPO="danie1net0/laravel-claude-commands"
BRANCH="main"
BASE_URL="https://raw.githubusercontent.com/$REPO/$BRANCH/guidelines"

echo "📥 Baixando guidelines..."

# Download dos guidelines essenciais
curl -fsSL "$BASE_URL/architecture.md" -o .ai/guidelines/architecture.md
curl -fsSL "$BASE_URL/code-style.md" -o .ai/guidelines/code-style.md
curl -fsSL "$BASE_URL/models.md" -o .ai/guidelines/models.md
curl -fsSL "$BASE_URL/filament.md" -o .ai/guidelines/filament.md
curl -fsSL "$BASE_URL/validation.md" -o .ai/guidelines/validation.md
curl -fsSL "$BASE_URL/api.md" -o .ai/guidelines/api.md
curl -fsSL "$BASE_URL/tests.md" -o .ai/guidelines/tests.md
curl -fsSL "$BASE_URL/commits.md" -o .ai/guidelines/commits.md

echo "✅ Guidelines instalados!"
```

**Liste os guidelines instalados:**

```bash
ls -lh .ai/guidelines/
```

**Mostre ao usuário um resumo:**

```
✅ 8 guidelines essenciais instalados com sucesso!

📄 architecture.md (3.8 KB)
   - Actions, Services, DTOs
   - Regras de decisão (onde colocar código)

📄 code-style.md (3.4 KB)
   - PSR-12, nomenclatura, type hints

📄 models.md (4.6 KB)
   - Relationships, Scopes, Casts

📄 filament.md (4.9 KB)
   - Resources, Forms, Tables

📄 validation.md (5.0 KB)
   - Form Requests (SEMPRE obrigatórios)

📄 api.md (4.5 KB)
   - API Resources (SEMPRE usar)

📄 tests.md (4.8 KB)
   - Pest, expectations encadeadas, factories

📄 commits.md (0.6 KB)
   - Conventional Commits

Total: ~31 KB (otimizado para performance, sem alertas)
```

Informe ao usuário:

**🎯 Guidelines instalados e prontos para uso!**

Estes guidelines agora serão **automaticamente carregados pelo Claude** toda vez que você trabalhar neste projeto. O Claude usará estas informações para:

✅ Seguir os padrões de arquitetura definidos (Actions, Services, DTOs)
✅ Aplicar convenções de código PSR-12
✅ Criar models com relationships, scopes e casts corretos
✅ Escrever migrations com nomenclatura e estrutura adequadas
✅ Aplicar regras de validação consistentes
✅ Estruturar Resources do Filament corretamente
✅ Criar componentes Livewire/Blade reutilizáveis
✅ Escrever testes seguindo boas práticas
✅ Fazer commits padronizados
✅ E muito mais...

**Importante:**

- Os guidelines são carregados automaticamente pelo Laravel Boost
- Não é necessário reiniciar o Claude - eles estão imediatamente disponíveis
- Você pode editar os guidelines em `.ai/guidelines/` a qualquer momento
- Execute `php artisan boost:update` após modificar os guidelines para recarregá-los

**Customização:**

- Os guidelines podem ser personalizados para seu projeto/equipe
- Edite os arquivos em `.ai/guidelines/` conforme necessário
- Adicione novos guidelines específicos do seu domínio

### 7. Verificar Guidelines Locais (Opcional)

**Verifique se existe diretório `guidelines/` no projeto:**

```bash
ls -la guidelines/ 2>/dev/null
```

**Se encontrar guidelines locais:**

Pergunte ao usuário se ele quer copiar/mesclar com os guidelines padrão já instalados:

```
✅ Guidelines padrão já instalados!

Encontrei guidelines personalizados em 'guidelines/'.
Deseja copiá-los também para '.ai/guidelines/' para complementar os guidelines padrão?

Opções:
1. Sim - Copiar e mesclar (sobrescreve arquivos com mesmo nome)
2. Não - Manter apenas os guidelines padrão
```

**Se o usuário aceitar copiar:**

```bash
echo "📥 Copiando guidelines locais..."
cp guidelines/*.md .ai/guidelines/ 2>/dev/null || true
cp guidelines/*.blade.php .ai/guidelines/ 2>/dev/null || true
echo "✅ Guidelines locais mesclados!"
```

### 8. Adicionar Guidelines Personalizados (Opcional)

**Os guidelines padrão já foram instalados.** Agora você pode adicionar guidelines personalizados adicionais se necessário.

Informe ao usuário que ele pode adicionar mais guidelines específicos do projeto em `.ai/guidelines/`:

- Arquivos `.md` ou `.blade.php` com instruções específicas do projeto
- Padrões de código da equipe
- Regras de negócio importantes
- Arquitetura do projeto

Exemplo de estrutura:

```bash
mkdir -p .ai/guidelines
```

Crie um arquivo exemplo `.ai/guidelines/project-standards.md`:

```markdown
# Padrões do Projeto

## Arquitetura

- Usamos Actions para lógica de negócio
- DTOs para transferência de dados
- Repository pattern para acesso a dados

## Nomenclatura

- Models no singular (User, Product)
- Controllers com sufixo Controller
- Actions com verbo no infinitivo (CreateUser, UpdateProduct)

## Testes

- Feature tests para fluxos completos
- Unit tests para lógica isolada
- Factories para todos os models
```

**Dica:** Recomenda-se criar guidelines para:

- `architecture.md` - Arquitetura e padrões do projeto
- `code-style.md` - Estilo de código e convenções
- `models.md` - Padrões para Models Eloquent
- `migrations.md` - Convenções de migrations
- `tests.md` - Estratégia de testes
- `filament.md` - Padrões do Filament (se usar)
- `validation.md` - Regras de validação
- `components.md` - Componentes Livewire/Blade

### 9. Testar a Integração

Execute um comando simples para testar se o Laravel Boost está funcionando:

```bash
php artisan boost:mcp
```

Este comando deve iniciar o servidor MCP sem erros.

### 10. Atualizar Guidelines

Execute o comando de atualização para sincronizar guidelines com os pacotes instalados:

```bash
php artisan boost:update
```

### 11. Verificar Ferramentas Disponíveis

Informe ao usuário que agora o Claude tem acesso às seguintes ferramentas MCP especializadas para Laravel:

**Ferramentas de Banco de Dados:**

- `query-database` - Executar queries SQL
- `list-tables` - Listar tabelas do banco
- `describe-table` - Descrever estrutura de uma tabela

**Ferramentas de Inspeção:**

- `list-routes` - Listar rotas da aplicação
- `list-models` - Listar Models Eloquent
- `inspect-model` - Inspecionar um Model específico
- `list-config` - Listar configurações
- `get-config` - Obter valor de configuração

**Ferramentas de Execução:**

- `tinker` - Executar código PHP via Tinker
- `artisan` - Executar comandos Artisan

**Ferramentas de Log:**

- `search-logs` - Buscar em arquivos de log
- `tail-log` - Monitorar log em tempo real

**Ferramentas de Documentação:**

- `search-docs` - Buscar na documentação Laravel (17.000+ itens)

### 12. Configurar .gitignore (se necessário)

Verifique se `.ai/` está no `.gitignore` se os guidelines forem específicos do desenvolvedor.

Se os guidelines forem compartilhados pela equipe, NÃO adicione ao `.gitignore`.

**Recomendação:**

- **Compartilhado (recomendado):** Não adicione `.ai/` ao `.gitignore` (toda equipe usa os mesmos guidelines)
- **Individual:** Adicione `.ai/` ao `.gitignore` (cada dev tem seus próprios guidelines)

**Dica:** Para projetos em equipe, é recomendado versionar os guidelines no Git para garantir que todos sigam os mesmos padrões.

### 13. Resumo Final

Mostre ao usuário um resumo do que foi instalado e configurado:

## ✅ Laravel Boost Instalado com Sucesso!

**O que foi configurado:**

- ✅ Laravel Boost instalado via Composer
- ✅ Servidor MCP configurado e registrado no Claude
- ✅ Guidelines otimizados para Claude gerados para Laravel e pacotes do ecossistema
- ✅ Auto-update configurado no composer.json
- ✅ Integração com Claude Code ativada e funcionando

**Ferramentas MCP Disponíveis:**

- 📊 Consulta ao banco de dados
- 🔍 Inspeção de rotas, models e configurações
- ⚡ Execução de código via Tinker
- 📝 Busca em logs
- 📚 Busca na documentação Laravel (17.000+ itens)

**Comandos úteis:**

- `php artisan boost:update` - Atualizar guidelines
- `php artisan boost:mcp` - Iniciar servidor MCP
- `claude mcp list` - Listar servidores MCP registrados

**Guidelines personalizados para Claude:**

- Adicione arquivos `.md` ou `.blade.php` em `.ai/guidelines/`
- Guidelines são automaticamente carregados e usados pelo Claude
- Use para ensinar o Claude sobre padrões, arquitetura e regras específicas do seu projeto

**Próximos passos:**

1. Reinicie o Claude Code para garantir que o servidor MCP está carregado
2. Teste fazendo perguntas ao Claude sobre seu projeto Laravel
3. Use comandos como "liste as rotas da aplicação", "mostre os models" ou "consulte a tabela users"
4. Adicione guidelines personalizados para melhorar as respostas do Claude conforme necessário

**Nota importante:** Laravel Boost está em beta e recebe atualizações frequentes. Execute `composer update laravel/boost` regularmente para obter novos recursos.

## Notas Importantes

- O servidor MCP deve ser registrado automaticamente no Claude durante a instalação
- Se o registro falhar, use o comando manual: `claude mcp add -s local -t stdio laravel-boost php artisan boost:mcp`
- Execute `php artisan boost:update` sempre que instalar novos pacotes Laravel para atualizar os guidelines do Claude
- Guidelines customizados são opcionais mas altamente recomendados para ensinar o Claude sobre padrões específicos do projeto
- O Laravel Boost detecta automaticamente pacotes do ecossistema e configura guidelines específicos para: Laravel, Livewire, Filament, Jetstream, Breeze, Fortify, Cashier, Horizon, Telescope, Sanctum, Passport, Scout, Socialite, Vapor e outros
- Com o Laravel Boost, o Claude pode executar comandos Artisan, consultar o banco de dados e buscar na documentação Laravel diretamente
