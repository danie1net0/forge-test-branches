# Revisar

Revisa os arquivos alterados no projeto.

## Uso

```
/revisar [escopo]
```

## Escopos

- `php` - Apenas arquivos PHP
- `models` - Apenas Models
- `filament` - Apenas Resources Filament
- `api` - Apenas Controllers e Resources API
- `tests` - Apenas testes
- (vazio) - Todos os arquivos alterados

## Processo

1. Identificar arquivos alterados (`git diff --name-only`)
2. Classificar por tipo
3. Aplicar checklist apropriado
4. Reportar problemas encontrados

## Checklist Geral

### PHP

- [ ] `declare(strict_types=1);`
- [ ] Type hints completos
- [ ] Early returns (sem else)
- [ ] Imports organizados
- [ ] Sem ifs aninhados2

### Arquitetura

- [ ] Controllers magros
- [ ] Lógica em Actions
- [ ] DTOs entre camadas

### Commits

- [ ] Formato: `tipo(escopo): mensagem`
- [ ] Mensagem em português
- [ ] Sem `Co-Authored-By`

## Output

Para cada arquivo:

```
✅ arquivo.php - OK
⚠️ arquivo.php - 2 avisos
❌ arquivo.php - 1 erro (bloqueia commit)
```
