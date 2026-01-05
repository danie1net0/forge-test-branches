# Arquitetura - Essentials

## Princípios

- **Controllers magros** - apenas coordenam
- **Models focados** - lógica de domínio
- **Actions para negócio** - lógica complexa isolada
- **Services para externo** - APIs e integrações via Saloon
- **DTOs com spatie/laravel-data** - sempre que instalado
- **DTOs entre camadas** - sempre usar DTOs ao passar múltiplos atributos entre classes de camadas diferentes
- **Single Responsibility** - uma classe, uma responsabilidade

## Estrutura

```
app/
├── Actions/              # Lógica de negócio
├── Services/             # Orquestra integrações (usa Connectors do Saloon)
├── Integrations/         # Saloon Connectors e Requests
│   └── {Provider}/
│       ├── {Provider}Connector.php
│       ├── {Action}Request.php
│       ├── Data/
│       │   └── {Resource}Data.php
│       ├── Exceptions/
│       │   └── {Provider}Exception.php
│       └── Concerns/
│           └── {Trait}.php
├── DTOs/                 # Data Transfer Objects (uso geral)
├── Enums/                # Valores fixos
├── Jobs/                 # Trabalhos em fila
├── Events/               # Eventos
├── Listeners/            # Ouvintes de eventos
├── Observers/            # Observers de models
└── Traits/               # Traits reutilizáveis
```

## Actions

**Quando usar:**

- Lógica de negócio complexa
- Operações multi-step
- Código reutilizável
- Múltiplos models envolvidos

**Nomenclatura:** `[Verbo][Substantivo]Action`

```php
class Create{Resource}Action
{
    public function __construct(
        private {Provider}Service $service
    ) {}

    public function execute(Create{Resource}Data $data): {Resource}
    {
        $externalResource = $this->service->create(
            $data->externalId,
            $data->planId
        );

        $resource = {Resource}::create([
            'external_id' => $externalResource->id,
            'status' => 'active',
        ]);

        event(new {Resource}Created($resource));

        return $resource;
    }
}
```

## Services

**Quando usar:**

- APIs externas
- Envio de emails
- Upload de arquivos
- Comunicação externa

