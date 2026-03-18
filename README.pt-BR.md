# Forge Test Branches

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/danie1net0/forge-test-branches/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ddr/forge-test-branches/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)

**Idiomas:** [English](README.en.md) | [Español](README.es.md) | Português

Crie ambientes de teste efêmeros (review apps) a partir de branches git no Laravel Forge. Ideal para validar features com stakeholders antes do merge.

## Propósito

Este pacote automatiza a criação e gerenciamento de ambientes de teste no Laravel Forge. Ele elimina a configuração manual necessária para criar sites temporários a partir de branches git.

Sem o pacote, criar um ambiente de teste requer:

- Criar site manualmente no Forge
- Configurar repositório git e branch
- Criar banco de dados
- Criar usuário de banco de dados
- Configurar variáveis de ambiente
- Configurar SSL
- Fazer deploy inicial
- Lembrar de deletar tudo depois

Com o pacote, você executa um comando e tudo isso acontece automaticamente. Quando a branch é deletada, um webhook limpa os recursos.

## O que ele faz

O pacote gerencia o ciclo completo de ambientes de review:

**Criação**

1. Cria um site no Forge com domínio baseado no nome da branch
2. Instala o repositório git na branch especificada
3. Cria banco de dados com prefixo configurável
4. Cria usuário de banco com acesso apenas ao banco criado
5. Configura variáveis de ambiente customizadas
6. Executa script de deploy (migrations, composer, npm)
7. Configura certificado SSL Let's Encrypt
8. Habilita quick deploy (opcional)

**Destruição**

- Webhook detecta quando branch é deletada
- Remove site, banco de dados e usuário do banco
- Limpa registros locais

## Casos de uso

### 1. Validação de features com product managers

Você está desenvolvendo uma nova interface de checkout. O PM precisa validar antes do merge para produção.

```bash
git checkout -b feat/new-checkout

# ... desenvolve a feature ...

# Cria ambiente de teste
php artisan forge-test-branches:create --branch=feat/new-checkout
```

Resultado: `https://feat-new-checkout.review.myapp.com` com banco de dados próprio, SSL válido, pronto para validação.

O PM acessa o link, valida a feature. Após o merge, o webhook deleta o ambiente automaticamente.

### 2. Testes com APIs externas

Feature de pagamento integrando com gateway. Cada branch precisa usar credenciais de sandbox.

```php
// config/forge-test-branches.php
'env_variables' => [
    'PAYMENT_GATEWAY_URL' => 'https://sandbox.gateway.com',
    'PAYMENT_GATEWAY_KEY' => 'sandbox_key_{slug}',
],
```

Cada ambiente de review tem suas próprias credenciais, evitando interferência entre testes.

### 3. Demonstrações para clientes

Agência desenvolvendo features customizadas. Cliente quer ver progresso antes da entrega.

```yaml
# .gitlab-ci.yml
review_app:
    stage: review
    script:
        - php artisan forge-test-branches:create
        - php artisan forge-test-branches:deploy
    when: manual
```

Developer cria branch, abre MR e clica em "Deploy Review". Cliente acessa ambiente isolado sem afetar outros ambientes.

### 4. Testes de migração de schema

Nova migration que altera estrutura do banco. Precisa validar em ambiente limpo antes do merge.

```bash
git checkout -b feat/add-user-preferences

# Cria ambiente e roda migrations automaticamente
php artisan forge-test-branches:create
```

O deploy automático roda as migrations. Se falhar, você corrige antes do merge para develop/main.

### 5. Debugging de bugs em produção

Bug reportado em produção. Você precisa investigar com dados similares mas sem risco.

```bash
git checkout -b fix/payment-timeout production
php artisan forge-test-branches:create
```

Ambiente idêntico à produção, com seeders de dados realistas. Você investiga, aplica fix, testa e valida antes de fazer merge.

### 6. Testes de performance com dados reais

Feature de exportação de relatórios. Precisa testar com volume de dados realista.

```php
// config/forge-test-branches.php
'deploy' => [
    'seed' => true,
    'seed_class' => 'PerformanceSeeder',
],
```

Cada deploy executa o seeder que cria 100k+ registros. Você testa performance isoladamente.

## Como funciona

```
CRIAÇÃO:
Branch criada → CI/CD dispara comandos → Site + DB + SSL criados
                                          ↓
                        https://branch-name.review.mysite.com

DESTRUIÇÃO:
Branch deletada → Webhook dispara → Site + DB removidos
```

## Requisitos

- PHP 8.2+
- Laravel 11+
- Conta Laravel Forge com API Token

## Instalação

```bash
composer require ddr/forge-test-branches
```

Instalação interativa (recomendado):

```bash
php artisan forge-test-branches:install
```

Este comando:

- Publica configuração e migrations
- Configura variáveis de ambiente
- Opcionalmente adiciona job ao GitLab CI

Depois execute:

```bash
php artisan migrate
```

## Configuração

