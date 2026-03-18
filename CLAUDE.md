# Regras do Projeto

## Comunicação

- Responda **sempre em português brasileiro**
- Código, variáveis, commits e comentários técnicos em **inglês**
- Labels de UI, mensagens ao usuário e textos visíveis em **português**

## Ciclo Obrigatório

Antes de entregar qualquer código:

1. **Ler** a skill correspondente à tarefa (`.claude/skills/`)
2. **Verificar** arquivos similares no projeto para manter consistência
3. **Gerar** código seguindo os padrões
4. **Auto-revisar** usando o checklist de self-review
5. **Validar** com linter e análise estática
6. Só então **entregar**

## Commits

Formato: `tipo(escopo): mensagem em português`

Tipos: `feat`, `fix`, `refactor`, `style`, `docs`, `test`, `chore`, `perf`

Regras:

- Verbo imperativo 3ª pessoa: adiciona, corrige, remove, atualiza
- Máximo 72 caracteres na primeira linha
- Escopo obrigatório quando possível
- Corpo opcional para contexto adicional

Exemplos:

- `feat(users): adiciona endpoint de exportação CSV`
- `fix(auth): corrige validação de token expirado`
- `refactor(orders): extrai lógica de cálculo para Action`

## Regras Inegociáveis

1. **Tudo tipado** — parâmetros, retornos, propriedades, variáveis e callbacks (inclusive parâmetros e retorno de callbacks)
2. **Sem ifs aninhados, sem else, sem elseif** — early returns SEMPRE
3. **Sem nomes abreviados** — atributos, parâmetros, variáveis, constantes e métodos com nomes completos e descritivos
4. **Componentização** — separar responsabilidades e reaproveitar comportamentos (classes, componentes, funções)
5. **Sem comentários desnecessários** — código deve ser autoexplicativo; comentários só quando realmente necessários
6. **Visibilidade explícita** — todos métodos e atributos com `public`, `private` ou `protected` declarados (onde a linguagem suportar)

## Princípios

- **DRY** - Não repita código; extraia para funções/classes
- **KISS** - Soluções simples primeiro
- **Testes** - Código novo = teste novo

## PHP / Laravel

### Obrigatório em Todo Arquivo PHP

```php
<?php

declare(strict_types=1);
```

### Padrões

- **PSR-12** + Laravel Pint
- **PHPStan nível 5+**
- **Type hints** em todos os parâmetros, retornos e propriedades
- **Constructor property promotion** sempre
- **Comparações strict** (`===`, `!==`, `in_array(..., true)`)
- **ZERO** `else`/`elseif` — usar early returns
- **ZERO** ifs aninhados

### Arquitetura

- **Controllers** orquestram (recebem request → chamam action → retornam response)
- **Actions** orquestram lógica de negócio (uma ação = um método `execute`)
- **Models** = rich domain model (scopes, relationships, accessors, casts)
- **Services** = integrações com APIs externas
- **DTOs** (Spatie Laravel Data) entre camadas, atributos em camelCase
- `Model::query()` sempre (nunca `DB::`)

### Nomenclatura

- Classes: `PascalCase` (`CreateOrderAction`, `PaymentService`)
- Métodos: `camelCase`, verbos (`create`, `isActive`, `getByEmail`)
- Variáveis: `camelCase`, descritivas (`$externalCustomerId`)
- Enums: `UPPER_SNAKE_CASE` para valores

### Validação

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M
php artisan test --filter=NomeDaFeature
```
