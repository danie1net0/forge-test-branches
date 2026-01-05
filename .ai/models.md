# Models - Essentials

## Estrutura Base

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class {Resource} extends Model
{
    // 1. Traits
    use HasFactory, SoftDeletes;

    // 2. Configuração
    protected $table = 'resources';
    protected $primaryKey = 'id';

    // 3. Mass Assignment
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // 4. Casts
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 5. Relationships
    public function related(): HasMany
    {
        return $this->hasMany({Related}::class);
    }

    // 6. Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // 7. Accessors/Mutators
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => ucfirst($value),
        );
    }

    // 8. Business Logic
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
```

## Relationships

```php
// HasMany
public function children(): HasMany
{
    return $this->hasMany({Child}::class);
}

// BelongsTo
public function parent(): BelongsTo
{
    return $this->belongsTo({Parent}::class);
}

// BelongsToMany
public function related(): BelongsToMany
{
    return $this->belongsToMany({Related}::class)
        ->withPivot('active')
        ->withTimestamps();
}

// HasOne
public function detail(): HasOne
{
    return $this->hasOne({Detail}::class);
}

// MorphMany
public function comments(): MorphMany
{
    return $this->morphMany({Comment}::class, 'commentable');
}
```

## Scopes

```php
// Query Scope
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

// Uso:
{Resource}::active()->get();

// Scope com parâmetro
public function scopeOfType($query, string $type)
{
    return $query->where('type', $type);
}

// Uso:
{Resource}::ofType('premium')->get();
```

## Casts

```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'status' => {Resource}Status::class,  // Enum
    ];
}
```

## Accessors/Mutators

```php
// Accessor
protected function firstName(): Attribute
{
    return Attribute::make(
        get: fn ($value, $attributes) => explode(' ', $attributes['name'])[0],
    );
}

// Mutator
protected function password(): Attribute
{
    return Attribute::make(
        set: fn ($value) => Hash::make($value),
    );
}

// Ambos
protected function name(): Attribute
{
    return Attribute::make(
        get: fn ($value) => ucfirst($value),
        set: fn ($value) => strtolower($value),
    );
}
```

## Query Builders Customizados

```php
// App/Models/Builders/{Resource}Builder.php
class {Resource}Builder extends Builder
{
    public function active(): self
    {
        return $this->where('status', 'active');
    }

    public function verified(): self
    {
        return $this->whereNotNull('email_verified_at');
    }
}

// Model
public function newEloquentBuilder($query): {Resource}Builder
{
    return new {Resource}Builder($query);
}

// Uso com type hints
{Resource}::query()->active()->verified()->get();
```

## Factories

```php
class {Resource}Factory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }

    public function admin(): static
    {
        return $this->state([
            'role' => 'admin',
        ]);
    }
}

// Uso:
{Resource}::factory()->count(10)->create();
{Resource}::factory()->admin()->create();
```

## Performance

**Eager Loading:**

```php
// ✅ N+1 resolvido
$resources = {Resource}::with('related')->get();

// ✅ Nested
$resources = {Resource}::with('related.children')->get();

// ✅ Conditional
$resources = {Resource}::with(['related' => fn($q) => $q->active()])->get();
```

**Select específico:**

```php
{Resource}::select('id', 'name', 'email')->get();
```

**Chunking:**

```php
{Resource}::chunk(100, function ($resources) {
    foreach ($resources as $resource) {
        // Process
    }
});
```

## Observação

- Usar `fillable` (whitelist) ao invés de `guarded` (blacklist)
- Sempre type hint relationships
- Evitar lógica complexa em models
- Preferir Query Builders para queries complexas
- Usar Factories para testes
- Cuidado com N+1 (sempre usar `with()`)
