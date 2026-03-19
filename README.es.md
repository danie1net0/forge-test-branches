# Forge Test Branches

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/danie1net0/forge-test-branches/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/ddr/forge-test-branches/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ddr/forge-test-branches.svg?style=flat-square)](https://packagist.org/packages/ddr/forge-test-branches)

**Idiomas:** [English](README.en.md) | Español | [Português](README.pt-BR.md)

Crea entornos de prueba efímeros (review apps) desde ramas git en Laravel Forge. Ideal para validar características con stakeholders antes del merge.

## Propósito

Este paquete automatiza la creación y gestión de entornos de prueba en Laravel Forge. Elimina la configuración manual necesaria para crear sitios temporales desde ramas git.

Sin el paquete, crear un entorno de prueba requiere:

- Crear sitio manualmente en Forge
- Configurar repositorio git y rama
- Crear base de datos
- Crear usuario de base de datos
- Configurar variables de entorno
- Configurar SSL
- Despliegue inicial
- Recordar eliminar todo después

Con el paquete, ejecutas un comando y todo esto ocurre automáticamente. Cuando se elimina la rama, un webhook limpia los recursos.

## Qué hace

El paquete gestiona el ciclo completo de entornos de review:

**Creación**

1. Crea un sitio en Forge con dominio basado en el nombre de la rama
2. Instala el repositorio git en la rama especificada
3. Crea base de datos con prefijo configurable
4. Crea usuario de base de datos con acceso solo a la base creada
5. Configura variables de entorno personalizadas
6. Ejecuta script de deploy (migrations, composer, npm)
7. Configura certificado SSL Let's Encrypt
8. Habilita quick deploy (opcional)

**Destrucción**

- Webhook detecta cuando se elimina la rama
- Elimina sitio, base de datos y usuario de base de datos
- Limpia registros locales

## Casos de uso

### 1. Validación de características con product managers

Estás desarrollando una nueva interfaz de checkout. El PM necesita validar antes del merge a producción.

```bash
git checkout -b feat/new-checkout

# ... desarrolla la característica ...

# Crea entorno de prueba
php artisan forge-test-branches:create --branch=feat/new-checkout
```

Resultado: `https://feat-new-checkout.review.myapp.com` con su propia base de datos, SSL válido, listo para validación.

El PM accede al enlace, valida la característica. Después del merge, el webhook elimina el entorno automáticamente.

### 2. Pruebas con APIs externas

Característica de pago integrando con gateway. Cada rama necesita usar credenciales de sandbox.

```php
// config/forge-test-branches.php
'env_variables' => [
    'PAYMENT_GATEWAY_URL' => 'https://sandbox.gateway.com',
    'PAYMENT_GATEWAY_KEY' => 'sandbox_key_{slug}',
],
```

Cada entorno de review tiene sus propias credenciales, evitando interferencia entre pruebas.

### 3. Demostraciones para clientes

Agencia desarrollando características personalizadas. Cliente quiere ver progreso antes de la entrega.

```yaml
# .gitlab-ci.yml
review_app:
    stage: review
    script:
        - php artisan forge-test-branches:create
        - php artisan forge-test-branches:deploy
    when: manual
```

Developer crea rama, abre MR y hace clic en "Deploy Review". Cliente accede a entorno aislado sin afectar otros entornos.

### 4. Pruebas de migración de schema

Nueva migración que cambia estructura de base de datos. Necesita validar en entorno limpio antes del merge.

```bash
git checkout -b feat/add-user-preferences

# Crea entorno y ejecuta migrations automáticamente
php artisan forge-test-branches:create
```

El despliegue automático ejecuta las migrations. Si falla, corriges antes de hacer merge a develop/main.

### 5. Debugging de bugs en producción

Bug reportado en producción. Necesitas investigar con datos similares pero sin riesgo.

```bash
git checkout -b fix/payment-timeout production
php artisan forge-test-branches:create
```

Entorno idéntico a producción, con seeders de datos realistas. Investigas, aplicas fix, pruebas y validas antes de hacer merge.

### 6. Pruebas de rendimiento con datos reales

Característica de exportación de informes. Necesita probar con volumen de datos realista.

```php
// config/forge-test-branches.php
'deploy' => [
    'seed' => true,
    'seed_class' => 'PerformanceSeeder',
],
```

Cada despliegue ejecuta el seeder que crea 100k+ registros. Pruebas de rendimiento de forma aislada.

## Cómo funciona

```
CREACIÓN:
Rama creada → CI/CD dispara comandos → Sitio + BD + SSL creados
                                        ↓
                      https://branch-name.review.mysite.com

DESTRUCCIÓN:
Rama eliminada → Webhook dispara → Sitio + BD eliminados
```

## Requisitos

- PHP 8.2+
- Laravel 11+
- Cuenta Laravel Forge con API Token

## Instalación

```bash
composer require ddr/forge-test-branches
```

Instalación interactiva (recomendado):

```bash
php artisan forge-test-branches:install
```

Este comando:

- Publica configuración
- Configura variables de entorno
- Opcionalmente añade job a GitLab CI

## Configuración

Añade a `.env`:

```env
FORGE_API_TOKEN=tu-token-forge
FORGE_SERVER_ID=123456
FORGE_REVIEW_DOMAIN=review.mysite.com
FORGE_GIT_PROVIDER=gitlab
FORGE_GIT_REPOSITORY=usuario/repositorio
```

Configuración completa en `config/forge-test-branches.php`:

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
        'patterns' => ['*'], // o ['feat/*', 'fix/*']
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
        'script' => null, // null = script predeterminado de Forge
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
        // Variables personalizadas para el .env del sitio
        // 'APP_URL' => 'https://{slug}.review.mysite.com',
    ],
];
```

## Uso

### Comandos

```bash
# Crear entorno
php artisan forge-test-branches:create --branch=feat/nueva-caracteristica

