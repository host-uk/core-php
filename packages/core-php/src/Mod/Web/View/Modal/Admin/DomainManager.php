<?php

declare(strict_types=1);

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\DomainVerificationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DomainManager extends Component
{
    use WithPagination;

    // Add domain modal
    public bool $showAddModal = false;

    public string $newHost = '';

    public ?int $defaultBiolinkId = null;

    // Verification modal
    public bool $showVerifyModal = false;

    public ?Domain $selectedDomain = null;

    public array $dnsInstructions = [];

    public array $dnsStatus = [];

    // Edit modal
    public bool $showEditModal = false;

    public ?int $editingDomainId = null;

    public string $editCustomIndexUrl = '';

    public string $editCustomNotFoundUrl = '';

    public ?int $editDefaultBiolinkId = null;

    protected DomainVerificationService $verificationService;

    public function boot(DomainVerificationService $verificationService): void
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Get paginated domains for the current workspace.
     */
    #[Computed]
    public function domains()
    {
        return Domain::ownedByCurrentWorkspace()
            ->with(['exclusiveLink', 'user'])
            ->latest()
            ->paginate(10);
    }

    /**
     * Get biolinks for default selection dropdown.
     */
    #[Computed]
    public function biolinks()
    {
        return Page::ownedByCurrentWorkspace()
            ->where('type', 'biolink')
            ->orderBy('url')
            ->get(['id', 'url']);
    }

    /**
     * Open the add domain modal.
     */
    public function openAddModal(): void
    {
        $this->reset(['newHost', 'defaultBiolinkId']);
        $this->showAddModal = true;
    }

    /**
     * Close the add domain modal.
     */
    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->resetValidation();
    }

    /**
     * Add a new custom domain.
     */
    public function addDomain(): void
    {
        $this->validate([
            'newHost' => ['required', 'string', 'max:253'],
            'defaultBiolinkId' => ['nullable', 'exists:biolinks,id'],
        ]);

        // Normalise the host
        $host = $this->verificationService->normaliseHost($this->newHost);

        // Validate domain format
        if (! $this->verificationService->validateDomainFormat($host)) {
            $this->addError('newHost', 'Please enter a valid domain name (e.g., example.com or sub.example.com).');

            return;
        }

        // Check if domain is reserved
        if ($this->verificationService->isDomainReserved($host)) {
            $this->addError('newHost', 'This domain is reserved and cannot be added.');

            return;
        }

        // Check if domain already exists
        $exists = Domain::where('host', $host)->exists();
        if ($exists) {
            $this->addError('newHost', 'This domain is already registered.');

            return;
        }

        // Create the domain
        $domain = Domain::create([
            'user_id' => Auth::id(),
            'host' => $host,
            'biolink_id' => $this->defaultBiolinkId,
        ]);

        // Generate verification token
        $domain->generateVerificationToken();

        $this->closeAddModal();
        $this->dispatch('notify', message: 'Domain added. Please verify ownership to activate.', type: 'success');

        // Open verification modal
        $this->openVerifyModal($domain->id);
    }

    /**
     * Open the verification modal for a domain.
     */
    public function openVerifyModal(int $domainId): void
    {
        $this->selectedDomain = Domain::ownedByCurrentWorkspace()
            ->findOrFail($domainId);

        $this->dnsInstructions = $this->verificationService->getDnsInstructions($this->selectedDomain);
        $this->dnsStatus = [];
        $this->showVerifyModal = true;
    }

    /**
     * Close the verification modal.
     */
    public function closeVerifyModal(): void
    {
        $this->showVerifyModal = false;
        $this->selectedDomain = null;
        $this->dnsInstructions = [];
        $this->dnsStatus = [];
    }

    /**
     * Check DNS status for the selected domain.
     */
    public function checkDns(): void
    {
        if (! $this->selectedDomain) {
            return;
        }

        $this->dnsStatus = $this->verificationService->checkDnsResolution($this->selectedDomain->host);
    }

    /**
     * Attempt to verify the selected domain.
     */
    public function verifyDomain(): void
    {
        if (! $this->selectedDomain) {
            return;
        }

        $verified = $this->verificationService->verify($this->selectedDomain);

        if ($verified) {
            $this->dispatch('notify', message: 'Domain verified and activated.', type: 'success');
            $this->closeVerifyModal();
        } else {
            $this->dispatch('notify', message: 'Verification failed. Please check your DNS settings and try again.', type: 'error');
            $this->checkDns();
        }
    }

    /**
     * Open the edit modal for a domain.
     */
    public function openEditModal(int $domainId): void
    {
        $domain = Domain::ownedByCurrentWorkspace()->findOrFail($domainId);

        $this->editingDomainId = $domain->id;
        $this->editCustomIndexUrl = $domain->custom_index_url ?? '';
        $this->editCustomNotFoundUrl = $domain->custom_not_found_url ?? '';
        $this->editDefaultBiolinkId = $domain->biolink_id;
        $this->showEditModal = true;
    }

    /**
     * Close the edit modal.
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingDomainId = null;
        $this->resetValidation();
    }

    /**
     * Save domain settings.
     */
    public function saveDomain(): void
    {
        $this->validate([
            'editCustomIndexUrl' => ['nullable', 'url', 'max:512'],
            'editCustomNotFoundUrl' => ['nullable', 'url', 'max:512'],
            'editDefaultBiolinkId' => ['nullable', 'exists:biolinks,id'],
        ]);

        $domain = Domain::ownedByCurrentWorkspace()
            ->findOrFail($this->editingDomainId);

        $domain->update([
            'custom_index_url' => $this->editCustomIndexUrl ?: null,
            'custom_not_found_url' => $this->editCustomNotFoundUrl ?: null,
            'biolink_id' => $this->editDefaultBiolinkId,
        ]);

        $this->closeEditModal();
        $this->dispatch('notify', message: 'Domain settings saved.', type: 'success');
    }

    /**
     * Toggle domain enabled status.
     */
    public function toggleEnabled(int $domainId): void
    {
        $domain = Domain::ownedByCurrentWorkspace()->findOrFail($domainId);

        // Only allow enabling if verified
        if (! $domain->is_enabled && ! $domain->isVerified()) {
            $this->dispatch('notify', message: 'Please verify the domain before enabling it.', type: 'error');

            return;
        }

        $domain->update(['is_enabled' => ! $domain->is_enabled]);
        $this->dispatch('notify', message: 'Domain '.($domain->is_enabled ? 'enabled' : 'disabled').'.', type: 'success');
    }

    /**
     * Delete a domain.
     */
    public function deleteDomain(int $domainId): void
    {
        $domain = Domain::ownedByCurrentWorkspace()->findOrFail($domainId);
        $domain->delete();

        $this->dispatch('notify', message: 'Domain deleted.', type: 'success');
    }

    /**
     * Regenerate verification token.
     */
    public function regenerateToken(int $domainId): void
    {
        $domain = Domain::ownedByCurrentWorkspace()->findOrFail($domainId);
        $domain->generateVerificationToken();

        // Refresh instructions if this is the selected domain
        if ($this->selectedDomain && $this->selectedDomain->id === $domainId) {
            $this->selectedDomain = $domain->fresh();
            $this->dnsInstructions = $this->verificationService->getDnsInstructions($domain);
        }

        $this->dispatch('notify', message: 'Verification token regenerated.', type: 'success');
    }

    public function render()
    {
        return view('webpage::admin.domain-manager')
            ->layout('hub::admin.layouts.app', ['title' => 'Custom Domains']);
    }
}
