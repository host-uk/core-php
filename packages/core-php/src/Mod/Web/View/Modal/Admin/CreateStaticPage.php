<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\StaticPageSanitiser;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateStaticPage extends Component
{
    // Form fields
    public string $url = '';

    public string $title = '';

    public string $htmlContent = '';

    public string $cssContent = '';

    public string $jsContent = '';

    // Advanced options
    public bool $showAdvanced = false;

    public bool $isEnabled = true;

    public ?string $startDate = null;

    public ?string $endDate = null;

    // Editor options
    public bool $showCssEditor = false;

    public bool $showJsEditor = false;

    // Entitlement state
    public bool $canCreate = true;

    public ?string $entitlementError = null;

    public ?int $usedCount = null;

    public ?int $limitCount = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->checkEntitlement();
    }

    /**
     * Check if the user can create more static pages.
     */
    protected function checkEntitlement(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return;
        }

        $entitlements = app(EntitlementService::class);
        $result = $entitlements->can($workspace, 'bio.static');

        $this->canCreate = $result->isAllowed();
        $this->usedCount = $result->used;
        $this->limitCount = $result->limit;

        if ($result->isDenied()) {
            $this->entitlementError = $result->getMessage() ?? 'You have reached your static page limit.';
        }
    }

    /**
     * Toggle CSS editor visibility.
     */
    public function toggleCssEditor(): void
    {
        $this->showCssEditor = ! $this->showCssEditor;
    }

    /**
     * Toggle JS editor visibility.
     */
    public function toggleJsEditor(): void
    {
        $this->showJsEditor = ! $this->showJsEditor;
    }

    /**
     * Toggle advanced options visibility.
     */
    public function toggleAdvanced(): void
    {
        $this->showAdvanced = ! $this->showAdvanced;
    }

    /**
     * Get the validation rules.
     */
    protected function rules(): array
    {
        return [
            'url' => [
                'required',
                'string',
                'min:3',
                'max:256',
                'regex:/^[a-z0-9\-_]+$/i',
            ],
            'title' => [
                'required',
                'string',
                'min:3',
                'max:128',
            ],
            'htmlContent' => [
                'required',
                'string',
                'max:500000', // 500KB limit
            ],
            'cssContent' => [
                'nullable',
                'string',
                'max:100000', // 100KB limit
            ],
            'jsContent' => [
                'nullable',
                'string',
                'max:100000', // 100KB limit
            ],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
        ];
    }

    /**
     * Custom validation messages.
     */
    protected function messages(): array
    {
        return [
            'url.regex' => 'The URL can only contain letters, numbers, hyphens, and underscores.',
            'url.min' => 'The URL must be at least 3 characters.',
            'title.required' => 'Please enter a page title.',
            'htmlContent.required' => 'Please enter some HTML content for your static page.',
            'htmlContent.max' => 'HTML content must not exceed 500KB.',
            'cssContent.max' => 'CSS content must not exceed 100KB.',
            'jsContent.max' => 'JavaScript content must not exceed 100KB.',
            'endDate.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Create the static page.
     */
    public function create(): void
    {
        // Re-check entitlement (could have changed since page load)
        $this->checkEntitlement();

        if (! $this->canCreate) {
            $this->dispatch('notify', message: $this->entitlementError ?? 'You have reached your limit.', type: 'error');

            return;
        }

        $this->validate();

        // Check URL uniqueness
        $exists = Page::where('url', Str::lower($this->url))
            ->whereNull('domain_id')
            ->exists();

        if ($exists) {
            $this->addError('url', 'This URL is already taken. Try a different one.');

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            $this->dispatch('notify', message: 'Authentication error.', type: 'error');

            return;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            $this->dispatch('notify', message: 'No workspace found.', type: 'error');

            return;
        }

        // Sanitise content
        $sanitiser = app(StaticPageSanitiser::class);
        $sanitised = $sanitiser->sanitiseStaticPage(
            $this->htmlContent,
            $this->cssContent,
            $this->jsContent
        );

        // Create the biolink
        $biolink = Page::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'type' => 'static',
            'url' => Str::lower($this->url),
            'is_enabled' => $this->isEnabled,
            'start_date' => $this->startDate ? now()->parse($this->startDate) : null,
            'end_date' => $this->endDate ? now()->parse($this->endDate) : null,
            'settings' => [
                'title' => $this->title,
                'static_html' => $sanitised['html'],
                'static_css' => $sanitised['css'],
                'static_js' => $sanitised['js'],
            ],
        ]);

        // Record usage
        $entitlements = app(EntitlementService::class);
        $entitlements->recordUsage(
            $workspace,
            'bio.static',
            1,
            $user,
            ['biolink_id' => $biolink->id, 'type' => 'static']
        );

        $this->dispatch('notify', message: 'Static page created successfully', type: 'success');

        // Redirect to the biolinks index
        $this->redirect(route('hub.bio.index'), navigate: true);
    }

    /**
     * Get the full URL preview.
     */
    #[Computed]
    public function fullUrlPreview(): string
    {
        $domain = config('bio.default_domain', 'https://bio.host.uk.com');

        return rtrim($domain, '/').'CreateStaticPage.php/'.($this->url ?: 'your-slug');
    }

    public function render()
    {
        return view('webpage::admin.create-static-page')
            ->layout('hub::admin.layouts.app', ['title' => 'Create Static Page']);
    }
}