# Desplegar actualizaciones
php artisan forge-test-branches:deploy --branch=feat/nueva-caracteristica

# Destruir entorno
php artisan forge-test-branches:destroy --branch=feat/nueva-caracteristica

# Actualizar script de deploy de un entorno existente
php artisan forge-test-branches:update-script --branch=feat/nueva-caracteristica

# Probar conexión con la API de Forge
php artisan forge-test-branches:test-connection

# Listar todos los entornos (muestra estado Active/Orphan)
php artisan forge-test-branches:list

# Listar solo entornos huérfanos
php artisan forge-test-branches:list --orphans

# Destruir todos los entornos huérfanos (con confirmación)
php artisan forge-test-branches:list --destroy-orphans
```

En CI/CD, la variable `CI_COMMIT_REF_NAME` se detecta automáticamente:

```bash
php artisan forge-test-branches:create
```

### Facade

```php
use Ddr\ForgeTestBranches\Facades\ForgeTestBranches;

// Crear
$env = ForgeTestBranches::create('feat/nueva-caracteristica');
echo $env->domain; // feat-nueva-caracteristica.review.mysite.com

// Verificar existencia
if (ForgeTestBranches::exists('feat/nueva-caracteristica')) {
    //
}

// Buscar
$env = ForgeTestBranches::find('feat/nueva-caracteristica');

// Desplegar
ForgeTestBranches::deploy('feat/nueva-caracteristica');

// Destruir
ForgeTestBranches::destroy('feat/nueva-caracteristica');

// Listar todos los entornos
$environments = ForgeTestBranches::listAll();
```

## Integración CI/CD

### GitLab

Añade a `.gitlab-ci.yml`:

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
        - php artisan forge-test-branches:create --branch=$CI_COMMIT_REF_NAME
        - php artisan forge-test-branches:deploy --branch=$CI_COMMIT_REF_NAME
    environment:
        name: review/$CI_COMMIT_REF_SLUG
        url: https://$CI_COMMIT_REF_SLUG.review.mysite.com
        on_stop: stop_review
    rules:
        - if: $CI_MERGE_REQUEST_ID
          when: manual

stop_review:
    stage: review
    image: php:8.4-cli
    before_script:
        - apt-get update && apt-get install -y git unzip
        - curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
        - composer install --no-interaction --prefer-dist
    script:
        - php artisan forge-test-branches:destroy --branch=$CI_COMMIT_REF_NAME
    environment:
        name: review/$CI_COMMIT_REF_SLUG
        action: stop
    rules:
        - if: $CI_MERGE_REQUEST_ID
          when: manual
```

### Webhook para Limpieza Automática

El webhook detecta cuando se elimina una rama y elimina automáticamente el entorno de review.

**Cómo funciona:**

- GitLab: Envía un Push Hook con `after: "0000000000000000000000000000000000000000"` cuando se elimina rama
- GitHub: Envía un evento `delete` cuando se elimina rama

**Configuración en GitLab:**

1. Ve a **Settings > Webhooks**
2. URL: `https://tu-app.com/forge-test-branches/webhook`
3. Secret token: **mismo valor que `FORGE_WEBHOOK_SECRET` en .env**
4. Marca solo: **Push events**
5. Desmarca "Enable SSL verification" si usas dominio de desarrollo
6. Haz clic en "Add webhook"

**Probar webhook:**
Después de configurar, haz clic en "Test" > "Push events" en GitLab. Deberías ver HTTP 200 y mensaje "Event ignored" o "Not a branch deletion" (normal, ya que la prueba no es una eliminación real).

**Configuración en GitHub:**

1. Settings > Webhooks
2. Payload URL: `https://tu-app.com/forge-test-branches/webhook`
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

## Configuraciones avanzadas

### Script de deploy personalizado

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

### Variables de entorno personalizadas

```php
'env_variables' => [
    'APP_ENV' => 'staging',
    'APP_DEBUG' => 'true',
    'APP_URL' => 'https://{slug}.review.mysite.com',
    'CACHE_PREFIX' => '{slug}_cache',
],
```

