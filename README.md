# Forge Test Branches

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/ddr/forge-test-branches/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ddr/forge-test-branches/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/ddr/forge-test-branches/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ddr/forge-test-branches/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)

Create ephemeral test environments (review apps) from git branches on Laravel Forge. Ideal for validating features with stakeholders before merging.

## Features

- Automatically creates sites on Forge from branches
- Generates dynamic subdomains (e.g., `feat-hu-123.review.mysite.com`)
- Automatically creates and manages databases
- Automatically configures Let's Encrypt SSL certificates
- Integration with GitLab CI/CD and GitHub Actions
- Webhook for automatic cleanup when branch is deleted
- Artisan commands for manual management
- Support for custom deploy scripts

## Requirements

- PHP 8.2+
- Laravel 11+
- Laravel Forge account with API Token

## How It Works

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              CREATION FLOW                                  │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   1. Branch created         2. CI/CD triggers          3. Environment ready │
│   ┌──────────────┐          ┌──────────────┐          ┌──────────────┐      │
│   │ feat/hu-123  │  ─────►  │ forge:create │  ─────►  │ Site + DB    │      │
│   └──────────────┘          │ forge:deploy │          │ + SSL        │      │
│                             └──────────────┘          └──────────────┘      │
│                                                              │              │
│                                                              ▼              │
│                                               https://feat-hu-123.review.  │
│                                                        mysite.com           │
└─────────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────────┐
│                             DESTRUCTION FLOW                                │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│   1. Branch deleted         2. Webhook triggers        3. Resources cleaned │
│   ┌──────────────┐          ┌──────────────┐          ┌──────────────┐      │
│   │ Merge + Del  │  ─────►  │   Webhook    │  ─────►  │ Site + DB    │      │
│   └──────────────┘          │   GitLab     │          │   DELETED    │      │
│                             └──────────────┘          └──────────────┘      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

The package automates the following operations on Forge:

1. **Site Creation** - With domain based on branch slug
2. **Git Repository Installation** - Clones the repository on the specified branch
3. **Database Creation** - With configurable prefix
4. **Database User Creation** - With access only to the created database
5. **.env Configuration** - Custom environment variables
6. **Deploy Script** - Configurable or default with migrations
7. **SSL Certificate** - Automatic Let's Encrypt
8. **Quick Deploy** - Automatic deploy via push (optional)

## Installation

```bash
composer require ddr/forge-test-branches
```

Run the interactive installation command:

```bash
php artisan forge-test-branches:install
```

This command will:

- Publish the configuration file
- Publish the migrations
- Configure environment variables in `.env`
- Add GitLab CI/CD configuration to `.gitlab-ci.yml`

After installation, run the migrations:

```bash
php artisan migrate
```

### Manual Installation

If you prefer to configure manually, publish the files:

```bash
php artisan vendor:publish --tag="forge-test-branches-config"
php artisan vendor:publish --tag="forge-test-branches-migrations"
```

And add the environment variables to `.env`:

```env
FORGE_API_TOKEN=your-forge-token
FORGE_SERVER_ID=123456
FORGE_REVIEW_DOMAIN=review.mysite.com
FORGE_GIT_PROVIDER=gitlab
FORGE_GIT_REPOSITORY=my-user/my-repo
FORGE_WEBHOOK_SECRET=your-optional-secret
```

## Configuration

### Environment Variables

| Variable                | Description                                      | Required                |
| ----------------------- | ------------------------------------------------ | ----------------------- |
| `FORGE_API_TOKEN`       | Forge API token                                  | Yes                     |
| `FORGE_SERVER_ID`       | Server ID on Forge                               | Yes                     |
| `FORGE_REVIEW_DOMAIN`   | Base domain (e.g., `review.mysite.com`)          | Yes                     |
| `FORGE_GIT_PROVIDER`    | Git provider (`gitlab`, `github`, `bitbucket`)   | No (default: `gitlab`)  |
| `FORGE_GIT_REPOSITORY`  | Repository in `user/repo` format                 | Yes                     |
| `FORGE_WEBHOOK_SECRET`  | Secret token to validate webhooks                | No                      |
| `FORGE_PHP_VERSION`     | PHP version (`php81`, `php82`, `php83`, `php84`) | No (default: `php84`)   |
| `FORGE_PROJECT_TYPE`    | Project type (`php`, `html`, `symfony`)          | No (default: `php`)     |
| `FORGE_WEB_DIRECTORY`   | Public directory                                 | No (default: `/public`) |
| `FORGE_ISOLATED`        | Isolated mode (dedicated user)                   | No (default: `false`)   |
| `FORGE_DB_PREFIX`       | Database prefix                                  | No (default: `review_`) |
| `FORGE_WEBHOOK_ENABLED` | Enable webhook                                   | No (default: `true`)    |

