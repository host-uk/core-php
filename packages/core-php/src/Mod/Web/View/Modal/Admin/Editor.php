<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Web\Effects\Catalog;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pixel;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\Services\ThemeService;

class Editor extends Component
{
    // Biolink data
    public ?int $biolinkId = null;

    public string $url = '';

    public string $type = 'biolink';

    public bool $isEnabled = true;

    public array $settings = [];

    // UI state
    public bool $showBlockPicker = false;

    public bool $showBlockEditor = false;

    public bool $showSettings = false;

    public bool $showPreview = false;

    public bool $showThemeEditor = false;

    public ?int $editingBlockId = null;

    public array $editingBlockSettings = [];

    public string $editingBlockType = '';

    public ?string $editingBlockUrl = null;

    // Theme state
    public ?int $selectedThemeId = null;

    // Pixel state
    public array $selectedPixelIds = [];

    // Effects state
    public array $effects = [];

    public ?string $selectedEffect = null;

    public array $effectConfig = [];

    // Password protection
    public string $newPassword = '';

    // Device preview settings (URL-tracked)
    #[Url(as: 'viewport')]
    public string $selectedViewport = 'phone';

    #[Url(as: 'mode')]
    public string $editorMode = 'visual';

    // HLCRF region state (URL-tracked)
    #[Url(as: 'header')]
    public bool $headerEnabled = false;

    #[Url(as: 'left')]
    public bool $leftEnabled = false;

    #[Url(as: 'right')]
    public bool $rightEnabled = false;

    #[Url(as: 'footer')]
    public bool $footerEnabled = false;

    // Content is usually always enabled, but track for N+1 nested layouts
    public bool $contentEnabled = true;

    // Layout preset
    public string $layoutPreset = 'bio';

    // Target region for adding new blocks
    public string $targetRegion = 'content';

    // Legacy device settings (deprecated)
    public string $selectedDevice = 'iphone-16-pro';

    public ?string $selectedVariant = null;

    public bool $debugMode = false;

    // Dirty tracking
    public bool $isDirty = false;

    // Socials block helper
    public string $socialPlatformToAdd = '';

    // Block picker search
    public string $blockSearch = '';

    /**
     * Mount the component.
     *
     * Accepts URL slug from lt.hn routes.
     * For sub-pages, receives both parentSlug and subSlug route parameters.
     */
    public function mount(?string $slug = null, ?string $parentSlug = null, ?string $subSlug = null): void
    {
        // Handle sub-page route: /{parentSlug}/{subSlug}/settings
        if ($parentSlug !== null && $subSlug !== null) {
            $biolink = $this->resolveSubPage($parentSlug, $subSlug);
        } else {
            $biolink = $this->resolveBiolink($slug);
        }

        $this->biolinkId = $biolink->id;
        $this->url = $biolink->url;
        $this->type = $biolink->type;
        $this->isEnabled = $biolink->is_enabled;
        $this->settings = $biolink->settings ? (array) $biolink->settings : [];
        $this->selectedThemeId = $biolink->theme_id;
        $this->selectedPixelIds = $biolink->pixels()->pluck('biolink_pixel.pixel_id')->toArray();

        // Load effects
        $this->effects = $biolink->effects ? (array) $biolink->effects : [];
        $this->loadEffectState();

        // Load layout preset
        $this->layoutPreset = $biolink->getLayoutPreset();

        // Set enabled regions based on preset and current breakpoint
        $this->syncRegionsFromPreset();
    }

    /**
     * Sync enabled regions based on layout preset and current viewport.
     */
    protected function syncRegionsFromPreset(): void
    {
        $layoutType = $this->getLayoutTypeForCurrentViewport();

        // Layout codes like 'C', 'HCF', 'HLCF', 'HCRF', 'HLCRF'
        // Future: nested layouts may omit C at certain depths
        $this->headerEnabled = str_contains($layoutType, 'H');
        $this->leftEnabled = str_contains($layoutType, 'L');
        $this->contentEnabled = str_contains($layoutType, 'C');
        $this->rightEnabled = str_contains($layoutType, 'R');
        $this->footerEnabled = str_contains($layoutType, 'F');
    }

    /**
     * Get the layout type code for the current viewport.
     */
    public function getLayoutTypeForCurrentViewport(): string
    {
        $presets = config('webpage.layout_presets', []);

        return $presets[$this->layoutPreset][$this->selectedViewport] ?? 'C';
    }

