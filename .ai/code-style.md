# Code Style - Essentials

## Padrões

- **PSR-12** - Padrão oficial PHP
- **Laravel Pint** - Formatação automática
- **PHPStan nível 5+** - Análise estática
- **Strict types** - Sempre declarar
- **Type hints** - Obrigatório em tudo

## Nomenclatura

**Classes:**

```php
{Resource}Controller        // Controllers
Create{Resource}Action      // Actions
{Provider}Service           // Services
Create{Resource}Data        // DTOs
{Resource}Status            // Enums
```

**Métodos:**

```php
public function getById(int $id): {Resource}      // camelCase
public function create(): void                    // verbos
public function isActive(): bool                  // predicados com 'is/has'
```

**Variáveis:**

```php
$resourceId = 1;          // camelCase
$externalCustomerId = ''; // descritivo
```

## Type Safety

**Sempre usar:**

```php
<?php

declare(strict_types=1);

namespace App\Actions;

class Create{Resource}Action
{
    public function execute(Create{Resource}Data $data): {Resource}
    {
        return {Resource}::create([
            'name' => $data->name,
            'email' => $data->email,
        ]);
    }
}
```

## Early Returns

**Preferir:**

```php
public function store(Request $request): Response
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    if (!$request->has('email')) {
        return back()->withErrors(['email' => 'Required']);
    }

    $user = $this->createUser($request->validated());

    return redirect()->route('users.show', $user);
}
```

**Evitar:**

```php
public function store(Request $request): Response
{
    if (auth()->check()) {
        if ($request->has('email')) {
            $user = $this->createUser($request->validated());
            return redirect()->route('users.show', $user);
        } else {
            return back()->withErrors(['email' => 'Required']);
        }
    } else {
        return redirect()->route('login');
    }
}
```

## Named Arguments

```php
// Usar quando melhora legibilidade
$resource = $service->create(
    externalId: $model->external_id,
    planId: $plan->id,
    quantity: 1
);
```

## Imports

**Sempre use imports diretos, nunca namespace aliases:**

```php
<?php

namespace App\Http\Controllers;

// ✅ Correto - imports diretos
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Infolists\Components\{TextEntry, IconEntry};
use App\Actions\Create{Resource}Action;
use App\Models\{Resource};

// ❌ Errado - namespace aliases
use Filament\Tables;
use Filament\Infolists\Components as InfolistComponents;
// Depois usando: Tables\Columns\TextColumn ou InfolistComponents\TextEntry
```

**Organizar por categoria:**

```php
// 1. PHP nativo / Framework
use Illuminate\Http\Request;
use Illuminate\Http\Response;

// 2. Vendor (terceiros)
use Spatie\LaravelData\Data;
use Filament\Tables\Columns\{TextColumn, IconColumn};

// 3. App (próprio código)
use App\Actions\Create{Resource}Action;
use App\Models\{Resource};
```

## Arrays

**Preferir sintaxe curta:**

```php
$users = ['John', 'Jane'];  // ✅
$users = array('John', 'Jane');  // ❌

// Trailing comma em multiline
$data = [
    'name' => 'John',
    'email' => 'john@example.com',  // ← trailing comma
];
```

## Strings

**Usar aspas simples quando possível:**

```php
$name = 'John';           // ✅
$name = "John";           // ❌
$name = "Hello $user";    // ✅ (interpolação)
```

## Comparações

**Usar strict:**

```php
if ($status === 'active') {}     // ✅
if ($status == 'active') {}      // ❌

if (in_array($id, $ids, true)) {} // ✅
```

## Null Coalescing

```php
$name = $request->input('name') ?? 'Guest';

$user->name ??= 'Guest';  // Assign if null
```

## Métodos Estáticos

**Evitar quando possível. Preferir injeção:**

```php
// Preferir:
public function __construct(
    private {Provider}Service $service
) {}

// Evitar:
{Provider}Service::create();
```

## Comentários

**Apenas quando necessário:**

```php
// ❌ Óbvio
// Get resource by ID
public function getById(int $id): {Resource}

// ✅ Útil
// Sync resource with external provider and update local status
public function syncWithProvider({Resource} $resource): void
```
