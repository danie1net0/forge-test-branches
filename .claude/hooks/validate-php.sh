#!/bin/bash

# Hook: Validação de arquivos PHP após edição
# Executa Laravel Pint e PHPStan nos arquivos alterados

set -e

FILE="$1"

# Só executa para arquivos PHP
if [[ ! "$FILE" =~ \.php$ ]]; then
    exit 0
fi

echo "🔍 Validando $FILE..."

# Verifica se o arquivo existe
if [[ ! -f "$FILE" ]]; then
    echo "⚠️ Arquivo não encontrado: $FILE"
    exit 0
fi

# Laravel Pint (formatação)
if command -v ./vendor/bin/pint &> /dev/null; then
    echo "  → Executando Pint..."
    ./vendor/bin/pint "$FILE" --quiet
fi

# PHPStan (análise estática)
if command -v ./vendor/bin/phpstan &> /dev/null; then
    echo "  → Executando PHPStan..."
    ./vendor/bin/phpstan analyse "$FILE" --no-progress --error-format=table 2>/dev/null || true
fi

# Verifica declare(strict_types=1)
if ! grep -q "declare(strict_types=1);" "$FILE"; then
    echo "⚠️ Falta declare(strict_types=1); em $FILE"
fi

echo "✅ Validação concluída"