**⚠️ IMPORTANTE:** Sempre use [Saloon](https://docs.saloon.dev/) para integrações com APIs externas.

```php
use Saloon\Http\Connector;

class {Provider}Connector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://api.provider.com/v1';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . config('services.provider.secret'),
        ];
    }
}

use Saloon\Http\Request;
use Saloon\Enums\Method;

class Create{Resource}Request extends Request
{
    protected Method $method = Method::POST;

    public function __construct(
        private string $externalId,
        private string $planId,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/resources';
    }

    protected function defaultBody(): array
    {
        return [
            'external_id' => $this->externalId,
            'plan_id' => $this->planId,
        ];
    }
}

class {Provider}Service
{
    public function __construct(
        private {Provider}Connector $connector,
    ) {}

    public function create(string $externalId, string $planId): {Resource}Data
    {
        $response = $this->connector->send(
            new Create{Resource}Request($externalId, $planId)
        );

        return {Resource}Data::from($response->json());
    }
}
```

## DTOs

**⚠️ IMPORTANTE:** Sempre use `spatie/laravel-data` quando instalado.

**Regras:**

- Sempre criar DTOs em classes separadas ao passar múltiplos atributos entre camadas diferentes (Controller → Action, Service → Action, etc.)
- Use DTOs ou collections de DTOs como retorno de Controllers (ver [laravel-data as resource](https://spatie.be/docs/laravel-data/v4/as-a-resource/from-data-to-resource))

### Regras por Camada

**Actions/Services:**

- Aceitar IDs como parâmetro, resolver Model internamente
- Se o caller só tem o ID, forçá-lo a buscar o Model é inversão de responsabilidade
- O método que precisa do Model deve ser responsável por resolvê-lo

```php
// ❌ EVITAR - obriga caller a buscar o Model
public function execute(User $user): void
{
    // ...
}

// ✅ CORRETO - aceita ID e resolve internamente
public function execute(int $userId): void
{
    $user = User::query()->findOrFail($userId);
    // ...
}
```

**Presenters/Formatters:**

- Aceitar View DTOs, NUNCA Models nem IDs
- Não devem ter responsabilidade de buscar dados
- Recebem dados prontos para apresentação

```php
// ❌ EVITAR - presenter buscando dados
public function format(int $taskId): string
{
    $task = Task::query()->findOrFail($taskId);
    // ...
}

// ❌ EVITAR - presenter recebendo Model
public function format(Task $task): string
{
    // ...
}

// ✅ CORRETO - presenter recebe View DTO
public function format(TaskViewData $task): string
{
    return "{$task->status->getEmoji()} {$task->title}";
}
```

**DTOs de Entrada (Create/Update):**

```php
use Spatie\LaravelData\Data;

class Create{Resource}Data extends Data
{
    public function __construct(
        public int $userId,
        public int $planId,
        public ?string $couponCode = null,
    ) {}
}

// Uso:
$data = Create{Resource}Data::from($request->all());
```

**View DTOs (para Presenters/Formatters):**

```php
class {Resource}ViewData extends Data
{
    public function __construct(
        public int $id,
        public string $title,
        public string $statusLabel,
        public bool $isActive,
    ) {}

    public static function fromModel({Resource} $resource): self
    {
        return new self(
            id: $resource->id,
            title: $resource->title,
            statusLabel: $resource->status->getLabel(),
            isActive: $resource->isActive(),
        );
    }
}

// Uso no Presenter:
class {Resource}Presenter
{
    public function format({Resource}ViewData $data): string
    {
        return "{$data->statusLabel}: {$data->title}";
    }
}

// Caller converte Model → ViewData antes de chamar:
$viewData = {Resource}ViewData::fromModel($resource);
$presenter->format($viewData);
```

**Retorno de Controllers com Data:**

```php
class {Resource}Controller extends Controller
{
    public function show({Resource} $resource): {Resource}Data
    {
        return {Resource}Data::from($resource);
    }

    public function index(): DataCollection
    {
        return {Resource}Data::collect({Resource}::paginate());
    }
}
```

## Enums

```php
enum {Resource}Status: string
{
    case Active = 'active';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Ativo',
            self::Cancelled => 'Cancelado',
            self::Expired => 'Expirado',
        };
    }
}
```

## Jobs

```php
class Send{Action}EmailJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;

    public function __construct(
        public {Resource} $resource
    ) {}

    public function handle(): void
    {
        Mail::to($this->resource->email)
            ->send(new {Action}Mail($this->resource));
    }
}

// Dispatch:
Send{Action}EmailJob::dispatch($resource);
```

## Actions vs Services

| Aspecto                       | Actions                                            | Services                                             |
| ----------------------------- | -------------------------------------------------- | ---------------------------------------------------- |
| **Responsabilidade**          | Lógica de negócio                                  | Comunicação externa                                  |
| **Exemplos**                  | Criar recurso, calcular valor, aprovar solicitação | Chamar API externa, enviar email, upload de arquivos |
| **Acessa banco?**             | ✅ Leitura e escrita                               | ✅ Apenas leitura (quando necessário)                |
| **Chama APIs externas?**      | ❌ Não (usa Services)                              | ✅ Sim (via Saloon)                                  |
| **Contém regras de negócio?** | ✅ Sim                                             | ❌ Não                                               |
| **Pode chamar outros?**       | Actions podem chamar Services                      | Services não chamam Actions                          |

**Fluxo típico:**

```
Controller → Action → Service → API Externa
                ↓
              Model
```

## Regras de Decisão

**Controllers:**

- ✅ Validar entrada
- ✅ Chamar Actions/Services
- ✅ Retornar DTOs/Data como response
- ❌ Lógica de negócio (sempre em Actions)
- ❌ Queries diretas

**Models:**

- ✅ Relationships
- ✅ Scopes
- ✅ Casts
- ❌ Lógica complexa
- ❌ Integrações externas

**Actions:**

- ✅ Lógica de negócio (OBRIGATÓRIO)
- ✅ Orquestração
- ✅ Reutilização
- ❌ APIs externas

**Services:**

- ✅ APIs externas via Saloon
- ✅ Infraestrutura
- ✅ Saloon Requests encapsulados
- ❌ Lógica de negócio
- ❌ Queries de banco

**DTOs:**

- ✅ Passar múltiplos atributos entre camadas
- ✅ Retorno de Controllers (Data as Resource)
- ✅ Classes separadas (não inline)
- ❌ Usar arrays associativos entre camadas

**Actions/Services:**

- ✅ Aceitar IDs como parâmetro
- ✅ Resolver Models internamente
- ❌ Receber Models como parâmetro

**Presenters/Formatters:**

- ✅ Aceitar View DTOs
- ❌ Receber Models
- ❌ Receber IDs (não devem buscar dados)