### Full Configuration File

```php
return [
    'forge_api_token' => env('FORGE_API_TOKEN'),
    'server_id' => env('FORGE_SERVER_ID'),

    'domain' => [
        'base' => env('FORGE_REVIEW_DOMAIN'),
        'pattern' => '{branch}.{base}', // feat-hu-123.review.mysite.com
    ],

    'git' => [
        'provider' => env('FORGE_GIT_PROVIDER', 'gitlab'), // gitlab, github, bitbucket
        'repository' => env('FORGE_GIT_REPOSITORY'),
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
        'script' => null, // Custom script or null for default
        'quick_deploy' => true,
    ],

    'webhook' => [
        'enabled' => env('FORGE_WEBHOOK_ENABLED', true),
        'secret' => env('FORGE_WEBHOOK_SECRET'),
        'path' => 'forge-test-branches/webhook',
    ],

    'env_variables' => [
        // Custom variables for site .env
        // 'APP_URL' => 'https://{slug}.review.mysite.com',
    ],
];
```

### Custom Deploy Script

To use a custom deploy script, define it in the configuration:

```php
'deploy' => [
    'script' => <<<'SCRIPT'
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH

composer install --no-interaction --prefer-dist --optimize-autoloader

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

npm ci
npm run build

php artisan queue:restart
SCRIPT,
],
```

Or use `null` for the default Forge script with automatic migrations.

### Custom Environment Variables

Configure specific environment variables for review environments:

```php
'env_variables' => [
    'APP_ENV' => 'staging',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'https://{slug}.review.mysite.com',
    'CACHE_PREFIX' => '{slug}_cache',
    'SESSION_DOMAIN' => '.review.mysite.com',
],
```

The `{slug}` placeholder will be replaced with the sanitized branch slug.

## Usage

### Artisan Commands

```bash
# Create review environment
php artisan forge-test-branches:create --branch=feat/hu-123

# Deploy updates
php artisan forge-test-branches:deploy --branch=feat/hu-123

# Destroy environment
php artisan forge-test-branches:destroy --branch=feat/hu-123
```

Commands also automatically detect the branch via GitLab CI's `CI_COMMIT_REF_NAME` variable.

### Facade

```php
use Ddr\ForgeTestBranches\Facades\ForgeTestBranches;

// Create environment
$environment = ForgeTestBranches::create('feat/hu-123');
echo $environment->domain; // feat-hu-123.review.mysite.com

// Check if exists
if (ForgeTestBranches::exists('feat/hu-123')) {
    // ...
}

// Find environment
$environment = ForgeTestBranches::find('feat/hu-123');

// Deploy
ForgeTestBranches::deploy('feat/hu-123');

// Destroy
ForgeTestBranches::destroy('feat/hu-123');
```

### ReviewEnvironment Model

```php
use Ddr\ForgeTestBranches\Models\ReviewEnvironment;

// List all environments
$environments = ReviewEnvironment::all();

// Find by branch
$environment = ReviewEnvironment::where('branch', 'feat/hu-123')->first();

// Access environment data
$environment->branch;        // feat/hu-123
$environment->slug;          // feat-hu-123
$environment->domain;        // feat-hu-123.review.mysite.com
$environment->server_id;     // 123456
$environment->site_id;       // 789
$environment->database_id;   // 101112
```

## CI/CD Integration

### GitLab CI/CD

The recommended flow is:

- **Create/Deploy**: Manual (you decide when to create/update the test environment)
- **Destroy**: Automatic via webhook when the branch is deleted (after merge)

