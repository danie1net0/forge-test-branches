# Testes - Essentials

## Princípios

- **SEMPRE use Pest** (nunca PHPUnit direto)
- Mensagens **sempre em português**
- Expectations **sempre encadeadas**
- Models **sempre com factories**

## Regra #1: Expectations Encadeadas

```php
// ✅ Sempre:
expect($resource)
    ->name->toBe('John')
    ->email->toBe('john@example.com')
    ->and($resource->isActive())->toBeTrue();

// ❌ Nunca:
expect($resource->name)->toBe('John');
expect($resource->email)->toBe('john@example.com');
```

## Estrutura de Diretórios

Estrutura DEVE espelhar o código testado:

```
app/Models/{Resource}.php                     → tests/Unit/Models/{Resource}Test.php
app/Services/{Provider}Service.php            → tests/Unit/Services/{Provider}ServiceTest.php
app/Actions/Create{Resource}Action.php        → tests/Unit/Actions/Create{Resource}ActionTest.php
app/Http/Controllers/Api/{Resource}Controller → tests/Feature/Api/{Resource}ControllerTest.php
```

## Sintaxe Básica

```php
it('retorna uma resposta bem-sucedida', function () {
    $this->get('/')
        ->assertStatus(200);
});

test('recurso pode ser criado', function () {
    $resource = {Resource}::factory()->create();
    $related = {Related}::factory()->create();

    $this->actingAs($resource)
        ->post('/resources', ['related_id' => $related->id])
        ->assertSuccessful();
});
```

## Expectations Comuns

```php
expect($value)
    ->toBeTrue()
    ->not->toBeNull()
    ->toBeGreaterThan(0);

expect($array)
    ->toHaveCount(5)
    ->toContain('item');

expect($string)
    ->toContain('substring')
    ->toStartWith('prefix');

// HTTP
$this->get('/api/resources')
    ->assertSuccessful()
    ->assertJsonCount(10, 'data');

// Database
expect({Resource}::count())->toBe(1);
$this->assertDatabaseHas('resources', ['email' => 'test@example.com']);
```

## Mocking

```php
use Mockery;

it('processa pagamento via serviço externo', function () {
    $mock = Mockery::mock({Provider}Service::class);
    $mock->shouldReceive('process')
        ->once()
        ->with(100.00)
        ->andReturn(true);

    app()->instance({Provider}Service::class, $mock);

    $action = app(Process{Resource}Action::class);
    $result = $action->execute(100.00);

    expect($result)->toBeTrue();
});
```

## Datasets

```php
it('valida status corretamente', function (string $status, bool $expected) {
    $resource = {Resource}::factory()->create(['status' => $status]);

    expect($resource->isActive())->toBe($expected);
})->with([
    ['active', true],
    ['inactive', false],
    ['pending', false],
]);
```

## Boas Práticas

```php
✅ it('descrição clara em português')
✅ expect($resource)->name->toBe('John')
✅ {Resource}::factory()->create()
✅ Arrange, Act, Assert

❌ it('funciona')
❌ Múltiplos expect() separados
❌ new {Resource}(['name' => 'John'])
```
