# Admin Package

The Admin package provides a complete admin panel with Livewire modals, form components, global search, and an extensible menu system.

## Installation

```bash
composer require host-uk/core-admin
```

## Features

### Admin Menu System

Extensible navigation menu with automatic discovery:

```php
<?php

namespace Mod\Blog;

use Core\Front\Admin\Contracts\AdminMenuProvider;
use Core\Front\Admin\Support\MenuItemBuilder;

class BlogMenuProvider implements AdminMenuProvider
{
    public function register(): array
    {
        return [
            MenuItemBuilder::make('Blog')
                ->icon('newspaper')
                ->priority(30)
                ->children([
                    MenuItemBuilder::make('Posts')
                        ->route('admin.blog.posts.index')
                        ->icon('document-text'),

                    MenuItemBuilder::make('Categories')
                        ->route('admin.blog.categories.index')
                        ->icon('folder'),
                ])
                ->build(),
        ];
    }
}
```

Register in your module's Boot.php:

```php
public function onAdmin(AdminPanelBooting $event): void
{
    $event->menu(new BlogMenuProvider());
}
```

[Learn more about Admin Menus →](/patterns-guide/admin-menus)

### Livewire Modals

Full-page modal system for admin interfaces:

```php
<?php

namespace Mod\Blog\View\Modal\Admin;

use Livewire\Component;

class PostEditor extends Component
{
    public ?Post $post = null;

    public $title;
    public $content;

    public function mount(?Post $post = null): void
    {
        $this->post = $post;
        $this->title = $post?->title;
        $this->content = $post?->content;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);

        if ($this->post) {
            $this->post->update($validated);
        } else {
            Post::create($validated);
        }

        $this->dispatch('post-saved');
        $this->closeModal();
    }

    public function render()
    {
        return view('blog::admin.post-editor');
    }
}
```

Open modals from any admin page:

```blade
<x-button wire:click="$dispatch('openModal', {component: 'blog.post-editor'})">
    New Post
</x-button>

<x-button wire:click="$dispatch('openModal', {component: 'blog.post-editor', arguments: {post: {{ $post->id }}}})">
    Edit Post
</x-button>
```

### Form Components

Pre-built form components with validation:

```blade
<x-admin::form action="{{ route('admin.posts.store') }}">
    <x-admin::form-group
        label="Title"
        name="title"
        required
    >
        <x-admin::input
            name="title"
            :value="old('title', $post->title)"
            placeholder="Enter post title"
        />
    </x-admin::form-group>

    <x-admin::form-group
        label="Content"
        name="content"
        required
    >
        <x-admin::textarea
            name="content"
            :value="old('content', $post->content)"
            rows="10"
        />
    </x-admin::form-group>

    <x-admin::form-group
        label="Category"
        name="category_id"
    >
        <x-admin::select
            name="category_id"
            :options="$categories"
            :selected="old('category_id', $post->category_id)"
        />
    </x-admin::form-group>

    <x-admin::form-group
        label="Published"
        name="is_published"
    >
        <x-admin::toggle
            name="is_published"
            :checked="old('is_published', $post->is_published)"
        />
    </x-admin::form-group>

    <div class="flex justify-end space-x-2">
        <x-admin::button type="submit" variant="primary">
            Save Post
        </x-admin::button>

        <x-admin::button type="button" variant="secondary" onclick="history.back()">
            Cancel
        </x-admin::button>
    </div>
</x-admin::form>
```

### Global Search

Search across all admin content:

```php
<?php

namespace Mod\Blog\Search;

use Core\Admin\Search\Contracts\SearchProvider;
use Core\Admin\Search\SearchResult;
use Mod\Blog\Models\Post;

class PostSearchProvider implements SearchProvider
{
    public function search(string $query): array
    {
        return Post::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(fn ($post) => new SearchResult(
                title: $post->title,
                description: $post->excerpt,
                url: route('admin.blog.posts.edit', $post),
                icon: 'document-text',
                type: 'Post',
            ))
            ->toArray();
    }

    public function getSearchableTypes(): array
    {
        return ['posts'];
    }
}
```

Register provider:

```php
// config/core-admin.php
'search' => [
    'providers' => [
        \Mod\Blog\Search\PostSearchProvider::class,
    ],
],
```

### Dashboard Widgets

Add widgets to the admin dashboard:

```php
<?php

namespace Mod\Blog\Widgets;

use Livewire\Component;

class PostStatsWidget extends Component
{
    public function render()
    {
        return view('blog::admin.widgets.post-stats', [
            'totalPosts' => Post::count(),
            'publishedPosts' => Post::published()->count(),
            'draftPosts' => Post::draft()->count(),
        ]);
    }
}
```

Register widget:

```php
public function onAdmin(AdminPanelBooting $event): void
{
    $event->widget(new PostStatsWidget(), priority: 10);
}
```

### Settings Pages

Add custom settings pages:

```php
<?php

namespace Mod\Blog\Settings;

use Livewire\Component;

class BlogSettings extends Component
{
    public $postsPerPage;
    public $enableComments;

    public function mount(): void
    {
        $this->postsPerPage = config('blog.posts_per_page', 10);
        $this->enableComments = config('blog.comments_enabled', true);
    }

    public function save(): void
    {
        ConfigService::set('blog.posts_per_page', $this->postsPerPage);
        ConfigService::set('blog.comments_enabled', $this->enableComments);

        $this->dispatch('settings-saved');
    }

    public function render()
    {
        return view('blog::admin.settings');
    }
}
```