Adicione ao `.env`:

```env
FORGE_API_TOKEN=seu-token-forge
FORGE_SERVER_ID=123456
FORGE_REVIEW_DOMAIN=review.mysite.com
FORGE_GIT_PROVIDER=gitlab
FORGE_GIT_REPOSITORY=usuario/repositorio
```

Configuração completa em `config/forge-test-branches.php`:

```php
return [
    'forge_api_token' => env('FORGE_API_TOKEN'),
    'server_id' => env('FORGE_SERVER_ID'),

    'domain' => [
        'base' => env('FORGE_REVIEW_DOMAIN'),
        'pattern' => '{branch}.{base}',
    ],

    'git' => [
        'provider' => env('FORGE_GIT_PROVIDER', 'gitlab'),
        'repository' => env('FORGE_GIT_REPOSITORY'),
    ],

    'branch' => [
        'patterns' => ['*'], // ou ['feat/*', 'fix/*']
    ],

    'database' => [
        'prefix' => env('FORGE_DB_PREFIX', 'review_'),
    ],

    'site' => [
        'php_version' => env('FORGE_PHP_VERSION', 'php84'),
        'project_type' => env('FORGE_PROJECT_TYPE', 'php'),
        'directory' => env('FORGE_WEB_DIRECTORY', '/public'),
        'isolated' => env('FORGE_ISOLATED', false),
    ],

    'deploy' => [
        'script' => null, // null = script padrão do Forge
        'quick_deploy' => true,
        'seed' => env('FORGE_SEED', false),
        'seed_class' => env('FORGE_SEED_CLASS'),
    ],

    'webhook' => [
        'enabled' => env('FORGE_WEBHOOK_ENABLED', true),
        'secret' => env('FORGE_WEBHOOK_SECRET'),
        'path' => 'forge-test-branches/webhook',
    ],

    'env_variables' => [
        // Variáveis customizadas para o .env do site
        // 'APP_URL' => 'https://{slug}.review.mysite.com',
    ],
];
```

## Uso

### Comandos

```bash
# Criar ambiente
php artisan forge-test-branches:create --branch=feat/nova-feature

# Deploy de atualizações
php artisan forge-test-branches:deploy --branch=feat/nova-feature

# Destruir ambiente
php artisan forge-test-branches:destroy --branch=feat/nova-feature
```

No CI/CD, a variável `CI_COMMIT_REF_NAME` é detectada automaticamente:

```bash
php artisan forge-test-branches:create
```

### Facade

```php
use Ddr\ForgeTestBranches\Facades\ForgeTestBranches;

// Criar
$env = ForgeTestBranches::create('feat/nova-feature');
echo $env->domain; // feat-nova-feature.review.mysite.com

// Verificar existência
if (ForgeTestBranches::exists('feat/nova-feature')) {
    //
}

// Buscar
$env = ForgeTestBranches::find('feat/nova-feature');

// Deploy
ForgeTestBranches::deploy('feat/nova-feature');

// Destruir
ForgeTestBranches::destroy('feat/nova-feature');
```

### Model

```php
use Ddr\ForgeTestBranches\Models\ReviewEnvironment;

$environments = ReviewEnvironment::all();
$env = ReviewEnvironment::where('branch', 'feat/nova-feature')->first();

$env->branch;        // feat/nova-feature
$env->slug;          // feat-nova-feature
$env->domain;        // feat-nova-feature.review.mysite.com
$env->site_id;       // ID no Forge
$env->database_id;   // ID do banco no Forge
```

## Integração CI/CD

### GitLab

Adicione ao `.gitlab-ci.yml`:

```yaml
stages:
    - review

review_app:
    stage: review
    image: php:8.4-cli
    before_script:
        - apt-get update && apt-get install -y git unzip
        - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        - composer install --no-interaction --prefer-dist
    script:
        - php artisan forge-test-branches:create
        - php artisan forge-test-branches:deploy
    environment:
        name: review/$CI_COMMIT_REF_SLUG
        url: https://$CI_COMMIT_REF_SLUG.review.mysite.com
    rules:
        - if: $CI_MERGE_REQUEST_ID
          when: manual
```

### Webhook para Limpeza Automática

O webhook detecta quando uma branch é deletada e remove automaticamente o ambiente de review.

**Como funciona:**

- GitLab: Envia um Push Hook com `after: "0000000000000000000000000000000000000000"` quando branch é deletada
- GitHub: Envia um evento `delete` quando branch é deletada

**Configuração no GitLab:**

1. Vá em **Settings > Webhooks**
2. URL: `https://sua-app.com/forge-test-branches/webhook`
3. Secret token: **mesmo valor de `FORGE_WEBHOOK_SECRET` do .env**
4. Marque apenas: **Push events**
5. Desmarque "Enable SSL verification" se estiver usando domínio de desenvolvimento
6. Clique em "Add webhook"

