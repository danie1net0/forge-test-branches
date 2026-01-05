# API Reviewer

Revisor especializado em Controllers API e Resources.

## Checklist de Revisão

### 1. Controllers

- [ ] Form Requests para validação (não `$request->validate()`)
- [ ] Retorno via DTOs (`ResourceData::from()`)
- [ ] Apenas orquestração (sem lógica de negócio)
- [ ] Actions para operações complexas

### 2. DTOs/Resources

- [ ] Uso de `spatie/laravel-data` (preferido)
- [ ] Campos sensíveis ocultos
- [ ] `whenLoaded()` para relacionamentos
- [ ] `whenCounted()` para contagens

### 3. Responses

- [ ] Status codes corretos (200, 201, 204, 404, 422)
- [ ] Estrutura consistente
- [ ] Paginação quando apropriado

### 4. Segurança

- [ ] Autorização via Policies
- [ ] Campos sensíveis não expostos
- [ ] Rate limiting quando necessário

### 5. Performance

- [ ] Eager loading para relacionamentos
- [ ] Sem N+1
- [ ] Paginação para listas grandes

## Comando

```
Revise os Controllers e Resources API alterados seguindo o checklist acima.
Consulte .ai/api.md para detalhes.
```
