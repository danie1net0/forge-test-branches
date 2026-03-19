# Forge Test Branches

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/danie1net0/forge-test-branches/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ddr/forge-test-branches/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/danie1net0/forge-test-branches/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/ddr/forge-test-branches/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)

**Languages:** English | [Español](README.es.md) | [Português](README.pt-BR.md)

Create ephemeral test environments (review apps) from git branches on Laravel Forge. Ideal for validating features with stakeholders before merging.

## Purpose

This package automates the creation and management of test environments on Laravel Forge. It eliminates the manual configuration needed to create temporary sites from git branches.

Without the package, creating a test environment requires:

- Manually create site on Forge
- Configure git repository and branch
- Create database
- Create database user
- Configure environment variables
- Set up SSL
- Initial deployment
- Remember to delete everything later

With the package, you run a command and all of this happens automatically. When the branch is deleted, a webhook cleans up the resources.

## What it does

The package manages the complete lifecycle of review environments:

**Creation**

1. Creates a site on Forge with domain based on branch name
2. Installs git repository on the specified branch
3. Creates database with configurable prefix
4. Creates database user with access only to the created database
5. Configures custom environment variables
6. Runs deploy script (migrations, composer, npm)
7. Sets up Let's Encrypt SSL certificate
8. Enables quick deploy (optional)

**Destruction**

- Webhook detects when branch is deleted
- Removes site, database, and database user
- Cleans local records

## Use cases

### 1. Feature validation with product managers

You're developing a new checkout interface. The PM needs to validate before merging to production.

```bash
git checkout -b feat/new-checkout

# ... develop the feature ...

# Create test environment
php artisan forge-test-branches:create --branch=feat/new-checkout
```

Result: `https://feat-new-checkout.review.myapp.com` with its own database, valid SSL, ready for validation.

The PM accesses the link, validates the feature. After merge, the webhook automatically deletes the environment.

### 2. Testing with external APIs

Payment feature integrating with gateway. Each branch needs to use sandbox credentials.

```php
// config/forge-test-branches.php
'env_variables' => [
    'PAYMENT_GATEWAY_URL' => 'https://sandbox.gateway.com',
    'PAYMENT_GATEWAY_KEY' => 'sandbox_key_{slug}',
],
```

Each review environment has its own credentials, avoiding interference between tests.

### 3. Client demonstrations

Agency developing custom features. Client wants to see progress before delivery.

```yaml
# .gitlab-ci.yml
review_app:
    stage: review
    script:
        - php artisan forge-test-branches:create
        - php artisan forge-test-branches:deploy
    when: manual
```

Developer creates branch, opens MR and clicks "Deploy Review". Client accesses isolated environment without affecting other environments.

### 4. Schema migration testing

New migration that changes database structure. Need to validate in clean environment before merge.

```bash
git checkout -b feat/add-user-preferences

# Create environment and run migrations automatically
php artisan forge-test-branches:create
```

Automatic deployment runs migrations. If it fails, you fix it before merging to develop/main.

### 5. Debugging production bugs

Bug reported in production. You need to investigate with similar data but without risk.

```bash
git checkout -b fix/payment-timeout production
php artisan forge-test-branches:create
```

Environment identical to production, with realistic data seeders. You investigate, apply fix, test and validate before merging.

### 6. Performance testing with real data

Report export feature. Need to test with realistic data volume.

```php
// config/forge-test-branches.php
'deploy' => [
    'seed' => true,
    'seed_class' => 'PerformanceSeeder',
],
```

Each deployment runs the seeder that creates 100k+ records. You test performance in isolation.

## How it works

```
CREATION:
Branch created → CI/CD triggers commands → Site + DB + SSL created
                                            ↓
                          https://branch-name.review.mysite.com

DESTRUCTION:
Branch deleted → Webhook triggers → Site + DB removed
```

## Requirements

- PHP 8.2+
- Laravel 11+
- Laravel Forge account with API Token

## Installation

```bash
composer require ddr/forge-test-branches
```

Interactive installation (recommended):

```bash
php artisan forge-test-branches:install
```

This command:

- Publishes configuration and migrations
- Configures environment variables
- Optionally adds job to GitLab CI

Then run:

```bash
php artisan migrate
```

## Configuration

Add to `.env`:

