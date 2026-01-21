<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\StaticPageSanitiser;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EditStaticPage extends Component
{
    use AuthorizesRequests;

    public Page $biolink;

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

    /**
     * Mount the component.
     */
    public function mount(Page $biolink): void
    {
        $this->authorize('update', $biolink);

        // Verify it's a static page
        if ($biolink->type !== 'static') {
            abort(404, 'Not a static page');
        }

        $this->biolink = $biolink;
        $this->url = $biolink->url;
        $this->isEnabled = (bool) $biolink->is_enabled;
        $this->startDate = $biolink->start_date?->format('Y-m-d\TH:i');
        $this->endDate = $biolink->end_date?->format('Y-m-d\TH:i');

        // Load static page content from settings
        $this->title = $biolink->getSetting('title', '');
        $this->htmlContent = $biolink->getSetting('static_html', '');
        $this->cssContent = $biolink->getSetting('static_css', '');
        $this->jsContent = $biolink->getSetting('static_js', '');

        // Show editors if content exists
        $this->showCssEditor = ! empty($this->cssContent);
        $this->showJsEditor = ! empty($this->jsContent);
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
     * Update the static page.
     */
    public function save(): void
    {
        $this->authorize('update', $this->biolink);

        $this->validate();

        // Check URL uniqueness (exclude current biolink)
        $exists = Page::where('url', Str::lower($this->url))
            ->whereNull('domain_id')
            ->where('id', '!=', $this->biolink->id)
            ->exists();

        if ($exists) {
            $this->addError('url', 'This URL is already taken. Try a different one.');

            return;
        }

        // Sanitise content
        $sanitiser = app(StaticPageSanitiser::class);
        $sanitised = $sanitiser->sanitiseStaticPage(
            $this->htmlContent,
            $this->cssContent,
            $this->jsContent
        );

        // Update the biolink
        $this->biolink->update([
            'url' => Str::lower($this->url),
            'is_enabled' => $this->isEnabled,
            'start_date' => $this->startDate ? now()->parse($this->startDate) : null,
            'end_date' => $this->endDate ? now()->parse($this->endDate) : null,
            'settings' => array_merge($this->biolink->settings->toArray(), [
                'title' => $this->title,
                'static_html' => $sanitised['html'],
                'static_css' => $sanitised['css'],
                'static_js' => $sanitised['js'],
            ]),
        ]);

        $this->dispatch('notify', message: 'Static page updated successfully', type: 'success');
    }

    /**
     * Delete the static page.
     */
    public function delete(): void
    {
        $this->authorize('delete', $this->biolink);

        $this->biolink->delete();

        $this->dispatch('notify', message: 'Static page deleted successfully', type: 'success');

        $this->redirect(route('hub.bio.index'), navigate: true);
    }

    /**
     * Get the full URL preview.
     */
    #[Computed]
    public function fullUrlPreview(): string
    {
        if ($this->biolink->domain) {
            return $this->biolink->domain->scheme.'://'.$this->biolink->domain->host.'/'.($this->url ?: 'your-slug');
        }

        $domain = config('bio.default_domain', 'https://bio.host.uk.com');

        return rtrim($domain, '/').'EditStaticPage.php/'.($this->url ?: 'your-slug');
    }

    public function render()
    {
        return view('webpage::admin.edit-static-page')
            ->layout('hub::admin.layouts.app', ['title' => 'Edit Static Page']);
    }
}
