# Commits - Essentials

## Formato

```
<tipo>(<escopo>): <mensagem>
```

## Tipos

```
feat      # Nova funcionalidade
fix       # Correção de bug
refactor  # Refatoração de código
style     # Formatação (sem mudança de código)
docs      # Documentação
test      # Testes
chore     # Configurações/build
perf      # Performance
```

## Exemplos

```
feat(auth): adiciona login com Google
fix(api): corrige validação de email
refactor(user): simplifica lógica de autenticação
style(components): formata código com Pint
docs(readme): atualiza instruções de instalação
test(auth): adiciona testes para login
chore(deps): atualiza dependências
perf(queries): otimiza consulta de usuários
```

## Regras

- Mensagem em português
- Verbo no infinitivo ou presente
- Máximo 50 caracteres
- Sem ponto final
- Escopo opcional
- ❌ NÃO incluir `Co-Authored-By: Claude`
