# Filament - Essentials

## Princípios

- **Métodos privados** - organizar schema, columns, actions
- **Evitar aninhamento** - criar variáveis intermediárias
- **Actions para negócio** - TODA lógica de negócio em Actions (app/Actions), não em Resources/Pages
- **Não usar model diretamente** - sempre via Actions para operações de negócio
- **Arquivos separados** - Forms, Tables e Infolists em arquivos separados (Filament 4)

## Estrutura de Resources

**⚠️ IMPORTANTE:** No Filament 4, Forms, Tables e Infolists devem estar em **arquivos separados**.

### Estrutura de Diretórios

```
app/Filament/Resources/
├── {Resource}Resource.php
├── {Resource}Resource/
│   ├── Schemas/
│   │   ├── {Resource}Form.php
│   │   └── {Resource}Infolist.php
│   ├── Tables/
│   │   └── {PluralResource}Table.php
│   └── Pages/
│       ├── List{PluralResource}.php
│       ├── Create{Resource}.php
│       ├── Edit{Resource}.php
│       └── View{Resource}.php
```

### Resource Principal

```php
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BackedEnum;

class {Resource}Resource extends Resource
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-icon-name';

    public static function form(Schema $schema): Schema
    {
        return {Resource}Form::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return {Resource}Infolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return {PluralResource}Table::configure($table);
    }
}
```

### Form Schema (Schemas/{Resource}Form.php)

```php
use Filament\Forms\Components\{TextInput, Select};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class {Resource}Form
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(self::formSchema());
    }

    private static function formSchema(): array
    {
        $fields[] = TextInput::make('name')
            ->label('Nome')
            ->required();

        $fields[] = Select::make('status')
            ->options(['active' => 'Ativo'])
            ->required();

        return $fields;
    }
}
```

### Table (Tables/{PluralResource}Table.php)

```php
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Tables\Filters\{Filter, SelectFilter};
use Filament\Tables\Table;

final class {PluralResource}Table
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::tableColumns())
            ->filters(self::tableFilters())
            ->actions(self::tableActions());
    }

    private static function tableColumns(): array
    {
        $columns[] = TextColumn::make('name')
            ->searchable()
            ->sortable();

        return $columns;
    }
}
```

## Evitar Aninhamento

```php
// ✅ Correto:
private static function formSchema(): array
{
    $personalSchema[] = TextInput::make('name');
    $personalSchema[] = TextInput::make('email');

    $addressSchema[] = TextInput::make('street');
    $addressSchema[] = TextInput::make('city');

    $fields[] = Section::make('Endereço')->schema($addressSchema);

    return $fields;
}

// ❌ Errado:
return [
    Section::make('Endereço')->schema([
        TextInput::make('name'),
        TextInput::make('street'),
    ]),
];
```

## Campos Comuns

```php
TextInput::make('name')
    ->label('Nome')
    ->required()
    ->maxLength(255);

Select::make('status')
    ->options(['active' => 'Ativo'])
    ->required();

Textarea::make('bio')
    ->rows(3)
    ->maxLength(500);

DatePicker::make('birth_date')
    ->native(false)
    ->maxDate(now());

FileUpload::make('avatar')
    ->image()
    ->maxSize(1024);
```

## Usando Actions

**⚠️ IMPORTANTE:** No Filament 4, Actions estão em `Filament\Actions\`, não em `Filament\Tables\Actions\`.

```php
use Filament\Actions\{Action, ViewAction, EditAction, DeleteAction};

// Action customizada na tabela
Action::make('approve')
    ->label('Aprovar')
    ->action(function (Model $record, Approve{Resource}Action $action) {
        $action->execute($data);
    })
    ->requiresConfirmation();

// Actions padrão
ViewAction::make();
EditAction::make();
DeleteAction::make();
```

## Filament 4 - Mudanças Importantes

### Arquivos Separados

- **Forms, Tables e Infolists** devem estar em arquivos separados
- Forms em `Schemas/{Resource}Form.php`
- Tables em `Tables/{PluralResource}Table.php`
- Infolists em `Schemas/{Resource}Infolist.php`
- Resource principal apenas referencia essas classes

### Schema vs Form

- **Filament 4 usa `Schema`** em vez de `Form` diretamente
- Método `form()` recebe e retorna `Schema`, não `Form`
- Use `$schema->components([...])` para adicionar componentes
- **Componentes de campo** continuam em `Filament\Forms\Components\` (TextInput, Select, etc.)
- **Componentes de layout** estão em `Filament\Schemas\Components\` (Section, Group, etc.)

### Actions

- **Actions estão em `Filament\Actions\`**, não em `Filament\Tables\Actions\`
- Use `Filament\Actions\Action` para actions customizadas
- Use `Filament\Actions\{ViewAction, EditAction, DeleteAction}` para actions padrão

### Navigation Icon

- Tipo deve ser `string | BackedEnum | null`
- Use `protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-icon-name';`

### Infolist

- Método `infolist()` também recebe e retorna `Schema`
- Use `$schema->components([...])` para adicionar entries
- Componentes do infolist continuam em `Filament\Infolists\Components\`
- **Use imports diretos:** `use Filament\Infolists\Components\{TextEntry, IconEntry, ViewEntry};`

### Imports

- **Sempre use imports diretos**, nunca namespace aliases
- ✅ `use Filament\Tables\Columns\{TextColumn, IconColumn};`
- ✅ `use Filament\Tables\Filters\{Filter, SelectFilter};`
- ✅ `use Filament\Infolists\Components\{TextEntry, IconEntry};`
- ❌ `use Filament\Tables;` e depois `Tables\Columns\TextColumn`

## Boas Práticas

```php
✅ Métodos privados para schema/columns
✅ Variáveis intermediárias
✅ Labels em português
✅ Lógica de negócio em Actions (app/Actions)
✅ DTOs para passar dados entre camadas
✅ Usar Schema no Filament 4
✅ Actions de Filament\Actions\
✅ Arquivos separados para Forms, Tables e Infolists

❌ Aninhamento profundo
❌ Lógica de negócio no Resource/Page
❌ Model::create() direto em Resources
❌ Regras de negócio inline em closures
❌ Usar Form diretamente (use Schema)
❌ Actions de Filament\Tables\Actions\
❌ Tudo no mesmo arquivo Resource
```
