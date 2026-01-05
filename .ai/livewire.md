# Livewire - Essentials

## Princípios

- **Actions para negócio** - lógica complexa em Actions, não no componente
- **Componentes focados** - uma responsabilidade por componente
- **Estado no servidor** - UI reflete o estado do componente
- **Validação com atributos** - usar `#[Validate]` para regras simples
- **Flux UI** - usar componentes Flux quando disponíveis

## Ordem dos Métodos

```
1. Traits
2. Propriedades (com validação primeiro, depois estado)
3. Lifecycle hooks (boot, mount, hydrate, dehydrate)
4. Updated hooks (updatedProperty)
5. Actions (métodos públicos de ação)
6. Métodos auxiliares privados
7. Computed properties (#[Computed])
8. render()
```

## Estrutura de Componentes

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\{CreateResourceAction, UpdateResourceAction};
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Attributes\{Computed, Validate};
use Livewire\Component;
use RuntimeException;

class ResourceEditor extends Component
{
    // 1. Traits
    use WithFileUploads;

    // 2. Propriedades com validação
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|file|mimes:jpg,png|max:10240')]
    public $image;

    // 3. Propriedades de estado
    public bool $isProcessing = false;
    public ?string $errorMessage = null;

    // 4. Lifecycle hooks
    public function mount(Resource $resource): void
    {
        $this->name = $resource->name;
    }

    // 5. Updated hooks
    public function updatedImage(): void
    {
        $this->validate(['image' => 'required|file|mimes:jpg,png|max:10240']);
    }

    // 6. Actions (métodos públicos)
    public function save(CreateResourceAction $action): void
    {
        $this->validate();

        set_time_limit(0);
        $this->isProcessing = true;
        $this->errorMessage = null;

        try {
            $action->execute($this->name, $this->image);
            Flux::modal('success')->show();
        } catch (RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        } finally {
            $this->isProcessing = false;
        }
    }

    // 7. Métodos auxiliares privados
    private function resetForm(): void
    {
        $this->reset(['name', 'image', 'errorMessage']);
    }

    // 8. Computed properties
    #[Computed]
    public function availableOptions(): array
    {
        return ResourceAction::availableOptions();
    }

    // 9. Render
    public function render(): View
    {
        return view('livewire.resource-editor')
            ->layout('layouts.app', ['title' => 'Editor']);
    }
}
```

## Validação

```php
// Atributo para regras simples
#[Validate('required|string|max:255')]
public string $name = '';

// Validação em hook para uploads
public function updatedFile(): void
{
    $this->validate(['file' => 'required|file|mimes:pdf|max:10240']);
}

// Validação manual no método
public function save(): void
{
    $this->validate();
    // ou
    $this->validate([
        'name' => 'required|min:3',
        'email' => 'required|email',
    ]);
}
```

## Padrão para Operações Longas

```php
public function processVideo(ProcessVideoAction $action): void
{
    if (! $this->videoPath) {
        $this->errorMessage = 'Nenhum vídeo carregado.';
        return;
    }

    set_time_limit(0);
    $this->isProcessing = true;
    $this->errorMessage = null;

    try {
        $result = $action->execute($this->videoPath);
        $this->outputPath = Storage::disk('public')->url($result);
        Flux::modal('result')->show();
    } catch (RuntimeException $e) {
        $this->errorMessage = $e->getMessage();
    } finally {
        $this->isProcessing = false;
    }
}
```

## Computed Properties

```php
use Livewire\Attributes\Computed;

// Livewire 3 - usar atributo #[Computed]
#[Computed]
public function formattedDuration(): string
{
    return gmdate('H:i:s', (int) $this->duration);
}

#[Computed]
public function availableFormats(): array
{
    return ConvertVideoAction::availableFormats();
}

// Com cache (persiste entre requests)
#[Computed(persist: true)]
public function expensiveCalculation(): int
{
    return SomeAction::calculate();
}

// Uso na view
{{ $this->formattedDuration }}

@foreach ($this->availableFormats as $value => $label)
    <option value="{{ $value }}">{{ $label }}</option>
@endforeach
```

## Flux UI Integration

```php
// Mostrar modal após operação
Flux::modal('result')->show();

// Na view
<flux:modal name="result">
    <flux:heading>Resultado</flux:heading>
    <!-- conteúdo -->
</flux:modal>

// Toast notification (via Alpine)
x-on:click="$flux.toast({ heading: 'Sucesso!', text: 'Operação concluída' })"
```

## File Uploads

```php
use Livewire\WithFileUploads;

class FileUploader extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:mp4,webm|max:204800')]
    public $video;

    public ?string $videoPath = null;

    public function updatedVideo(): void
    {
        $this->validate(['video' => 'required|file|mimes:mp4,webm|max:204800']);

        $path = $this->video->store('videos', 'public');
        $this->videoPath = $path;
    }
}
```

## Views (Blade)

```blade
{{-- Loading state --}}
<flux:button wire:click="process" wire:loading.attr="disabled">
    <span wire:loading.remove wire:target="process">Processar</span>
    <span wire:loading wire:target="process">Processando...</span>
</flux:button>

{{-- Error display --}}
@if ($errorMessage)
    <flux:callout variant="danger">
        <flux:callout.heading>Erro</flux:callout.heading>
        <flux:callout.text>{{ $errorMessage }}</flux:callout.text>
    </flux:callout>
@endif

{{-- Wire model --}}
<flux:input wire:model="name" label="Nome" />
<flux:input wire:model.live="search" label="Buscar" />
{{-- Reativo --}}

{{-- Select com computed property --}}
<flux:select wire:model="format">
    @foreach ($this->availableFormats as $value => $label)
        <flux:select.option value="{{ $value }}">
            {{ $label }}
        </flux:select.option>
    @endforeach
</flux:select>
```

## Alpine.js Integration

```blade
<div
    x-data="{
        isPlaying: false,
        duration: @entangle("duration"),

        togglePlay() {
            this.isPlaying = ! this.isPlaying
        },

        updateDuration(value) {
            $wire.set('duration', value)
        },
    }"
>
    <button @click="togglePlay()">
        <span x-show="!isPlaying">Play</span>
        <span x-show="isPlaying">Pause</span>
    </button>
</div>
```

## Boas Práticas

```php
✅ Usar Actions para lógica de negócio
✅ set_time_limit(0) para operações longas
✅ Try/catch com errorMessage para feedback
✅ isProcessing para estados de loading
✅ #[Computed] para dados derivados
✅ #[Validate] para validação de propriedades
✅ Flux UI para componentes de interface
✅ wire:loading para feedback visual
✅ Seguir ordem de métodos definida

❌ Lógica de negócio no componente
❌ Queries complexas no componente
❌ Esquecer set_time_limit em operações longas
❌ Usar $this->emit() (v3 usa $this->dispatch())
❌ Usar getPropertyProperty() (v3 usa #[Computed])
❌ Manipular DOM diretamente (usar Alpine)
❌ Esquecer wire:key em loops
```

## Config (config/livewire.php)

```php
'temporary_file_upload' => [
    'disk' => 'public',
    'rules' => ['file', 'mimes:mp4,webm,mov,png,jpg,jpeg', 'max:204800'],
    'directory' => 'livewire-tmp',
],
```
