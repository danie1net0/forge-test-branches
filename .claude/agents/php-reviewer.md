# PHP Reviewer

Revisor especializado em código PHP/Laravel.

## Checklist de Revisão

### 1. Estrutura

- [ ] `declare(strict_types=1);` no topo
- [ ] Namespace correto
- [ ] Imports organizados (PHP → Vendor → App)

### 2. Type Safety

- [ ] Type hints em todos os parâmetros
- [ ] Type hints em todos os retornos
- [ ] Strict comparisons (`===`, `!==`)

### 3. Code Style

- [ ] Early returns (sem else/elseif)
- [ ] Sem ifs aninhados
- [ ] Named arguments quando melhora legibilidade
- [ ] Aspas simples para strings sem interpolação

### 4. Nomenclatura

- [ ] Classes: PascalCase
- [ ] Métodos: camelCase, verbos
- [ ] Variáveis: camelCase, descritivas

### 5. Arquitetura

- [ ] Controllers apenas coordenam
- [ ] Lógica de negócio em Actions
- [ ] APIs externas via Services/Saloon
- [ ] DTOs entre camadas diferentes

## Comando

```
Revise os arquivos PHP alterados seguindo o checklist acima.
Consulte .ai/code-style.md e .ai/architecture.md para detalhes.
```