Register settings page:

```php
public function onAdmin(AdminPanelBooting $event): void
{
    $event->settings('blog', BlogSettings::class);
}
```

## Components Reference

### Input

```blade
<x-admin::input
    name="title"
    type="text"
    :value="$value"
    placeholder="Enter title"
    required
    disabled
    readonly
/>
```

### Textarea

```blade
<x-admin::textarea
    name="content"
    :value="$value"
    rows="10"
    placeholder="Enter content"
/>
```

### Select

```blade
<x-admin::select
    name="category"
    :options="[1 => 'Tech', 2 => 'Design']"
    :selected="$selectedId"
    placeholder="Select category"
/>
```

### Checkbox

```blade
<x-admin::checkbox
    name="terms"
    :checked="$isChecked"
    label="I agree to terms"
/>
```

### Toggle

```blade
<x-admin::toggle
    name="is_active"
    :checked="$isActive"
    label="Active"
/>
```

### Button

```blade
<x-admin::button
    type="submit"
    variant="primary|secondary|danger"
    size="sm|md|lg"
    icon="save"
    disabled
    loading
>
    Save Changes
</x-admin::button>
```

### Form Group

```blade
<x-admin::form-group
    label="Email"
    name="email"
    help="We'll never share your email"
    error="$errors->first('email')"
    required
>
    <x-admin::input name="email" type="email" />
</x-admin::form-group>
```

## Layouts

### Admin App Layout

```blade
<x-admin::layout>
    <x-slot:header>
        <h1>Page Title</h1>
    </x-slot>

    {{-- Main content --}}
    <div class="container mx-auto">
        Content here
    </div>
</x-admin::layout>
```

### HLCRF Layout

```blade
<x-hlcrf::layout>
    <x-hlcrf::header>
        Page Header with Actions
    </x-hlcrf::header>

    <x-hlcrf::left>
        Sidebar Navigation
    </x-hlcrf::left>

    <x-hlcrf::content>
        Main Content Area
    </x-hlcrf::content>

    <x-hlcrf::right>
        Contextual Help & Widgets
    </x-hlcrf::right>
</x-hlcrf::layout>
```

[Learn more about HLCRF →](/patterns-guide/hlcrf)

## Configuration

```php
// config/core-admin.php
return [
    'menu' => [
        'cache_enabled' => true,
        'cache_ttl' => 3600,
        'show_icons' => true,
    ],

    'search' => [
        'enabled' => true,
        'providers' => [
            // Register search providers
        ],
        'max_results' => 10,
    ],

    'livewire' => [
        'modal_max_width' => '7xl',
        'modal_close_on_escape' => true,
    ],

    'form' => [
        'validation_real_time' => true,
        'show_required_indicator' => true,
    ],
];
```

## Styling

Admin package uses Tailwind CSS. Customize theme:

```js
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        admin: {
          primary: '#3b82f6',
          secondary: '#64748b',
          success: '#22c55e',
          danger: '#ef4444',
        },
      },
    },
  },
};
```

## JavaScript

Admin package includes Alpine.js for interactivity:

```blade
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>

    <div x-show="open">
        Content
    </div>
</div>
```

## Testing

### Feature Tests

```php
public function test_can_access_admin_dashboard(): void
{
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)
        ->get('/admin');

    $response->assertStatus(200);
}

public function test_admin_menu_displays_blog_items(): void
{
    $user = User::factory()->admin()->create();

    $response = $this->actingAs($user)
        ->get('/admin');

    $response->assertSee('Blog');
    $response->assertSee('Posts');
    $response->assertSee('Categories');
}
```

### Livewire Component Tests

```php
public function test_can_create_post_via_modal(): void
{
    Livewire::actingAs($admin)
        ->test(PostEditor::class)
        ->set('title', 'Test Post')
        ->set('content', 'Test content')
        ->call('save')
        ->assertDispatched('post-saved');

    $this->assertDatabaseHas('posts', [
        'title' => 'Test Post',
    ]);
}
```

## Best Practices

### 1. Use Livewire Modals for CRUD

```php
// ✅ Good - modal UX
<x-button wire:click="$dispatch('openModal', {component: 'post-editor'})">
    New Post
</x-button>

// ❌ Bad - full page redirect
<a href="{{ route('admin.posts.create') }}">New Post</a>
```

### 2. Organize Menu Items by Domain

```php
MenuItemBuilder::make('Content')
    ->children([
        MenuItemBuilder::make('Posts')->route('admin.posts.index'),
        MenuItemBuilder::make('Pages')->route('admin.pages.index'),
    ]);
```

### 3. Use Form Components

```blade
{{-- ✅ Good - consistent styling --}}
<x-admin::form-group label="Title" name="title">
    <x-admin::input name="title" />
</x-admin::form-group>

{{-- ❌ Bad - custom HTML --}}
<div class="mb-4">
    <label>Title</label>
    <input type="text" name="title">
</div>
```

## Changelog

See [CHANGELOG.md](https://github.com/host-uk/core-php/blob/main/packages/core-admin/changelog/2026/jan/features.md)

## License

EUPL-1.2

## Learn More

- [HLCRF Layout System →](/patterns-guide/hlcrf)
- [Livewire Documentation](https://livewire.laravel.com)
- [Alpine.js Documentation](https://alpinejs.dev)