> **Tip**: The `php artisan forge-test-branches:install` command can automatically configure GitLab CI/CD.

#### 1. Add the job to `.gitlab-ci.yml`:

```yaml
stages:
    - review

# Creates the environment (if it doesn't exist) and deploys (manual)
review_app:
    stage: review
    image: php:8.4-cli
    before_script:
        - apt-get update && apt-get install -y git unzip
        - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        - composer install --no-interaction --prefer-dist
    script:
        - php artisan forge-test-branches:create --branch=$CI_COMMIT_REF_NAME
        - php artisan forge-test-branches:deploy --branch=$CI_COMMIT_REF_NAME
    environment:
        name: review/$CI_COMMIT_REF_SLUG
        url: https://$CI_COMMIT_REF_SLUG.review.mysite.com
    rules:
        - if: $CI_MERGE_REQUEST_ID
          when: manual
          allow_failure: true
```

A complete example file is available at `stubs/.gitlab-ci.review.yml`.

#### 2. Configure the Webhook for automatic deletion

To automatically destroy the environment when the branch is deleted (after merge):

1. Go to **Settings > Webhooks** in GitLab
2. URL: `https://your-app.com/forge-test-branches/webhook`
3. Secret Token: the same value as `FORGE_WEBHOOK_SECRET`
4. Trigger: **Push events**
5. Click **Add webhook**

When the branch is deleted (manually or after merge with "Delete source branch"), the webhook will be triggered and the environment will be automatically destroyed.

### GitHub Actions

```yaml
name: Review App

on:
    pull_request:
        types: [opened, synchronize, reopened, closed]

jobs:
    deploy-review:
        runs-on: ubuntu-latest
        if: github.event.action != 'closed'

        steps:
            - uses: actions/checkout@v4

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.4"

            - name: Install Dependencies
              run: composer install --no-interaction --prefer-dist

            - name: Create Review Environment
              env:
                  FORGE_API_TOKEN: ${{ secrets.FORGE_API_TOKEN }}
                  FORGE_SERVER_ID: ${{ secrets.FORGE_SERVER_ID }}
                  FORGE_REVIEW_DOMAIN: ${{ secrets.FORGE_REVIEW_DOMAIN }}
                  FORGE_GIT_REPOSITORY: ${{ github.repository }}
              run: |
                  php artisan forge-test-branches:create --branch=${{ github.head_ref }}
                  php artisan forge-test-branches:deploy --branch=${{ github.head_ref }}

    cleanup-review:
        runs-on: ubuntu-latest
        if: github.event.action == 'closed'

        steps:
            - uses: actions/checkout@v4

            - name: Setup PHP
              uses: shivammathur/setup-php@v2
              with:
                  php-version: "8.4"

            - name: Install Dependencies
              run: composer install --no-interaction --prefer-dist

            - name: Destroy Review Environment
              env:
                  FORGE_API_TOKEN: ${{ secrets.FORGE_API_TOKEN }}
              run: php artisan forge-test-branches:destroy --branch=${{ github.head_ref }}
```

## Troubleshooting

### Error "Site creation failed"

Check if:

- The `FORGE_API_TOKEN` is correct and has the necessary permissions
- The `FORGE_SERVER_ID` exists and is accessible
- The base domain is correctly configured in DNS

### Error "Database creation failed"

Check if:

- The server has MySQL/PostgreSQL installed
- The database prefix doesn't conflict with existing databases

### Webhook not working

Check if:

- The `FORGE_WEBHOOK_SECRET` is configured identically in GitLab and `.env`
- The endpoint is publicly accessible
- The webhook is configured for **Push events**

### SSL not being generated

The SSL certificate is automatically generated after site creation. If it fails:

- Check if the domain is correctly pointing to the server
- Wait for DNS propagation (may take a few minutes)

## Testing

```bash
composer test
```

For coverage:

```bash
composer test:coverage
```

For static analysis:

```bash
composer analyse
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for more information about recent changes.

## Credits

- [Daniel Neto](https://github.com/danie1net0)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). See [License File](LICENSE.md) for more information.