El placeholder `{slug}` se reemplaza por el nombre sanitizado de la rama.

### Filtros de rama

```php
'branch' => [
    'patterns' => ['feat/*', 'fix/*'],
],
```

Solo las ramas que coincidan con los patrones tendrán entornos creados.

### Limpieza de huérfanos

Los entornos quedan huérfanos cuando su rama se elimina sin activar el webhook (ej: eliminada vía merge request). El comando `list` detecta estos entornos comparando con las ramas remotas vía `git ls-remote`.

```bash
# Ver todos los entornos con estado
php artisan forge-test-branches:list

# Salida:
# +---------------------+-------------------------------------------+--------+---------+
# | Branch              | Domain                                    | Status | Site ID |
# +---------------------+-------------------------------------------+--------+---------+
# | feat/rama-activa    | feat-rama-activa.review.mysite.com        | Active | 123456  |
# | feat/rama-eliminada | feat-rama-eliminada.review.mysite.com     | Orphan | 123457  |
# +---------------------+-------------------------------------------+--------+---------+

# Destruir todos los huérfanos (pide confirmación)
php artisan forge-test-branches:list --destroy-orphans

# Destruir sin confirmación (para tareas programadas)
php artisan forge-test-branches:list --destroy-orphans --force
```

También puede programar la limpieza de huérfanos en `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('forge-test-branches:list --destroy-orphans --force')
    ->weekly();
```

### Logging

El paquete registra todas las operaciones en un canal dedicado. Los logs se graban en `storage/logs/forge-test-branches-YYYY-MM-DD.log` con rotación diaria.

Eventos registrados:

- Creación, destrucción y deploy de entornos
- Webhook recibido, procesado, ignorado o rechazado
- Fallos en la validación de firma

Configuración en `config/forge-test-branches.php`:

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

Defina `FORGE_LOG_ENABLED=false` para deshabilitar o `FORGE_LOG_LEVEL=info` para reducir la verbosidad.

### Database seeding

```php
'deploy' => [
    'seed' => true,
    'seed_class' => 'ReviewSeeder',
],
```

O vía `.env`:

```env
FORGE_SEED=true
FORGE_SEED_CLASS=ReviewSeeder
```

## Resolución de problemas

### "Site creation failed"

Verifica:

- `FORGE_API_TOKEN` está correcto
- `FORGE_SERVER_ID` existe y es accesible
- Dominio base está configurado en DNS

### "Database creation failed"

Verifica:

- Servidor tiene MySQL/PostgreSQL instalado
- Prefijo de base de datos no conflictúa con bases existentes

### Webhook no funciona

**1. Verifica si el webhook está siendo llamado:**

- En GitLab: Settings > Webhooks > clic en webhook > "Recent events"
- Ve si hay requests y qué código de estado se devolvió

**2. HTTP 401 - Unauthorized:**

- `FORGE_WEBHOOK_SECRET` en `.env` debe ser EXACTAMENTE igual al configurado en GitLab
- Verifica espacios en blanco o caracteres extra
- O elimina el secret: deja `FORGE_WEBHOOK_SECRET=` vacío en `.env`

**3. HTTP 404 - Not Found:**

- Verifica si `FORGE_WEBHOOK_ENABLED=true` en `.env`
- Ejecuta `php artisan config:clear`
- Ejecuta `php artisan route:list | grep webhook` para ver si existe la ruta

**4. HTTP 500 - Server Error:**

- Verifica los logs de la aplicación: `tail -f storage/logs/laravel.log`

**5. Webhook no dispara al eliminar rama:**

- Asegúrate de marcar **solo** "Push events" en GitLab
- Espera algunos segundos después de eliminar la rama
- Verifica en "Recent events" en GitLab si se disparó el webhook

**6. Test manual del webhook:**

```bash
# Reemplaza los valores por los tuyos
curl -X POST https://tu-app.com/forge-test-branches/webhook \
  -H "X-Gitlab-Event: Push Hook" \
  -H "X-Gitlab-Token: tu-secret-token" \
  -H "Content-Type: application/json" \
  -d '{
    "ref": "refs/heads/feat/test-branch",
    "after": "0000000000000000000000000000000000000000"
  }'
```

Si devuelve `{"message":"Environment not found"}` ¡está funcionando! (el webhook está activo, solo que el entorno no existe en la base de datos)

### SSL no se genera

El certificado se genera automáticamente después de la creación del sitio. Si falla:

- Verifica si el dominio apunta al servidor
- Espera la propagación DNS (algunos minutos)

## Testing

```bash
composer test
composer test:coverage
composer analyse
```

## Changelog

Ver [CHANGELOG](CHANGELOG.md) para cambios recientes.

## Créditos

- [Daniel Neto](https://github.com/danie1net0)
- [Todos los Contribuidores](../../contributors)

## Licencia

MIT License. Ver [LICENSE.md](LICENSE.md) para más información.