    /**
     * Resolve biolink from ID or URL slug.
     */
    protected function resolveBiolink(int|string $id): Page
    {
        if (is_numeric($id)) {
            return Page::where('user_id', Auth::id())->findOrFail((int) $id);
        }

        return Page::where('url', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * Resolve a sub-page from parent URL and sub-page URL.
     */
    protected function resolveSubPage(string $parentUrl, string $subUrl): Page
    {
        $parent = Page::where('url', $parentUrl)
            ->where('user_id', Auth::id())
            ->whereNull('parent_id')
            ->firstOrFail();

        return Page::where('parent_id', $parent->id)
            ->where('url', $subUrl)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * Get the biolink model.
     */
    #[Computed]
    public function biolink(): ?Page
    {
        return Page::with(['blocks' => fn ($q) => $q->orderBy('order')])
            ->find($this->biolinkId);
    }

    /**
     * Get block types grouped by category with tier access status.
     */
    #[Computed]
    public function blockTypesByCategory(): array
    {
        $types = config('webpage.block_types', []);
        $tierAccess = $this->tierAccess;
        $grouped = [];

        foreach ($types as $key => $config) {
            $category = $config['category'] ?? 'other';
            $tier = $config['tier'] ?? null;

            // Add access status to the config
            $config['locked'] = ! $this->canAccessTier($tier, $tierAccess);
            $config['tier_label'] = $this->getTierLabel($tier);

            $grouped[$category][$key] = $config;
        }

        return $grouped;
    }

    /**
     * Get tier access status for current user.
     */
    #[Computed]
    public function tierAccess(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [
                'pro' => false,
                'ultimate' => false,
                'payment' => false,
            ];
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return [
                'pro' => false,
                'ultimate' => false,
                'payment' => false,
            ];
        }

        $entitlements = app(EntitlementService::class);

        return [
            'pro' => $entitlements->can($workspace, 'bio.tier.pro')->isAllowed(),
            'ultimate' => $entitlements->can($workspace, 'bio.tier.ultimate')->isAllowed(),
            'payment' => $entitlements->can($workspace, 'bio.tier.payment')->isAllowed(),
        ];
    }

    /**
     * Check if user can access a specific tier.
     */
    protected function canAccessTier(?string $tier, array $tierAccess): bool
    {
        if ($tier === null) {
            return true; // Free tier - always accessible
        }

        return $tierAccess[$tier] ?? false;
    }

    /**
     * Get human-readable label for a tier.
     */
    protected function getTierLabel(?string $tier): ?string
    {
        return match ($tier) {
            'pro' => 'Pro',
            'ultimate' => 'Ultimate',
            'payment' => 'Payment',
            default => null,
        };
    }

    /**
     * Get category labels.
     */
    #[Computed]
    public function categories(): array
    {
        return config('webpage.categories', [
            'standard' => ['name' => 'Standard', 'icon' => 'fa-cubes'],
            'embeds' => ['name' => 'Embeds', 'icon' => 'fa-play'],
            'advanced' => ['name' => 'Advanced', 'icon' => 'fa-cogs'],
            'payments' => ['name' => 'Payments', 'icon' => 'fa-credit-card'],
        ]);
    }

    /**
     * Get available tracking pixels for the current workspace.
     */
    #[Computed]
    public function availablePixels()
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
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    /**
     * Toggle a pixel selection.
     */
    public function togglePixel(int $pixelId): void
    {
        if (in_array($pixelId, $this->selectedPixelIds)) {
            $this->selectedPixelIds = array_values(array_diff($this->selectedPixelIds, [$pixelId]));
        } else {
            $this->selectedPixelIds[] = $pixelId;
        }
    }

    /**
     * Save pixel assignments.
     */
    public function savePixels(): void
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            return;
        }

        $biolink->pixels()->sync($this->selectedPixelIds);
        $this->dispatch('notify', message: 'Tracking pixels updated.', type: 'success');
    }

    /**
     * Get viewport options for device preview.
     *
     * Returns the HLCRF viewport configurations with true device dimensions.
     */
    #[Computed]
    public function viewports(): array
    {
        $viewports = config('webpage.viewports.viewports', []);

        // Add FontAwesome icons (name only, core:icon adds fa- prefix)
        $icons = [
            'phone' => 'mobile-screen-button',
            'tablet' => 'tablet-screen-button',
            'desktop' => 'desktop',
        ];

        foreach ($viewports as $key => &$viewport) {
            $viewport['icon'] = $icons[$key] ?? 'desktop';
        }

        return $viewports;
    }

    /**
     * Get the current viewport config.
     */
    #[Computed]
    public function currentViewport(): array
    {
        return $this->viewports[$this->selectedViewport]
            ?? $this->viewports['phone']
            ?? [];
    }

