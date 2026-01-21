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

class CreateFileLink extends Component
{
    use WithFileUploads;

    // Form fields
    public string $url = '';

    public $file;

    public string $filename = '';

    public bool $autoGenerateSlug = true;

    // Advanced options
    public bool $showAdvanced = false;

    public bool $isEnabled = true;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?string $password = null;

    public bool $passwordEnabled = false;

    // Entitlement state
    public bool $canCreate = true;

    public ?string $entitlementError = null;

    public ?int $usedCount = null;

    public ?int $limitCount = null;

    // Allowed file extensions
    protected array $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf', 'odt', 'ods', 'odp',
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
        'mp3', 'wav', 'ogg', 'm4a',
        'mp4', 'webm', 'mov', 'avi',
        'zip', 'rar', '7z', 'tar', 'gz',
    ];

    /**
     * Mount the component and generate a random slug.
     */
    public function mount(): void
    {
        $this->url = $this->generateRandomSlug();
        $this->checkEntitlement();
    }

    /**
     * Check if the user can create more file links.
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
            $this->entitlementError = $result->getMessage() ?? 'You have reached your file link limit.';
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
        $maxSize = 50 * 1024; // 50MB in KB
        $extensions = implode(',', $this->allowedExtensions);

        return [
            'url' => [
                'required',
                'string',
                'min:3',
                'max:256',
                'regex:/^[a-z0-9\-_]+$/i',
            ],
            'file' => [
                'required',
                'file',
                'max:'.$maxSize,
                'mimes:'.$extensions,
            ],
            'filename' => ['nullable', 'string', 'max:255'],
            'startDate' => ['nullable', 'date'],
            'endDate' => ['nullable', 'date', 'after_or_equal:startDate'],
            'password' => ['nullable', 'string', 'min:4', 'max:64'],
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
            'file.required' => 'Please select a file to upload.',
            'file.max' => 'The file must not exceed 50MB.',
            'file.mimes' => 'This file type is not allowed.',
            'endDate.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Handle file upload and update filename.
     */
    public function updatedFile(): void
    {
        if ($this->file) {
            $this->filename = $this->file->getClientOriginalName();
        }
    }

    /**
     * Create the file link.
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

        // Store the file
        $workspace = $user->defaultHostWorkspace();
        $extension = $this->file->getClientOriginalExtension();
        $storedName = Str::uuid().'Hub'.$extension;
        $path = $this->file->storeAs(
            'biolinks/files/'.($workspace?->id ?? $user->id),
            $storedName,
            'local'
        );

        // Build settings
        $settings = [
            'file_path' => $path,
            'file_name' => $this->filename ?: $this->file->getClientOriginalName(),
            'file_size' => $this->file->getSize(),
            'file_extension' => $extension,
            'mime_type' => $this->file->getMimeType(),
        ];

        if ($this->passwordEnabled && $this->password) {
            $settings['password'] = bcrypt($this->password);
            $settings['password_protected'] = true;
        }

        $biolink = Page::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'type' => 'file',
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
                ['biolink_id' => $biolink->id, 'type' => 'file']
            );
        }

        $this->dispatch('notify', message: 'File link created successfully', type: 'success');

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

        return rtrim($domain, '/').'CreateFileLink.php/'.($this->url ?: 'your-slug');
    }

    /**
     * Get human-readable max file size.
     */
    #[Computed]
    public function maxFileSizeFormatted(): string
    {
        return '50MB';
    }

    /**
     * Get allowed extensions as a string.
     */
    #[Computed]
    public function allowedExtensionsFormatted(): string
    {
        return implode(', ', array_map(fn ($ext) => '.'.$ext, $this->allowedExtensions));
    }

    public function render()
    {
        return view('webpage::admin.create-file-link')
            ->layout('hub::admin.layouts.app', ['title' => 'Create File Link']);
    }
}
