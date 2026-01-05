# API Resources - Essentials

## Quando Usar

✅ **SEMPRE use DTOs (spatie/laravel-data)** para retornar dados JSON de Controllers.

**⚠️ IMPORTANTE:** Prefira usar `spatie/laravel-data` como resource em vez de `JsonResource` nativo do Laravel. Ver [Data as Resource](https://spatie.be/docs/laravel-data/v4/as-a-resource/from-data-to-resource).

## Com spatie/laravel-data (Preferido)

```php
use Spatie\LaravelData\Data;

class {Resource}Data extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $created_at,
    ) {}

    public static function fromModel({Resource} $resource): self
    {
        return new self(
            id: $resource->id,
            name: $resource->name,
            email: $resource->email,
            created_at: $resource->created_at?->toISOString(),
        );
    }
}

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

## Com JsonResource (Alternativa)

```bash
php artisan make:resource {Resource}Resource
```

```php
class {Resource}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}

// Uso:
return new {Resource}Resource($resource);
return {Resource}Resource::collection($resources);
```

## Atributos Condicionais

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,

        // Apenas para admin
        'email_verified_at' => $this->when(
            $request->user()?->isAdmin(),
            $this->email_verified_at
        ),

        // Múltiplos campos sensíveis
        $this->mergeWhen($request->user()?->isAdmin(), [
            'last_login_at' => $this->last_login_at,
            'external_customer_id' => $this->external_customer_id,
        ]),
    ];
}
```

## Relacionamentos (Evita N+1)

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'title' => $this->title,

        // Apenas se foi eager loaded
        'author' => {Author}Resource::make($this->whenLoaded('author')),
        'comments' => {Comment}Resource::collection($this->whenLoaded('comments')),

        // Contagens
        'comments_count' => $this->whenCounted('comments'),
    ];
}

// Controller:
$resource->load(['author', 'comments']);
return new {Resource}Resource($resource);
```

## Paginação

```php
public function index()
{
    $resources = {Resource}::paginate(15);
    return {Resource}Resource::collection($resources);
}
```

## Boas Práticas

```php
✅ return {Resource}Data::from($resource);              // Preferido
✅ return {Resource}Data::collect($resources);          // Collections
✅ return new {Resource}Resource($resource);            // Alternativa
✅ Use whenLoaded() para relacionamentos
✅ Use whenCounted() para contagens
✅ Oculte dados sensíveis com when()

❌ return response()->json($resource);
❌ return $resource->toArray();
❌ 'relations' => $this->relations (causa N+1)
❌ Expor passwords, tokens
```
