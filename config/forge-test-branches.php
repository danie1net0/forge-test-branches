<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Forge API Token
    |--------------------------------------------------------------------------
    |
    | Laravel Forge API authentication token. Can be generated at:
    | https://forge.laravel.com/user-profile/api
    |
    */

    'forge_api_token' => env('FORGE_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Server ID
    |--------------------------------------------------------------------------
    |
    | The Forge server ID where review environments will be created.
    | Can be found in the server URL: forge.laravel.com/servers/{id}
    |
    */

    'server_id' => env('FORGE_SERVER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Domain Configuration
    |--------------------------------------------------------------------------
    |
    | Domain configuration for review environments.
    |
    | - base: Base domain (e.g., review.mysite.com)
    | - pattern: Pattern to generate the final domain. Supports:
    |   - {branch}: sanitized branch slug
    |   - {base}: configured base domain
    |
    | Example: feat-login.review.mysite.com
    |
    */

    'domain' => [
        'base' => env('FORGE_REVIEW_DOMAIN'),
        'pattern' => '{branch}.{base}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Git Configuration
    |--------------------------------------------------------------------------
    |
    | Git repository configuration to be cloned in review environments.
    |
    | - provider: Git provider (gitlab, github, bitbucket)
    | - repository: Repository in user/repo format
    |
    */

    'git' => [
        'provider' => env('FORGE_GIT_PROVIDER', 'gitlab'),
        'repository' => env('FORGE_GIT_REPOSITORY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Branch Configuration
    |--------------------------------------------------------------------------
    |
    | Branch filtering configuration for review environments.
    |
    | - patterns: Array of glob patterns to match allowed branches.
    |             Use '*' to allow all branches (default).
    |             Examples: ['feat/*', 'review/*', 'fix/*']
    |
    */

    'branch' => [
        'patterns' => ['*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Database configuration for review environments.
    |
    | - prefix: Prefix for database and user name (e.g., review_feat_login)
    |
    */

    'database' => [
        'prefix' => env('FORGE_DB_PREFIX', 'review_'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Configuration
    |--------------------------------------------------------------------------
    |
    | Site configuration to be created on Forge.
    |
    | - php_version: PHP version (php81, php82, php83, php84)
    | - project_type: Project type (php, html, symfony, symfony_dev, symfony_four)
    | - directory: Site public directory
    | - isolated: Whether the site should run in isolated mode (dedicated user)
    |
    */

    'site' => [
        'php_version' => env('FORGE_PHP_VERSION', 'php84'),
        'project_type' => env('FORGE_PROJECT_TYPE', 'php'),
        'directory' => env('FORGE_WEB_DIRECTORY', '/public'),
        'isolated' => env('FORGE_ISOLATED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deploy Configuration
    |--------------------------------------------------------------------------
    |
    | Deploy configuration for review environments.
    |
    | - script: Custom deploy script. If null, uses the default script below.
    |           Supports {branch} placeholder which is replaced with the branch name.
    | - quick_deploy: Enables automatic deploy via repository push.
    | - seed: Run database seeders after migrations (default: false)
    | - seed_class: Specific seeder class to run (null = DatabaseSeeder)
    |
    | Default script (when script is null):
    |
    |   cd $FORGE_SITE_PATH
    |   git fetch origin {branch}
    |   git reset --hard origin/{branch}
    |   git clean -fd
    |
    |   $FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
    |
    |   ( flock -w 10 9 || exit 1
    |       echo 'Restarting FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9>/tmp/fpmlock
    |
    |   if [ -f artisan ]; then
    |       $FORGE_PHP artisan migrate --force
    |       $FORGE_PHP artisan config:cache
    |       $FORGE_PHP artisan route:cache
    |       $FORGE_PHP artisan view:cache
    |   fi
    |
    */

    'deploy' => [
        'script' => null,
        'quick_deploy' => true,
        'seed' => env('FORGE_SEED', false),
        'seed_class' => env('FORGE_SEED_CLASS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Webhook configuration for automatic environment destruction.
    | When a branch is deleted on GitLab, the corresponding environment
    | is automatically destroyed.
    |
    | - enabled: Enables the webhook endpoint
    | - secret: Secret token to validate requests (X-Gitlab-Token header)
    | - path: Webhook endpoint path
    |
    */

    'webhook' => [
        'enabled' => env('FORGE_WEBHOOK_ENABLED', true),
        'secret' => env('FORGE_WEBHOOK_SECRET'),
        'path' => 'forge-test-branches/webhook',
    ],

    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    |
    | SSL certificate configuration for review environments.
    |
    | - enabled: Whether to automatically obtain SSL certificates (default: true)
    | - type: Certificate type (letsencrypt)
    |
    | When enabled, a Let's Encrypt SSL certificate will be obtained
    | automatically when creating a new review environment.
    |
    */

    'ssl' => [
        'enabled' => env('FORGE_SSL_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Variables
    |--------------------------------------------------------------------------
    |
    | Additional environment variables to be set in the .env file
    | of the review environment. Supports the {slug} placeholder which
    | will be replaced with the branch slug.
    |
    | Example:
    | 'env_variables' => [
    |     'APP_URL' => 'https://{slug}.review.mysite.com',
    |     'CACHE_PREFIX' => '{slug}_cache',
    | ],
    |
    */

    'env_variables' => [],
];
