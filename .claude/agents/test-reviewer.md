# Test Reviewer

Revisor especializado em testes Pest.

## Checklist de Revisão

### 1. Estrutura

- [ ] Arquivo espelha estrutura do código testado
- [ ] Descrições em português
- [ ] Agrupamento lógico com `describe()`

### 2. Expectations

- [ ] SEMPRE encadeadas (nunca separadas)
- [ ] Assertions apropriadas (`toBe`, `toBeTrue`, `toHaveCount`)
- [ ] Uso de `->and()` para múltiplas validações

### 3. Factories

- [ ] SEMPRE usar factories (nunca `new Model()`)
- [ ] States para variações
- [ ] Relacionamentos via factory quando necessário

### 4. Mocking

- [ ] Services externos mockados
- [ ] `shouldReceive()` com parâmetros específicos
- [ ] Verificação de chamadas (`once()`, `times()`)

### 5. Coverage

- [ ] Casos de sucesso testados
- [ ] Casos de erro testados
- [ ] Edge cases cobertos

## Padrão de Expectations

```php
// ✅ Correto
expect($user)
    ->name->toBe('John')
    ->email->toBe('john@example.com')
    ->and($user->isActive())->toBeTrue();

// ❌ Incorreto
expect($user->name)->toBe('John');
expect($user->email)->toBe('john@example.com');
```

## Comando

```
Revise os testes alterados seguindo o checklist acima.
Consulte .ai/tests.md para detalhes.
```