```env
FORGE_API_TOKEN=your-forge-token
FORGE_SERVER_ID=123456
FORGE_REVIEW_DOMAIN=review.mysite.com
FORGE_GIT_PROVIDER=gitlab
FORGE_GIT_REPOSITORY=username/repository
```

Full configuration in `config/forge-test-branches.php`:

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

    'branch' => [
        'patterns' => ['*'], // ['feat/*', 'review/*', 'fix/*']
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
        'script' => null, // null = Forge default script
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
        // Custom variables for site .env
        // 'APP_URL' => 'https://{slug}.review.mysite.com',
    ],
];
```

## Usage

### Commands

```bash
# Create environment
php artisan forge-test-branches:create --branch=feat/new-feature

# Deploy updates
php artisan forge-test-branches:deploy --branch=feat/new-feature

# Destroy environment
php artisan forge-test-branches:destroy --branch=feat/new-feature

# Update deploy script for an existing environment
php artisan forge-test-branches:update-script --branch=feat/new-feature

# Test Forge API connection
php artisan forge-test-branches:test-connection

# List all environments (shows Active/Orphan status)
php artisan forge-test-branches:list

# List only orphaned environments (branch no longer exists on remote)
php artisan forge-test-branches:list --orphans

# Destroy all orphaned environments (with confirmation)
php artisan forge-test-branches:list --destroy-orphans
```

In CI/CD, the `CI_COMMIT_REF_NAME` variable is automatically detected:

```bash
php artisan forge-test-branches:create
```

### Facade

```php
use Ddr\ForgeTestBranches\Facades\ForgeTestBranches;

// Create
$env = ForgeTestBranches::create('feat/new-feature');
echo $env->domain; // feat-new-feature.review.mysite.com

// Check existence
if (ForgeTestBranches::exists('feat/new-feature')) {
    //
}

// Find
$env = ForgeTestBranches::find('feat/new-feature');

// Deploy
ForgeTestBranches::deploy('feat/new-feature');

// Destroy
ForgeTestBranches::destroy('feat/new-feature');

// List all environments
$environments = ForgeTestBranches::listAll();
```

### Model

```php
use Ddr\ForgeTestBranches\Models\ReviewEnvironment;

$environments = ReviewEnvironment::all();
$env = ReviewEnvironment::where('branch', 'feat/new-feature')->first();

$env->branch;        // feat/new-feature
$env->slug;          // feat-new-feature
$env->domain;        // feat-new-feature.review.mysite.com
$env->site_id;       // Forge site ID
$env->database_id;   // Forge database ID
```

## CI/CD Integration

### GitLab

Add to `.gitlab-ci.yml`:

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

### Webhook for Automatic Cleanup

The webhook detects when a branch is deleted and automatically removes the review environment.

**How it works:**

- GitLab: Sends a Push Hook with `after: "0000000000000000000000000000000000000000"` when branch is deleted
- GitHub: Sends a `delete` event when branch is deleted

**GitLab Configuration:**

1. Go to **Settings > Webhooks**
2. URL: `https://your-app.com/forge-test-branches/webhook`
3. Secret token: **same value as `FORGE_WEBHOOK_SECRET` in .env**
4. Check only: **Push events**
5. Uncheck "Enable SSL verification" if using development domain
6. Click "Add webhook"

**Test webhook:**
After configuring, click "Test" > "Push events" in GitLab. You should see HTTP 200 and message "Event ignored" or "Not a branch deletion" (normal, as the test is not a real deletion).

**GitHub Configuration:**

1. Settings > Webhooks
2. Payload URL: `https://your-app.com/forge-test-branches/webhook`
3. Secret: value from `FORGE_WEBHOOK_SECRET`
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

## Advanced settings

### Custom deploy script

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

### Custom environment variables

```php
'env_variables' => [
    'APP_ENV' => 'staging',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'https://{slug}.review.mysite.com',
    'CACHE_PREFIX' => '{slug}_cache',
],
```

The `{slug}` placeholder is replaced by the sanitized branch name.

### Branch filters

```php
'branch' => [
    'patterns' => ['feat/*', 'fix/*'],
],
```

Only branches matching the patterns will have environments created.

### Orphan cleanup

Environments become orphaned when their branch is deleted without triggering the webhook (e.g., deleted via merge request). The `list` command detects these by comparing environments against remote branches via `git ls-remote`.

