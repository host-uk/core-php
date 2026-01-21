<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateVcard extends Component
{
    use WithFileUploads;

    // Form fields - URL
    public string $url = '';

    public bool $autoGenerateSlug = true;

    // Contact information
    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public string $phoneWork = '';

    public string $company = '';

    public string $jobTitle = '';

    public string $website = '';

    // Address
    public string $addressStreet = '';

    public string $addressCity = '';

    public string $addressRegion = '';

    public string $addressPostcode = '';

    public string $addressCountry = 'United Kingdom';

    // Social links
    public string $linkedin = '';

    public string $twitter = '';

    public string $facebook = '';

    public string $instagram = '';

    // Profile photo
    public $photo;

    public ?string $photoPreview = null;

    // Notes
    public string $notes = '';

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
     * Mount the component.
     */
    public function mount(): void
    {
        $this->url = $this->generateRandomSlug();
        $this->checkEntitlement();
    }

    /**
     * Check if the user can create more vCard links.
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
            $this->entitlementError = $result->getMessage() ?? 'You have reached your vCard limit.';
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
     * Handle photo upload preview.
     */
    public function updatedPhoto(): void
    {
        $this->validate([
            'photo' => 'image|max:2048', // 2MB max
        ]);

        if ($this->photo) {
            $this->photoPreview = $this->photo->temporaryUrl();
        }
    }

    /**
     * Remove the uploaded photo.
     */
    public function removePhoto(): void
    {
        $this->photo = null;
        $this->photoPreview = null;
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
            'firstName' => ['required', 'string', 'max:100'],
            'lastName' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'phoneWork' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:255'],
            'jobTitle' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'addressStreet' => ['nullable', 'string', 'max:255'],
            'addressCity' => ['nullable', 'string', 'max:100'],
            'addressRegion' => ['nullable', 'string', 'max:100'],
            'addressPostcode' => ['nullable', 'string', 'max:20'],
            'addressCountry' => ['nullable', 'string', 'max:100'],
            'linkedin' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'firstName.required' => 'First name is required.',
            'lastName.required' => 'Last name is required.',
            'email.email' => 'Please enter a valid email address.',
            'website.url' => 'Please enter a valid URL including http:// or https://',
            'photo.max' => 'The photo must not exceed 2MB.',
            'endDate.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Create the vCard link.
     */
    public function create(): void
    {
        // Re-check entitlement
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

        // Handle photo upload
        $photoPath = null;
        if ($this->photo) {
            $storedName = Str::uuid().'Hub'.$this->photo->getClientOriginalExtension();
            $photoPath = $this->photo->storeAs(
                'biolinks/vcards/'.($workspace?->id ?? $user->id),
                $storedName,
                'local'
            );
        }

        // Build settings
        $settings = [
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'phone_work' => $this->phoneWork ?: null,
            'company' => $this->company ?: null,
            'job_title' => $this->jobTitle ?: null,
            'website' => $this->website ?: null,
            'address' => [
                'street' => $this->addressStreet ?: null,
                'city' => $this->addressCity ?: null,
                'region' => $this->addressRegion ?: null,
                'postcode' => $this->addressPostcode ?: null,
                'country' => $this->addressCountry ?: null,
            ],
            'social' => [
                'linkedin' => $this->linkedin ?: null,
                'twitter' => $this->twitter ?: null,
                'facebook' => $this->facebook ?: null,
                'instagram' => $this->instagram ?: null,
            ],
            'photo_path' => $photoPath,
            'notes' => $this->notes ?: null,
        ];

        $biolink = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'type' => 'vcard',
            'url' => Str::lower($this->url),
            'is_enabled' => $this->isEnabled,
            'start_date' => $this->startDate ? now()->parse($this->startDate) : null,
            'end_date' => $this->endDate ? now()->parse($this->endDate) : null,
            'settings' => $settings,
        ]);

        // Record usage
        if ($workspace) {
            $entitlements = app(EntitlementService::class);
            $entitlements->recordUsage(
                $workspace,
                'bio.shortlinks',
                1,
                $user,
                ['biolink_id' => $biolink->id, 'type' => 'vcard']
            );
        }

        $this->dispatch('notify', message: 'vCard created successfully', type: 'success');

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

        return rtrim($domain, '/').'CreateVcard.php/'.($this->url ?: 'your-slug');
    }

    /**
     * Get the full name preview.
     */
    #[Computed]
    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName) ?: 'Your Name';
    }

    public function render()
    {
        return view('webpage::admin.create-vcard')
            ->layout('hub::admin.layouts.app', ['title' => 'Create vCard']);
    }
}
