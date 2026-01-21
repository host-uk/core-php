<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateShortLink extends Component
{
    // Form fields
    public string $url = '';

    public string $destinationUrl = '';

    public bool $autoGenerateSlug = true;

    // Advanced options
    public bool $showAdvanced = false;

    public bool $isEnabled = true;

    public ?string $startDate = null;

    public ?string $endDate = null;

    // Entitlement state
    public bool $canCreate = true;

    public ?string $entitlementError = null;

    public ?int $usedCount = null;

    public ?int $limitCount = null;

    /**
     * Mount the component and generate a random slug.
     */
    public function mount(): void
    {
        $this->url = $this->generateRandomSlug();
        $this->checkEntitlement();
    }

    /**
     * Check if the user can create more short links.
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
        $result = $entitlements->can($workspace, 'bio.shortlinks');

        $this->canCreate = $result->isAllowed();
        $this->usedCount = $result->used;
        $this->limitCount = $result->limit;

        if ($result->isDenied()) {
            $this->entitlementError = $result->getMessage() ?? 'You have reached your short link limit.';
        }
    }

    /**
     * Generate a random URL slug.
     */
    protected function generateRandomSlug(int $length = 6): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $slug = '';

        for ($i = 0; $i < $length; $i++) {
            $slug .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Ensure uniqueness
        while (Page::where('url', $slug)->whereNull('domain_id')->exists()) {
            $slug = $this->generateRandomSlug($length);
        }

        return $slug;
    }

    /**
     * Regenerate the URL slug.
     */
    public function regenerateSlug(): void
    {
        $this->url = $this->generateRandomSlug();
        $this->autoGenerateSlug = true;
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
            'destinationUrl' => [
                'required',
                'url',
                'max:2048',
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
            'destinationUrl.url' => 'Please enter a valid URL including http:// or https://',
            'endDate.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Create the short link.
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

        $biolink = Page::create([
            'user_id' => $user->id,
            'type' => 'link',
            'url' => Str::lower($this->url),
            'location_url' => $this->destinationUrl,
            'is_enabled' => $this->isEnabled,
            'start_date' => $this->startDate ? now()->parse($this->startDate) : null,
            'end_date' => $this->endDate ? now()->parse($this->endDate) : null,
            'settings' => [],
        ]);

        // Record usage
        $workspace = $user->defaultHostWorkspace();
        if ($workspace) {
            $entitlements = app(EntitlementService::class);
            $entitlements->recordUsage(
                $workspace,
                'bio.shortlinks',
                1,
                $user,
                ['biolink_id' => $biolink->id, 'type' => 'link']
            );
        }

        $this->dispatch('notify', message: 'Short link created successfully', type: 'success');

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

        return rtrim($domain, '/').'CreateShortLink.php/'.($this->url ?: 'your-slug');
    }

    /**
     * Get the vanity URL preview (if different from default).
     */
    #[Computed]
    public function vanityUrlPreview(): ?string
    {
        $vanity = config('bio.vanity_domain');
        $default = config('bio.default_domain');

        if (! $vanity || $vanity === $default) {
            return null;
        }

        return rtrim($vanity, '/').'CreateShortLink.php/'.($this->url ?: 'your-slug');
    }

    public function render()
    {
        return view('webpage::admin.create-short-link')
            ->layout('hub::admin.layouts.app', ['title' => 'Create Short Link']);
    }
}