    /**
     * Get enabled regions as an array.
     */
    #[Computed]
    public function enabledRegions(): array
    {
        return [
            'header' => $this->headerEnabled,
            'left' => $this->leftEnabled,
            'content' => $this->contentEnabled,
            'right' => $this->rightEnabled,
            'footer' => $this->footerEnabled,
        ];
    }

    /**
     * Select a viewport breakpoint.
     */
    public function selectViewport(string $viewport): void
    {
        $viewports = config('webpage.viewports.viewports', []);

        if (! isset($viewports[$viewport])) {
            return;
        }

        $this->selectedViewport = $viewport;

        // Sync regions for new viewport
        $this->syncRegionsFromPreset();
    }

    /**
     * Get available layout presets.
     */
    #[Computed]
    public function layoutPresets(): array
    {
        $presets = config('webpage.layout_presets', []);

        return collect($presets)->map(function ($breakpoints, $key) {
            return [
                'key' => $key,
                'name' => ucfirst($key),
                'phone' => $breakpoints['phone'],
                'tablet' => $breakpoints['tablet'],
                'desktop' => $breakpoints['desktop'],
            ];
        })->values()->all();
    }

    /**
     * Select a layout preset.
     */
    public function selectPreset(string $preset): void
    {
        $presets = config('webpage.layout_presets', []);

        if (! isset($presets[$preset])) {
            return;
        }

        $this->layoutPreset = $preset;
        $this->syncRegionsFromPreset();

        // Save to biolink
        $biolink = $this->biolink;
        $layoutConfig = $biolink->layout_config ?? [];
        $layoutConfig['preset'] = $preset;
        $biolink->layout_config = $layoutConfig;
        $biolink->save();

        $this->dispatch('notify', message: 'Layout preset changed to '.ucfirst($preset), type: 'success');
    }

    /**
     * Get blocks grouped by region.
     */
    #[Computed]
    public function blocksByRegion(): array
    {
        return $this->biolink?->getBlocksByRegion() ?? [
            'header' => collect(),
            'left' => collect(),
            'content' => collect(),
            'right' => collect(),
            'footer' => collect(),
        ];
    }

    /**
     * Open block picker for a specific region.
     */
    public function openBlockPickerForRegion(string $region): void
    {
        $this->targetRegion = $region;
        $this->showBlockPicker = true;
    }

    /**
     * Enable a region.
     */
    public function enableRegion(string $region): void
    {
        match ($region) {
            'header' => $this->headerEnabled = true,
            'left' => $this->leftEnabled = true,
            'right' => $this->rightEnabled = true,
            'footer' => $this->footerEnabled = true,
            default => null,
        };
    }

    /**
     * Disable a region.
     */
    public function disableRegion(string $region): void
    {
        match ($region) {
            'header' => $this->headerEnabled = false,
            'left' => $this->leftEnabled = false,
            'right' => $this->rightEnabled = false,
            'footer' => $this->footerEnabled = false,
            default => null,
        };
    }

    /**
     * Toggle a region.
     */
    public function toggleRegion(string $region): void
    {
        match ($region) {
            'header' => $this->headerEnabled = ! $this->headerEnabled,
            'left' => $this->leftEnabled = ! $this->leftEnabled,
            'right' => $this->rightEnabled = ! $this->rightEnabled,
            'footer' => $this->footerEnabled = ! $this->footerEnabled,
            default => null,
        };
    }

    /**
     * Get devices for the current viewport (legacy compatibility).
     *
     * @deprecated Use CSS-only device chrome instead
     */
    #[Computed]
    public function devicesForViewport(): array
    {
        $devices = config('device-frames.devices', []);

        return collect($devices)
            ->filter(fn ($device) => $device['viewport'] === $this->selectedViewport)
            ->all();
    }

    /**
     * Get the currently selected device config (legacy compatibility).
     *
     * @deprecated Use CSS-only device chrome instead
     */
    #[Computed]
    public function currentDevice(): ?array
    {
        return config("device-frames.devices.{$this->selectedDevice}");
    }

    /**
     * Get the current device's available variants (legacy compatibility).
     *
     * @deprecated Use CSS-only device chrome instead
     */
    #[Computed]
    public function currentVariants(): array
    {
        return $this->currentDevice['variants'] ?? [];
    }

    /**
     * Get the effective variant (legacy compatibility).
     *
     * @deprecated Use CSS-only device chrome instead
     */
    #[Computed]
    public function effectiveVariant(): string
    {
        return $this->selectedVariant
            ?? $this->currentDevice['default_variant']
            ?? array_key_first($this->currentVariants);
    }

