<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use DateTimeZone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateEvent extends Component
{
    // Form fields - URL
    public string $url = '';

    public bool $autoGenerateSlug = true;

    // Event details
    public string $eventName = '';

    public string $description = '';

    public string $startDate = '';

    public string $startTime = '';

    public string $endDate = '';

    public string $endTime = '';

    public string $timezone = 'Europe/London';

    public bool $allDay = false;

    // Location
    public string $locationType = 'physical'; // physical, online, hybrid

    public string $locationName = '';

    public string $locationAddress = '';

    public string $onlineUrl = '';

    // Organiser
    public string $organiserName = '';

    public string $organiserEmail = '';

    // Advanced options
    public bool $showAdvanced = false;

    public bool $isEnabled = true;

    public ?string $linkStartDate = null;

    public ?string $linkEndDate = null;

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

        // Set default dates to tomorrow
        $this->startDate = now()->addDay()->format('Y-m-d');
        $this->startTime = '10:00';
        $this->endDate = now()->addDay()->format('Y-m-d');
        $this->endTime = '12:00';
    }

    /**
     * Check if the user can create more event links.
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
            $this->entitlementError = $result->getMessage() ?? 'You have reached your event page limit.';
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
     * Handle all-day toggle.
     */
    public function updatedAllDay(): void
    {
        if ($this->allDay) {
            $this->startTime = '';
            $this->endTime = '';
        } else {
            $this->startTime = '10:00';
            $this->endTime = '12:00';
        }
    }

    /**
     * Get available timezones.
     */
    #[Computed]
    public function timezones(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        // Group by region
        $grouped = [];
        foreach ($timezones as $tz) {
            $parts = explode('/', $tz);
            $region = $parts[0] ?? 'Other';
            $grouped[$region][] = $tz;
        }

        return $grouped;
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
            'eventName' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'startDate' => ['required', 'date'],
            'startTime' => ['required_unless:allDay,true', 'nullable', 'date_format:H:i'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'endTime' => ['required_unless:allDay,true', 'nullable', 'date_format:H:i'],
            'timezone' => ['required', 'string', 'timezone'],
            'locationType' => ['required', 'in:physical,online,hybrid'],
            'locationName' => ['nullable', 'string', 'max:255'],
            'locationAddress' => ['nullable', 'string', 'max:500'],
            'onlineUrl' => ['nullable', 'url', 'max:500'],
            'organiserName' => ['nullable', 'string', 'max:255'],
            'organiserEmail' => ['nullable', 'email', 'max:255'],
            'linkStartDate' => ['nullable', 'date'],
            'linkEndDate' => ['nullable', 'date', 'after_or_equal:linkStartDate'],
        ];
    }

    /**
     * Custom validation messages.
     */
    protected function messages(): array
    {
        return [
            'url.regex' => 'The URL can only contain letters, numbers, hyphens, and underscores.',
            'eventName.required' => 'Event name is required.',
            'startDate.required' => 'Start date is required.',
            'endDate.after_or_equal' => 'End date must be on or after the start date.',
            'onlineUrl.url' => 'Please enter a valid URL for the online meeting link.',
        ];
    }

    /**
     * Create the event link.
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

        // Build datetime strings
        $startDateTime = $this->startDate;
        $endDateTime = $this->endDate;

        if (! $this->allDay && $this->startTime) {
            $startDateTime .= 'T'.$this->startTime.':00';
        }
        if (! $this->allDay && $this->endTime) {
            $endDateTime .= 'T'.$this->endTime.':00';
        }

        // Build settings
        $settings = [
            'event_name' => $this->eventName,
            'description' => $this->description ?: null,
            'start_datetime' => $startDateTime,
            'end_datetime' => $endDateTime,
            'timezone' => $this->timezone,
            'all_day' => $this->allDay,
            'location' => [
                'type' => $this->locationType,
                'name' => $this->locationName ?: null,
                'address' => $this->locationAddress ?: null,
                'online_url' => $this->onlineUrl ?: null,
            ],
            'organiser' => [
                'name' => $this->organiserName ?: null,
                'email' => $this->organiserEmail ?: null,
            ],
        ];

        $biolink = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'type' => 'event',
            'url' => Str::lower($this->url),
            'is_enabled' => $this->isEnabled,
            'start_date' => $this->linkStartDate ? now()->parse($this->linkStartDate) : null,
            'end_date' => $this->linkEndDate ? now()->parse($this->linkEndDate) : null,
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
                ['biolink_id' => $biolink->id, 'type' => 'event']
            );
        }

        $this->dispatch('notify', message: 'Event page created successfully', type: 'success');

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

        return rtrim($domain, '/').'CreateEvent.php/'.($this->url ?: 'your-slug');
    }

    public function render()
    {
        return view('webpage::admin.create-event')
            ->layout('hub::admin.layouts.app', ['title' => 'Create Event Page']);
    }
}
