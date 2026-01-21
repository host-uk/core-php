<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Pixel;
use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PixelManager extends Component
{
    // Create/Edit modal state
    public bool $showModal = false;

    public ?int $editingPixelId = null;

    public string $type = 'facebook';

    public string $name = '';

    public string $pixelId = '';

    // Delete confirmation
    public bool $showDeleteModal = false;

    public ?int $deletingPixelId = null;

    /**
     * Get all pixels for the current workspace.
     */
    #[Computed]
    public function pixels()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return collect();
        }

        return Pixel::where('workspace_id', $workspace->id)
            ->withCount('biolinks')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get available pixel types.
     */
    #[Computed]
    public function pixelTypes(): array
    {
        return Pixel::TYPES;
    }

    /**
     * Open modal to create a new pixel.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Open modal to edit an existing pixel.
     */
    public function openEditModal(int $pixelId): void
    {
        $pixel = $this->findPixel($pixelId);

        if (! $pixel) {
            return;
        }

        $this->editingPixelId = $pixel->id;
        $this->type = $pixel->type;
        $this->name = $pixel->name;
        $this->pixelId = $pixel->pixel_id;
        $this->showModal = true;
    }

    /**
     * Close the create/edit modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->editingPixelId = null;
        $this->type = 'facebook';
        $this->name = '';
        $this->pixelId = '';
        $this->resetErrorBag();
    }

    /**
     * Save a pixel (create or update).
     */
    public function save(): void
    {
        $this->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(Pixel::TYPES))],
            'name' => ['required', 'string', 'max:64'],
            'pixelId' => ['required', 'string', 'max:128'],
        ]);

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

        if ($this->editingPixelId) {
            // Update existing pixel
            $pixel = Pixel::where('workspace_id', $workspace->id)
                ->find($this->editingPixelId);

            if (! $pixel) {
                $this->dispatch('notify', message: 'Pixel not found.', type: 'error');

                return;
            }

            $pixel->update([
                'type' => $this->type,
                'name' => $this->name,
                'pixel_id' => $this->pixelId,
            ]);

            $this->dispatch('notify', message: 'Pixel updated.', type: 'success');
        } else {
            // Create new pixel
            Pixel::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'type' => $this->type,
                'name' => $this->name,
                'pixel_id' => $this->pixelId,
            ]);

            $this->dispatch('notify', message: 'Pixel created.', type: 'success');
        }

        $this->closeModal();
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $pixelId): void
    {
        $pixel = $this->findPixel($pixelId);

        if (! $pixel) {
            return;
        }

        $this->deletingPixelId = $pixelId;
        $this->showDeleteModal = true;
    }

    /**
     * Close delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingPixelId = null;
    }

    /**
     * Delete a pixel.
     */
    public function deletePixel(): void
    {
        if (! $this->deletingPixelId) {
            return;
        }

        $pixel = $this->findPixel($this->deletingPixelId);

        if (! $pixel) {
            $this->dispatch('notify', message: 'Pixel not found.', type: 'error');
            $this->closeDeleteModal();

            return;
        }

        // Detach from all biolinks first
        $pixel->biolinks()->detach();
        $pixel->delete();

        $this->dispatch('notify', message: 'Pixel deleted.', type: 'success');
        $this->closeDeleteModal();
    }

    /**
     * Find a pixel belonging to the current workspace.
     */
    private function findPixel(int $pixelId): ?Pixel
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return null;
        }

        return Pixel::where('workspace_id', $workspace->id)
            ->find($pixelId);
    }

    /**
     * Get the placeholder text for pixel ID field based on type.
     */
    public function getPixelIdPlaceholder(): string
    {
        return match ($this->type) {
            'facebook' => 'e.g. 1234567890123456',
            'google_analytics' => 'e.g. G-XXXXXXXXXX',
            'google_tag_manager' => 'e.g. GTM-XXXXXXX',
            'google_ads' => 'e.g. AW-XXXXXXXXX',
            'tiktok' => 'e.g. XXXXXXXXXXXXXXXXXX',
            'twitter' => 'e.g. tw-xxxxx-xxxxx',
            'pinterest' => 'e.g. 1234567890123',
            'linkedin' => 'e.g. 1234567',
            'snapchat' => 'e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx',
            'quora' => 'e.g. xxxxxxxxxxxxxxxxxxxxxxxx',
            'bing' => 'e.g. 12345678',
            default => 'Enter your pixel/tag ID',
        };
    }

    /**
     * Get the icon for a pixel type.
     */
    public function getPixelIcon(string $type): string
    {
        return match ($type) {
            'facebook' => 'fa-brands fa-facebook',
            'google_analytics', 'google_tag_manager', 'google_ads' => 'fa-brands fa-google',
            'tiktok' => 'fa-brands fa-tiktok',
            'twitter' => 'fa-brands fa-twitter',
            'pinterest' => 'fa-brands fa-pinterest',
            'linkedin' => 'fa-brands fa-linkedin',
            'snapchat' => 'fa-brands fa-snapchat',
            'quora' => 'fa-brands fa-quora',
            'bing' => 'fa-brands fa-microsoft',
            default => 'fa-solid fa-code',
        };
    }

    /**
     * Get the colour for a pixel type.
     */
    public function getPixelColour(string $type): string
    {
        return match ($type) {
            'facebook' => '#1877f2',
            'google_analytics', 'google_tag_manager' => '#f9ab00',
            'google_ads' => '#4285f4',
            'tiktok' => '#000000',
            'twitter' => '#1da1f2',
            'pinterest' => '#e60023',
            'linkedin' => '#0a66c2',
            'snapchat' => '#fffc00',
            'quora' => '#b92b27',
            'bing' => '#00a1f1',
            default => '#6b7280',
        };
    }

    public function render()
    {
        return view('webpage::admin.pixel-manager')
            ->layout('hub::admin.layouts.app', ['title' => 'Tracking Pixels']);
    }
}