    /**
     * Select a device and reset variant (legacy compatibility).
     *
     * @deprecated Use CSS-only device chrome instead
     */
    public function selectDevice(string $device): void
    {
        $devices = config('device-frames.devices', []);

        if (! isset($devices[$device])) {
            return;
        }

        $this->selectedDevice = $device;
        $this->selectedVariant = null;
    }

    /**
     * Select a variant.
     */
    public function selectVariant(string $variant): void
    {
        $this->selectedVariant = $variant;
    }

    /**
     * Get approximate colour for variant display.
     */
    public function getVariantColor(string $variant): string
    {
        // Common colour mappings for device variants
        return match ($variant) {
            // Apple colours
            'black', 'space-black', 'black-titanium' => '#1d1d1f',
            'silver', 'white', 'white-titanium' => '#e3e3e0',
            'natural-titanium' => '#97948e',
            'gold', 'light-gold' => '#d4af37',
            'deep-purple' => '#635e6c',
            'desert-titanium' => '#c4a77d',
            'blue', 'blue-titanium', 'deep-blue' => '#4a6fa5',
            'sky-blue' => '#87ceeb',
            'pink' => '#fadadd',
            'teal' => '#4db6ac',
            'ultramarine' => '#3f51b5',
            'cloud-white' => '#f5f5f5',
            'space-gray' => '#535150',
            'green' => '#4caf50',
            'orange', 'cosmic-orange' => '#ff9800',
            'purple' => '#9c27b0',
            'red' => '#f44336',
            'yellow' => '#ffeb3b',
            'stardust' => '#bdb5a1',
            // Android colours
            'obsidian' => '#292929',
            'hazel' => '#8d7b6c',
            'rose-quartz' => '#e8b4b8',
            'porcelain' => '#f0ebe3',
            // Windows/Surface colours
            'graphite' => '#4a4a4a',
            'platinum' => '#e5e4e2',
            // Default
            default => '#888888',
        };
    }

    /**
     * Save biolink settings.
     */
    public function save(): void
    {
        $this->validate([
            'url' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9\-_]+$/i'],
        ]);

        $biolink = $this->biolink;

        // Check URL uniqueness if changed
        if ($this->url !== $biolink->url) {
            $exists = Page::where('url', $this->url)
                ->where('domain_id', $biolink->domain_id)
                ->where('id', '!=', $biolink->id)
                ->exists();

            if ($exists) {
                $this->addError('url', 'This URL is already taken.');

                return;
            }
        }

        // Handle password protection
        $settings = $this->settings;
        if (! empty($this->newPassword)) {
            // Hash and store new password
            $settings['password'] = Hash::make($this->newPassword);
            $this->newPassword = '';
        } elseif (empty($settings['password_protected'])) {
            // Clear password if protection is disabled
            unset($settings['password']);
        }

        $biolink->update([
            'url' => strtolower($this->url),
            'is_enabled' => $this->isEnabled,
            'settings' => $settings,
        ]);

        // Clear public page cache
        $biolink->clearPublicCache();

        // Update local state with new settings
        $this->settings = $settings;