**Testar webhook:**
Após configurar, clique em "Test" > "Push events" no GitLab. Você deve ver HTTP 200 e mensagem "Event ignored" ou "Not a branch deletion" (normal, pois o teste não é uma deleção real).

**GitHub**

1. Settings > Webhooks
2. Payload URL: `https://sua-app.com/forge-test-branches/webhook`
3. Secret: valor de `FORGE_WEBHOOK_SECRET`
4. Events: **Branch or tag deletion**

### GitHub Actions

```yaml
name: Review App

on:
    pull_request:
        types: [opened, synchronize]

jobs:
    deploy-review:
        runs-on: ubuntu-latest
        steps:
            - uses: actions/checkout@v4

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.4"

            - name: Install Dependencies
              run: composer install --no-interaction

            - name: Create Review Environment
              env:
                  FORGE_API_TOKEN: ${{ secrets.FORGE_API_TOKEN }}
                  FORGE_SERVER_ID: ${{ secrets.FORGE_SERVER_ID }}
                  FORGE_REVIEW_DOMAIN: ${{ secrets.FORGE_REVIEW_DOMAIN }}
                  FORGE_GIT_REPOSITORY: ${{ github.repository }}
              run: |
                  php artisan forge-test-branches:create --branch=${{ github.head_ref }}
                  php artisan forge-test-branches:deploy --branch=${{ github.head_ref }}
```

## Configurações avançadas

### Script de deploy customizado

```php
'deploy' => [
    'script' => <<<'SCRIPT'
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache

npm ci
npm run build
SCRIPT,
],
```

### Variáveis de ambiente customizadas

```php
'env_variables' => [
    'APP_ENV' => 'staging',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'https://{slug}.review.mysite.com',
    'CACHE_PREFIX' => '{slug}_cache',
],
```

O placeholder `{slug}` é substituído pelo nome sanitizado da branch.

### Filtros de branch

```php
'branch' => [
    'patterns' => ['feat/*', 'fix/*'],
],
```

Apenas branches que correspondem aos padrões terão ambientes criados.

### Database seeding

```php
'deploy' => [
    'seed' => true,
    'seed_class' => 'ReviewSeeder',
],
```

Ou via `.env`:

```env
FORGE_SEED=true
FORGE_SEED_CLASS=ReviewSeeder
```

## Troubleshooting

### "Site creation failed"

Verifique:

- `FORGE_API_TOKEN` está correto
- `FORGE_SERVER_ID` existe e é acessível
- Domínio base está configurado no DNS

### "Database creation failed"

Verifique:

- Servidor tem MySQL/PostgreSQL instalado
- Prefixo do banco não conflita com bancos existentes

### Webhook não funciona

**1. Verifique se o webhook está sendo chamado:**

- No GitLab: Settings > Webhooks > clique no webhook > "Recent events"
- Veja se há requests e qual o status code retornado

**2. HTTP 401 - Unauthorized:**

- O `FORGE_WEBHOOK_SECRET` no `.env` deve ser EXATAMENTE igual ao configurado no GitLab
- Verifique espaços em branco ou caracteres extras
- Ou remova o secret: deixe `FORGE_WEBHOOK_SECRET=` vazio no `.env`

**3. HTTP 404 - Not Found:**

- Verifique se `FORGE_WEBHOOK_ENABLED=true` no `.env`
- Execute `php artisan config:clear`
- Execute `php artisan route:list | grep webhook` para ver se a rota existe

**4. HTTP 500 - Server Error:**

- Verifique os logs da aplicação: `tail -f storage/logs/laravel.log`

**5. Webhook não dispara ao deletar branch:**

- Certifique-se de marcar **apenas** "Push events" no GitLab
- Aguarde alguns segundos após deletar a branch
- Verifique em "Recent events" no GitLab se o webhook foi disparado

**6. Teste manual do webhook:**

```bash
# Substitua os valores pelos seus
curl -X POST https://sua-app.com/forge-test-branches/webhook \
  -H "X-Gitlab-Event: Push Hook" \
  -H "X-Gitlab-Token: seu-secret-token" \
  -H "Content-Type: application/json" \
  -d '{
    "ref": "refs/heads/feat/test-branch",
    "after": "0000000000000000000000000000000000000000"
  }'
```

Se retornar `{"message":"Environment not found"}` está funcionando! (o webhook está ativo, apenas o ambiente não existe na base de dados)

### SSL não é gerado

O certificado é gerado automaticamente após criação do site. Se falhar:

- Verifique se domínio aponta para o servidor
- Aguarde propagação DNS (alguns minutos)

## Testes

```bash
composer test
composer test:coverage
composer analyse
```

## Changelog

Veja [CHANGELOG](CHANGELOG.md) para mudanças recentes.

## Créditos

- [Daniel Neto](https://github.com/danie1net0)
- [Todos os Contribuidores](../../contributors)

## Licença

MIT License. Veja [LICENSE.md](LICENSE.md) para mais informações.
