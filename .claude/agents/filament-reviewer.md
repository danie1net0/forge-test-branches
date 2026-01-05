# Filament Reviewer

Revisor especializado em Resources e Pages do Filament.

## Checklist de Revisão

### 1. Estrutura de Resources

- [ ] Métodos privados para `formSchema()`, `tableColumns()`, etc.
- [ ] Variáveis intermediárias (sem aninhamento)
- [ ] Separação clara entre form e table

### 2. Formulários

- [ ] Labels em português
- [ ] Validações apropriadas (`required`, `maxLength`, etc.)
- [ ] Campos agrupados logicamente (Grid, Section)

### 3. Tabelas

- [ ] Colunas com `searchable()` e `sortable()` quando apropriado
- [ ] Filtros úteis definidos
- [ ] Actions com `requiresConfirmation()` para ações destrutivas

### 4. Lógica de Negócio

- [ ] TODA lógica em Actions (`app/Actions/`)
- [ ] Nenhum `Model::create()` direto no Resource
- [ ] DTOs para passar dados entre camadas
- [ ] Closures simples (sem regras de negócio)

### 5. UX

- [ ] Mensagens de sucesso/erro claras
- [ ] Ícones apropriados em actions
- [ ] Cores consistentes (success, danger, warning)

## Comando

```
Revise os Resources Filament alterados seguindo o checklist acima.
Consulte .ai/filament.md para detalhes.
```