```bash
# See all environments with status
php artisan forge-test-branches:list

# Output:
# +---------------------+-------------------------------------------+--------+---------+
# | Branch              | Domain                                    | Status | Site ID |
# +---------------------+-------------------------------------------+--------+---------+
# | feat/active-branch  | feat-active-branch.review.mysite.com      | Active | 123456  |
# | feat/deleted-branch | feat-deleted-branch.review.mysite.com     | Orphan | 123457  |
# +---------------------+-------------------------------------------+--------+---------+

# Destroy all orphans (asks for confirmation)
php artisan forge-test-branches:list --destroy-orphans
```

You can also schedule orphan cleanup in your `routes/console.php`:

```php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('forge-test-branches:list --destroy-orphans --no-interaction')
    ->weekly();
```

### Logging

The package logs all operations to a dedicated channel. Logs are written to `storage/logs/forge-test-branches-YYYY-MM-DD.log` with daily rotation.

Events logged:

- Environment creation, destruction, and deployment
- Webhook received, processed, ignored, or rejected
- Signature validation failures

Configuration in `config/forge-test-branches.php`:

```php
'logging' => [
    'enabled' => env('FORGE_LOG_ENABLED', true),
    'channel' => 'forge-test-branches',
    'driver' => 'daily',
    'path' => storage_path('logs/forge-test-branches.log'),
    'days' => 14,
    'level' => env('FORGE_LOG_LEVEL', 'debug'),
],
```

Set `FORGE_LOG_ENABLED=false` to disable logging or `FORGE_LOG_LEVEL=info` to reduce verbosity.

### Database seeding

```php
'deploy' => [
    'seed' => true,
    'seed_class' => 'ReviewSeeder',
],
```

Or via `.env`:

```env
FORGE_SEED=true
FORGE_SEED_CLASS=ReviewSeeder
```

## Troubleshooting

### "Site creation failed"

Check:

- `FORGE_API_TOKEN` is correct
- `FORGE_SERVER_ID` exists and is accessible
- Base domain is configured in DNS

### "Database creation failed"

Check:

- Server has MySQL/PostgreSQL installed
- Database prefix doesn't conflict with existing databases

### Webhook not working

**1. Check if webhook is being called:**

- In GitLab: Settings > Webhooks > click webhook > "Recent events"
- See if there are requests and what status code was returned

**2. HTTP 401 - Unauthorized:**

- `FORGE_WEBHOOK_SECRET` in `.env` must be EXACTLY the same as configured in GitLab
- Check for whitespace or extra characters
- Or remove secret: leave `FORGE_WEBHOOK_SECRET=` empty in `.env`

**3. HTTP 404 - Not Found:**

- Check if `FORGE_WEBHOOK_ENABLED=true` in `.env`
- Run `php artisan config:clear`
- Run `php artisan route:list | grep webhook` to see if route exists

**4. HTTP 500 - Server Error:**

- Check package logs: `tail -f storage/logs/forge-test-branches-*.log`
- Check application logs: `tail -f storage/logs/laravel.log`

**5. Webhook doesn't trigger when deleting branch:**

- Make sure to check **only** "Push events" in GitLab
- Wait a few seconds after deleting branch
- Check "Recent events" in GitLab to see if webhook was triggered

**6. Manual webhook test:**

```bash
# Replace values with yours
curl -X POST https://your-app.com/forge-test-branches/webhook \
  -H "X-Gitlab-Event: Push Hook" \
  -H "X-Gitlab-Token: your-secret-token" \
  -H "Content-Type: application/json" \
  -d '{
    "ref": "refs/heads/feat/test-branch",
    "after": "0000000000000000000000000000000000000000"
  }'
```

If it returns `{"message":"Environment not found"}` it's working! (webhook is active, environment just doesn't exist in database)

### SSL not generated

Certificate is automatically generated after site creation. If it fails:

- Check if domain points to the server
- Wait for DNS propagation (a few minutes)

## Testing

```bash
composer test
composer test:coverage
composer analyse
```

## Changelog

See [CHANGELOG](CHANGELOG.md) for recent changes.

## Credits

- [Daniel Neto](https://github.com/danie1net0)
- [All Contributors](../../contributors)

## License

MIT License. See [LICENSE.md](LICENSE.md) for more information.
