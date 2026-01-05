# Validação - Essentials

## Princípio Fundamental

**SEMPRE use Form Requests**, nunca validação inline no controller.

## Criando Form Requests

```bash
php artisan make:request Store{Resource}Request
```

## Estrutura Básica

```php
class Store{Resource}Request extends FormRequest
{
    // Omitir se não valida autorização
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('resource'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:resources'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.unique' => 'Este e-mail já está em uso.',
            'password.min' => 'A senha deve ter no mínimo :min caracteres.',
        ];
    }
}

// Controller:
public function store(Store{Resource}Request $request)
{
    $resource = {Resource}::create($request->validated());
    return redirect()->route('resources.show', $resource);
}
```

## Rules Comuns

```php
'name' => ['required', 'string', 'max:255']
'email' => ['required', 'email', 'unique:resources']
'age' => ['integer', 'min:18', 'max:100']
'price' => ['numeric', 'decimal:2']
'active' => ['boolean']
'data' => ['array']
'file' => ['file', 'max:2048']
'image' => ['image', 'mimes:jpg,png']
'date' => ['date', 'after:today']

// Database
'email' => ['unique:resources,email,' . $resourceId]  // Ignorar próprio registro
'related_id' => ['exists:related,id']

// Condicionais
'email' => ['required_if:type,email']
'phone' => ['required_without:email']
```

## Validação de Arrays

```php
public function rules(): array
{
    return [
        'items' => ['required', 'array', 'min:1'],
        'items.*.id' => ['required', 'exists:items,id'],
        'items.*.quantity' => ['required', 'integer', 'min:1'],
    ];
}
```

## Boas Práticas

```php
✅ Store{Resource}Request extends FormRequest
✅ ['required', 'string', 'max:255']  // Array syntax
✅ Mensagens em português
✅ Omitir authorize() se não valida

❌ $request->validate() no controller
❌ 'required|string|max:255'  // String syntax
❌ authorize() { return true; }  // Desnecessário
```
