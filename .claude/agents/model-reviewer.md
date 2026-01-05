# Model Reviewer

Revisor especializado em Eloquent Models.

## Checklist de Revisão

### 1. Estrutura do Model

- [ ] Ordem: Traits → Config → Fillable → Hidden → Casts → Relations → Scopes → Accessors → Methods
- [ ] `$fillable` definido (não `$guarded`)
- [ ] `$hidden` para campos sensíveis

### 2. Casts

- [ ] Datas com `'datetime'`
- [ ] Booleans com `'boolean'`
- [ ] Arrays com `'array'`
- [ ] Enums com classe do Enum

### 3. Relationships

- [ ] Type hints corretos (`HasMany`, `BelongsTo`, etc.)
- [ ] Nomes descritivos (plural para many, singular para one)
- [ ] `withPivot()` quando necessário

### 4. Scopes

- [ ] Prefixo `scope` no método
- [ ] Reutilizáveis e composáveis
- [ ] Documentados se complexos

### 5. Performance

- [ ] Eager loading quando necessário
- [ ] Sem N+1 em relacionamentos
- [ ] Indexes nas colunas de busca

## Comando

```
Revise os Models alterados seguindo o checklist acima.
Consulte .ai/models.md para detalhes.
```
