# Core-Admin TODO

## Form Authorization Components

**Priority:** Medium
**Context:** Authorization checks scattered throughout views with `@can` directives.

### Solution

Build authorization into form components themselves.

### Implementation

```blade
{{-- resources/views/components/forms/input.blade.php --}}
@props([
    'id',
    'label' => null,
    'canGate' => null,
    'canResource' => null,
])

@php
    $disabled = $attributes->get('disabled', false);
    if ($canGate && $canResource && !$disabled) {
        $disabled = !auth()->user()?->can($canGate, $canResource);
    }
@endphp

<input {{ $attributes->merge(['disabled' => $disabled]) }} />
```

### Usage

```blade
<x-forms.input canGate="update" :canResource="$biolink" id="name" label="Name" />
<x-forms.button canGate="update" :canResource="$biolink">Save</x-forms.button>
```

### Components to Create

- `input.blade.php`
- `textarea.blade.php`
- `select.blade.php`
- `checkbox.blade.php`
- `button.blade.php`
- `toggle.blade.php`

---

## Global Search (⌘K)

**Priority:** Medium
**Context:** No unified search across resources.

### Implementation

```php
class GlobalSearch extends Component
{
    public bool $open = false;
    public string $query = '';
    public array $results = [];

    public function updatedQuery()
    {
        if (strlen($this->query) < 2) return;

        $this->results = $this->searchProviders();
    }
}
```

### Features

- ⌘K keyboard shortcut
- Arrow key navigation
- Enter to select
- Module-provided search providers
- Recent searches

### Requirements

- `SearchProvider` interface for modules to implement
- Auto-discover providers from registered modules
- Fuzzy matching support

---

## Real-time WebSocket (Soketi)

**Priority:** Low
**Context:** No real-time updates. Users must refresh.

### Implementation

Self-hosted Soketi + Laravel Echo.

```yaml
# docker-compose.yml
soketi:
  image: 'quay.io/soketi/soketi:latest'
  ports:
    - '6001:6001'
```

```php
// Broadcasting events
broadcast(new ResourceUpdated($resource));

// Livewire listening
protected function getListeners()
{
    return [
        "echo-private:workspace.{$this->workspace->id},resource.updated" => 'refresh',
    ];
}
```

### Notes

- DO NOT route through Bunny CDN (charges per connection)
- Use private channels for workspace-scoped events

---

## Enhanced Form Components

**Priority:** Medium
**Context:** Extend Flux Pro components with additional features.

### Features to Add

- Dark mode consistency
- Automatic error display
- Helper text support
- Disabled states from authorization
- `instantSave` for real-time persistence

### Components

```blade
<x-forms.input id="name" label="Name" helper="Enter a display name" />
<x-forms.toggle id="is_public" label="Public" instantSave />
```