        $this->isDirty = false;
        $this->dispatch('notify', message: 'Biolink saved', type: 'success');
    }

    /**
     * Open block picker.
     */
    public function openBlockPicker(): void
    {
        $this->showBlockPicker = true;
    }

    /**
     * Close block picker.
     */
    public function closeBlockPicker(): void
    {
        $this->showBlockPicker = false;
        $this->blockSearch = '';
    }

    /**
     * Add a new block.
     */
    public function addBlock(string $type): void
    {
        $blockTypes = config('webpage.block_types', []);

        if (! isset($blockTypes[$type])) {
            $this->dispatch('notify', message: 'Invalid block type', type: 'error');

            return;
        }

        // Check tier access
        $tier = $blockTypes[$type]['tier'] ?? null;
        if ($tier !== null && ! $this->canAccessTier($tier, $this->tierAccess)) {
            $tierLabel = $this->getTierLabel($tier);
            $this->dispatch('notify', message: "This block requires a {$tierLabel} plan. Please upgrade to access.", type: 'error');

            return;
        }

        // Check region access
        $allowedRegions = $blockTypes[$type]['allowed_regions'] ?? null;
        $targetShortCode = Block::REGION_SHORT_CODES[$this->targetRegion] ?? 'C';

        if ($allowedRegions !== null && ! in_array($targetShortCode, $allowedRegions)) {
            $this->dispatch('notify', message: 'This block type cannot be added to this region.', type: 'error');

            return;
        }

        $biolink = $this->biolink;
        $maxOrder = $biolink->blocks()->max('order') ?? -1;
        $maxRegionOrder = $biolink->blocks()->where('region', $this->targetRegion)->max('region_order') ?? -1;

        $block = Block::create([
            'workspace_id' => $biolink->workspace_id,
            'biolink_id' => $biolink->id,
            'type' => $type,
            'region' => $this->targetRegion,
            'settings' => $this->getDefaultSettings($type),
            'order' => $maxOrder + 1,
            'region_order' => $maxRegionOrder + 1,
            'is_enabled' => true,
        ]);

        $this->closeBlockPicker();
        $this->targetRegion = 'content'; // Reset to default
        $this->editBlock($block->id);
    }

    /**
     * Get default settings for a block type.
     */
    protected function getDefaultSettings(string $type): array
    {
        return match ($type) {
            'heading' => ['text' => 'New Heading', 'size' => 'h2', 'alignment' => 'center'],
            'paragraph' => ['text' => 'Your text here...', 'alignment' => 'center'],
            'link' => ['name' => 'Link Button', 'background_color' => '#000000', 'text_color' => '#ffffff', 'border_radius' => 'rounded'],
            'avatar' => ['size' => 100, 'border_radius' => 'round'],
            'divider' => ['style' => 'solid', 'color' => '#e5e7eb'],
            'socials' => ['platforms' => [], 'style' => 'colored', 'size' => 'medium'],
            'youtube' => ['video_id' => ''],
            'spotify' => ['uri' => ''],
            'image' => ['alt' => 'Image'],
            default => [],
        };
    }

    /**
     * Edit a block.
     */
    public function editBlock(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->findOrFail($blockId);

        $this->editingBlockId = $block->id;
        $this->editingBlockType = $block->type;
        $this->editingBlockSettings = $block->settings ? (array) $block->settings : [];
        $this->editingBlockUrl = $block->location_url;
        $this->showBlockEditor = true;
    }

    /**
     * Save block settings.
     */
    public function saveBlock(): void
    {
        if (! $this->editingBlockId) {
            return;
        }

        $block = Block::where('biolink_id', $this->biolinkId)
            ->findOrFail($this->editingBlockId);

        $block->update([
            'settings' => $this->editingBlockSettings,
            'location_url' => $this->editingBlockUrl,
        ]);

        $this->closeBlockEditor();
        $this->dispatch('notify', message: 'Block saved', type: 'success');
    }

    /**
     * Close block editor.
     */
    public function closeBlockEditor(): void
    {
        $this->showBlockEditor = false;
        $this->editingBlockId = null;
        $this->editingBlockSettings = [];
        $this->editingBlockType = '';
        $this->editingBlockUrl = null;
        $this->socialPlatformToAdd = '';
    }

    /**
     * Add a social platform to the current block.
     */
    public function addSocialPlatform(): void
    {
        if (empty($this->socialPlatformToAdd)) {
            return;
        }

        $platforms = $this->editingBlockSettings['platforms'] ?? [];
        $platforms[$this->socialPlatformToAdd] = '';
        $this->editingBlockSettings['platforms'] = $platforms;
        $this->socialPlatformToAdd = '';
    }

    /**
     * Remove a social platform from the current block.
     */
    public function removeSocialPlatform(string $key): void
    {
        $platforms = $this->editingBlockSettings['platforms'] ?? [];
        unset($platforms[$key]);
        $this->editingBlockSettings['platforms'] = $platforms;
    }

    /**
     * Toggle block enabled status.
     */
    public function toggleBlock(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->findOrFail($blockId);
        $block->update(['is_enabled' => ! $block->is_enabled]);
    }

    /**
     * Delete a block.
     */
    public function deleteBlock(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->findOrFail($blockId);
        $order = $block->order;
        $block->delete();

        // Reorder remaining blocks
        Block::where('biolink_id', $this->biolinkId)
            ->where('order', '>', $order)
            ->decrement('order');

        if ($this->editingBlockId === $blockId) {
            $this->closeBlockEditor();
        }

        $this->dispatch('notify', message: 'Block deleted', type: 'success');
    }

    /**
     * Duplicate a block.
     */
    public function duplicateBlock(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->findOrFail($blockId);

        // Shift blocks after this one
        Block::where('biolink_id', $this->biolinkId)
            ->where('order', '>', $block->order)
            ->increment('order');

        $newBlock = $block->replicate();
        $newBlock->order = $block->order + 1;
        $newBlock->clicks = 0;
        $newBlock->save();

        $this->dispatch('notify', message: 'Block duplicated', type: 'success');
    }

    /**
     * Move block up.
     */
    public function moveBlockUp(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->findOrFail($blockId);

        if ($block->order === 0) {
            return;
        }

        // Swap with previous block
        Block::where('biolink_id', $this->biolinkId)
            ->where('order', $block->order - 1)
            ->update(['order' => $block->order]);

        $block->update(['order' => $block->order - 1]);
    }

    /**
     * Move block down.
     */
    public function moveBlockDown(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->findOrFail($blockId);
        $maxOrder = Block::where('biolink_id', $this->biolinkId)->max('order');

        if ($block->order >= $maxOrder) {
            return;
        }

        // Swap with next block
        Block::where('biolink_id', $this->biolinkId)
            ->where('order', $block->order + 1)
            ->update(['order' => $block->order]);

        $block->update(['order' => $block->order + 1]);
    }

    /**
     * Handle block reorder from drag and drop within a region.
     */
    #[On('blocks-reordered')]
    public function reorderBlocks(array $order, ?string $region = null): void
    {
        foreach ($order as $position => $blockId) {
            $update = ['order' => $position];

            // If region specified, also update region_order
            if ($region !== null) {
                $update['region_order'] = $position;
            }

            Block::where('biolink_id', $this->biolinkId)
                ->where('id', $blockId)
                ->update($update);
        }
    }

    /**
     * Move a block to a different region.
     */
    public function moveBlockToRegion(int $blockId, string $targetRegion, ?int $beforeBlockId = null): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->find($blockId);

        if (! $block) {
            return;
        }

        // Check if block type is allowed in target region
        $blockTypes = config('webpage.block_types', []);
        $blockConfig = $blockTypes[$block->type] ?? [];
        $allowedRegions = $blockConfig['allowed_regions'] ?? null;
        $targetShortCode = Block::REGION_SHORT_CODES[$targetRegion] ?? 'C';

        if ($allowedRegions !== null && ! in_array($targetShortCode, $allowedRegions)) {
            $this->dispatch('notify', message: 'This block type cannot be placed in '.ucfirst($targetRegion), type: 'error');

            return;
        }

        // Calculate new region_order
        if ($beforeBlockId !== null) {
            // Insert before specific block
            $beforeBlock = Block::where('biolink_id', $this->biolinkId)->find($beforeBlockId);
            if ($beforeBlock && $beforeBlock->region === $targetRegion) {
                $newOrder = $beforeBlock->region_order;

                // Shift blocks after this position
                Block::where('biolink_id', $this->biolinkId)
                    ->where('region', $targetRegion)
                    ->where('region_order', '>=', $newOrder)
                    ->increment('region_order');
            } else {
                // Append to end
                $newOrder = Block::where('biolink_id', $this->biolinkId)
                    ->where('region', $targetRegion)
                    ->max('region_order') + 1;
            }
        } else {
            // Append to end of region
            $newOrder = Block::where('biolink_id', $this->biolinkId)
                ->where('region', $targetRegion)
                ->max('region_order') ?? -1;
            $newOrder++;
        }

        // Update the block
        $oldRegion = $block->region;
        $oldRegionOrder = $block->region_order;

        $block->update([
            'region' => $targetRegion,
            'region_order' => $newOrder,
        ]);

        // Reorder old region to fill gap
        Block::where('biolink_id', $this->biolinkId)
            ->where('region', $oldRegion)
            ->where('region_order', '>', $oldRegionOrder)
            ->decrement('region_order');

        $this->dispatch('notify', message: 'Block moved to '.ucfirst($targetRegion), type: 'success');
    }

    /**
     * Toggle breakpoint visibility for a block.
     */
    public function toggleBreakpointVisibility(int $blockId, string $breakpoint): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->find($blockId);

        if (! $block) {
            return;
        }

        $validBreakpoints = ['phone', 'tablet', 'desktop'];
        if (! in_array($breakpoint, $validBreakpoints)) {
            return;
        }

        // Get current visibility (null means all visible)
        $visibility = $block->breakpoint_visibility ?? $validBreakpoints;

        if (in_array($breakpoint, $visibility)) {
            // Remove this breakpoint
            $visibility = array_values(array_diff($visibility, [$breakpoint]));
        } else {
            // Add this breakpoint
            $visibility[] = $breakpoint;
        }

        // If all are selected, set to null (default all visible)
        if (count($visibility) === 3) {
            $visibility = null;
        }

        // Don't allow hiding from all breakpoints
        if ($visibility !== null && count($visibility) === 0) {
            $this->dispatch('notify', message: 'Block must be visible on at least one breakpoint', type: 'error');

            return;
        }

        $block->update(['breakpoint_visibility' => $visibility]);
    }

    /**
     * Reset breakpoint visibility to show on all.
     */
    public function resetBreakpointVisibility(int $blockId): void
    {
        $block = Block::where('biolink_id', $this->biolinkId)->find($blockId);

        if (! $block) {
            return;
        }

        $block->update(['breakpoint_visibility' => null]);
    }

    /**
     * Open settings panel.
     */
    public function openSettings(): void
    {
        $this->showSettings = true;
    }

    /**
     * Close settings panel.
     */
    public function closeSettings(): void
    {
        $this->showSettings = false;
    }

    /**
     * Open theme editor panel.
     */
    public function openThemeEditor(): void
    {
        $this->showThemeEditor = true;
    }

    /**
     * Close theme editor panel.
     */
    public function closeThemeEditor(): void
    {
        $this->showThemeEditor = false;
    }

    /**
     * Get available themes for the theme selector.
     */
    #[Computed]
    public function availableThemes()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return Theme::system()->active()->orderBy('sort_order')->get();
        }

        $themeService = app(ThemeService::class);

        return $themeService->getAvailableThemes($user);
    }

    /**
     * Get the currently selected theme.
     */
    #[Computed]
    public function currentTheme(): ?Theme
    {
        if (! $this->selectedThemeId) {
            return null;
        }

        return Theme::find($this->selectedThemeId);
    }

    /**
     * Apply a theme to the bio.
     */
    public function applyTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            $this->dispatch('notify', message: 'Theme not found', type: 'error');

            return;
        }

        // Check premium access
        if ($theme->is_premium) {
            $user = Auth::user();
            if ($user instanceof User) {
                $themeService = app(ThemeService::class);
                $workspace = $user->defaultHostWorkspace();
                // Use reflection or check the method directly
                $hasPremium = $this->tierAccess['pro'] || $this->tierAccess['ultimate'];
                if (! $hasPremium) {
                    $this->dispatch('notify', message: 'This theme requires a Pro or Ultimate plan.', type: 'error');

                    return;
                }
            }
        }

        $biolink = $this->biolink;
        $biolink->theme_id = $themeId;
        $biolink->save();
        $biolink->clearPublicCache();

        $this->selectedThemeId = $themeId;
        $this->dispatch('notify', message: 'Theme applied', type: 'success');
    }

    /**
     * Remove theme from biolink (revert to default).
     */
    public function removeTheme(): void
    {
        $biolink = $this->biolink;
        $biolink->theme_id = null;
        $biolink->save();
        $biolink->clearPublicCache();

        $this->selectedThemeId = null;
        $this->dispatch('notify', message: 'Theme removed', type: 'success');
    }

    /**
     * Handle theme applied event from ThemeEditor child component.
     */
    #[On('theme-applied')]
    public function handleThemeApplied(array $settings): void
    {
        // Refresh the component
        $this->selectedThemeId = $this->biolink->theme_id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Effects
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get available background effects for the UI.
     */
    #[Computed]
    public function availableBackgroundEffects(): array
    {
        return Catalog::backgroundOptions();
    }

    /**
     * Get effect categories for grouping.
     */
    #[Computed]
    public function effectCategories(): array
    {
        return Catalog::backgroundCategories();
    }

    /**
     * Load effect state from the effects array.
     */
    protected function loadEffectState(): void
    {
        $bgEffect = $this->effects['background'] ?? null;

        if ($bgEffect && isset($bgEffect['effect'])) {
            $this->selectedEffect = $bgEffect['effect'];
            $this->effectConfig = (array) $bgEffect;
        } else {
            $this->selectedEffect = null;
            $this->effectConfig = [];
        }
    }

    /**
     * Select a background effect.
     */
    public function selectBackgroundEffect(?string $effectSlug): void
    {
        if ($effectSlug === null || $effectSlug === '') {
            $this->clearBackgroundEffect();

            return;
        }

        $effectClass = Catalog::getBackgroundEffect($effectSlug);

        if (! $effectClass) {
            $this->dispatch('notify', message: 'Invalid effect', type: 'error');

            return;
        }

        // Get defaults for this effect
        $defaults = $effectClass::defaults();

        $this->selectedEffect = $effectSlug;
        $this->effectConfig = array_merge($defaults, ['effect' => $effectSlug]);
        $this->effects['background'] = $this->effectConfig;

        $this->saveEffects();
    }

    /**
     * Update an effect configuration value.
     */
    public function updateEffectConfig(string $key, mixed $value): void
    {
        if (! $this->selectedEffect) {
            return;
        }

        $this->effectConfig[$key] = $value;
        $this->effects['background'] = $this->effectConfig;

        $this->saveEffects();
    }

    /**
     * Clear the background effect.
     */
    public function clearBackgroundEffect(): void
    {
        $this->selectedEffect = null;
        $this->effectConfig = [];
        unset($this->effects['background']);

        $this->saveEffects();
    }

    /**
     * Save effects to the biolink.
     */
    protected function saveEffects(): void
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            return;
        }

        $biolink->effects = ! empty($this->effects) ? $this->effects : null;
        $biolink->save();
        $biolink->clearPublicCache();

        $this->dispatch('notify', message: 'Effects updated', type: 'success');
    }

    /**
     * Get the current effect parameters for the UI.
     */
    #[Computed]
    public function currentEffectParameters(): array
    {
        if (! $this->selectedEffect) {
            return [];
        }

        $effectClass = Catalog::getBackgroundEffect($this->selectedEffect);

        if (! $effectClass) {
            return [];
        }

        return $effectClass::parameters();
    }

    /**
     * Toggle preview.
     */
    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    /**
     * Toggle debug/calibration mode.
     */
    public function toggleDebugMode(): void
    {
        $this->debugMode = ! $this->debugMode;
    }

    /**
     * Get the public URL (for display/sharing).
     */
    #[Computed]
    public function publicUrl(): string
    {
        $biolink = $this->biolink;

        // Use custom domain if set
        if ($biolink->domain) {
            return rtrim($biolink->domain->scheme.'://'.$biolink->domain->host, '/').'/'.$biolink->url;
        }

        // Use current request host if on a bio domain
        $host = request()->getHost();
        $scheme = request()->getScheme();

        return $scheme.'://'.$host.'/'.$biolink->url;
    }

    /**
     * Get the preview URL (for iframe).
     */
    #[Computed]
    public function previewUrl(): string
    {
        $biolink = $this->biolink;

        // Use custom domain if set
        if ($biolink->domain) {
            return rtrim($biolink->domain->scheme.'://'.$biolink->domain->host, '/').'/'.$biolink->url;
        }

        // Use current request host
        $host = request()->getHost();
        $scheme = request()->getScheme();

        return $scheme.'://'.$host.'/'.$biolink->url;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sub-Pages
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get sub-pages for this page (if root page).
     */
    #[Computed]
    public function subPages(): \Illuminate\Database\Eloquent\Collection
    {
        $biolink = $this->biolink;

        if (! $biolink || $biolink->isSubPage()) {
            return collect();
        }

        return $biolink->subPages()->get();
    }

    /**
     * Get the parent page (if this is a sub-page).
     */
    #[Computed]
    public function parentPage(): ?Page
    {
        $biolink = $this->biolink;

        if (! $biolink || ! $biolink->isSubPage()) {
            return null;
        }

        return $biolink->parent;
    }

    /**
     * Check if user can create more sub-pages.
     */
    #[Computed]
    public function canCreateSubPage(): bool
    {
        $user = Auth::user();
        $biolink = $this->biolink;

        // Can only create sub-pages from root pages
        if (! $biolink || $biolink->isSubPage()) {
            return false;
        }

        return $user->canCreateSubPage();
    }

    /**
     * Get remaining sub-page slots.
     */
    #[Computed]
    public function remainingSubPageSlots(): int
    {
        return Auth::user()->remainingSubPageSlots();
    }

    /**
     * Create a new sub-page under this page.
     */
    public function createSubPage(): void
    {
        $user = Auth::user();
        $biolink = $this->biolink;

        if (! $biolink || $biolink->isSubPage()) {
            session()->flash('error', 'Cannot create sub-pages from a sub-page.');

            return;
        }

        if (! $user->canCreateSubPage()) {
            session()->flash('error', 'You\'ve reached your sub-page limit. Purchase a Sub-Pages Pack boost to create more.');

            return;
        }

        // Generate a random URL for the sub-page
        $subUrl = 'page-'.substr(md5(uniqid()), 0, 6);

        $subPage = Page::create([
            'user_id' => $user->id,
            'parent_id' => $biolink->id,
            'type' => 'biolink',
            'url' => $subUrl,
            'is_enabled' => true,
            'settings' => [
                'seo' => [
                    'title' => 'New Sub-Page',
                ],
            ],
        ]);

        // Redirect to the new sub-page editor
        $this->redirect(route('bio.settings', ['id' => $biolink->url.'/'.$subUrl]));
    }

    public function render()
    {
        return view('webpage::admin.editor')
            ->layout('client::layouts.app', [
                'title' => 'Edit: '.$this->url,
                'bioUrl' => $this->url,
            ]);
    }
}
