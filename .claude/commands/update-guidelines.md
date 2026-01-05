# Update Guidelines

Atualiza os guidelines do Laravel instalados no projeto com as versões mais recentes do repositório.

## O que faz

Este comando:

1. Faz backup opcional dos guidelines atuais
2. Baixa as versões mais recentes dos guidelines
3. Atualiza o Laravel Boost (se instalado)
4. Mostra resumo das mudanças

## Instruções

### 1. Verificar se é um Projeto Laravel

```bash
ls artisan composer.json
```

Se não existirem, informe que este comando só funciona em projetos Laravel.

### 2. Verificar Guidelines Instalados

```bash
ls -la .ai/guidelines/
```

Se o diretório não existir, informe ao usuário:

```
❌ Guidelines não encontrados!

Parece que os guidelines ainda não foram instalados neste projeto.

Para instalar os guidelines, execute:
/setup-laravel-boost
```

E pare a execução.

### 3. Fazer Backup (Opcional)

Pergunte ao usuário:

```
📦 Deseja fazer backup dos guidelines atuais?

Se você tem guidelines customizados, é recomendado fazer backup.

Opções:
1. Sim - Fazer backup em .ai/guidelines.backup
2. Não - Sobrescrever diretamente
```

**Se sim:**

```bash
rm -rf .ai/guidelines.backup 2>/dev/null || true
cp -r .ai/guidelines .ai/guidelines.backup
echo "✅ Backup criado em .ai/guidelines.backup"
```

### 4. Baixar Guidelines Atualizados

Informe:

```
📥 Baixando guidelines atualizados do repositório...
```

**Baixar do GitHub:**

```bash
REPO_URL="https://raw.githubusercontent.com/danie1net0/laravel-claude-commands/main/guidelines"

echo "📥 Baixando guidelines..."

for file in architecture code-style commits filament models tests validation; do
    echo -n "  📄 $file.md: "
    if curl -fsSL "$REPO_URL/$file.md" -o ".ai/guidelines/$file.md"; then
        echo "✅"
    else
        echo "❌ (falhou)"
    fi
done

echo ""
echo "✅ Guidelines atualizados!"
```

### 5. Atualizar Laravel Boost (se instalado)

Verificar se Laravel Boost está instalado:

```bash
if php artisan list 2>/dev/null | grep -q boost; then
    echo "🔄 Atualizando Laravel Boost..."
    php artisan boost:update
    echo "✅ Laravel Boost atualizado!"
fi
```

### 6. Mostrar Diferenças (Opcional)

Se houver backup, pergunte se quer ver resumo das mudanças:

```
🔍 Deseja ver um resumo das mudanças?
```

**Se sim:**

```bash
if [ -d .ai/guidelines.backup ]; then
    echo ""
    echo "📊 Resumo das mudanças:"
    for file in .ai/guidelines/*.md; do
        filename=$(basename "$file")
        if [ -f ".ai/guidelines.backup/$filename" ]; then
            old_lines=$(wc -l < ".ai/guidelines.backup/$filename" 2>/dev/null || echo "0")
            new_lines=$(wc -l < "$file" 2>/dev/null || echo "0")
            diff=$((new_lines - old_lines))

            if [ $diff -gt 0 ]; then
                echo "  ✨ $filename: +$diff linhas"
            elif [ $diff -lt 0 ]; then
                echo "  🗜️  $filename: $diff linhas"
            else
                echo "  ✓ $filename: sem mudanças"
            fi
        else
            echo "  🆕 $filename: novo arquivo"
        fi
    done
fi
```

### 7. Resumo Final

```
✅ Guidelines Atualizados com Sucesso!

📁 Localização: .ai/guidelines/

📋 Guidelines disponíveis:
- architecture.md - Padrões de arquitetura (Actions, Services, DTOs)
- code-style.md - Convenções de código e estilo
- commits.md - Padrões para commits (Conventional Commits)
- filament.md - Padrões do Filament PHP
- models.md - Eloquent Models e relacionamentos
- tests.md - Estratégias de testes (Pest)
- validation.md - Validação e Form Requests

💡 Dicas:
- Se você fez backup, compare com .ai/guidelines.backup
- Para restaurar backup: cp -r .ai/guidelines.backup .ai/guidelines
- Customize os guidelines conforme necessário para seu projeto
- Execute novamente este comando para obter atualizações futuras
```

## Notas

- Guidelines são baixados do repositório oficial no GitHub
- O backup preserva suas customizações
- Laravel Boost é atualizado automaticamente se instalado
- Execute sempre que houver atualizações nos guidelines
