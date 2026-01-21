<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AixContentService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AiAssistant extends Component
{
    public ?int $biolinkId = null;

    public string $chatMessage = '';

    public array $chatHistory = [];

    public bool $isGenerating = false;

    public string $currentAction = '';

    public array $quickActions = [
        'generate_bio' => 'Generate bio description',
        'improve_seo' => 'Improve SEO',
        'suggest_improvements' => 'Suggest improvements',
        'generate_social' => 'Generate social description',
    ];

    /**
     * Mount the component.
     */
    public function mount(int $id): void
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);
        $this->biolinkId = $biolink->id;
    }

    /**
     * Get the biolink model.
     */
    #[Computed]
    public function biolink(): ?Page
    {
        return Page::with(['blocks'])->find($this->biolinkId);
    }

    /**
     * Get AI credits status.
     */
    #[Computed]
    public function aiCredits(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [
                'available' => false,
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
            ];
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return [
                'available' => false,
                'limit' => 0,
                'used' => 0,
                'remaining' => 0,
            ];
        }

        $entitlements = app(EntitlementService::class);
        $result = $entitlements->can($workspace, 'ai.credits');

        return [
            'available' => $result->isAllowed(),
            'limit' => $result->limit ?? 0,
            'used' => $result->used ?? 0,
            'remaining' => $result->remaining ?? 0,
            'unlimited' => $result->isUnlimited(),
        ];
    }

    /**
     * Check if user can use AI features.
     */
    protected function canUseAi(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return false;
        }

        $entitlements = app(EntitlementService::class);

        return $entitlements->can($workspace, 'ai.credits')->isAllowed();
    }

    /**
     * Record AI usage.
     */
    protected function recordUsage(int $quantity = 1): void
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
        $entitlements->recordUsage($workspace, 'ai.credits', $quantity, $user);
    }

    /**
     * Execute a quick action.
     */
    public function executeQuickAction(string $action): void
    {
        if (! $this->canUseAi()) {
            $this->addMessage('system', 'You have no AI credits remaining. Please upgrade your plan to continue using AI features.');

            return;
        }

        $this->isGenerating = true;
        $this->currentAction = $action;

        try {
            match ($action) {
                'generate_bio' => $this->handleGenerateBio(),
                'improve_seo' => $this->handleImproveSeo(),
                'suggest_improvements' => $this->handleSuggestImprovements(),
                'generate_social' => $this->handleGenerateSocial(),
                default => $this->addMessage('system', 'Unknown action'),
            };
        } catch (\Exception $e) {
            $this->addMessage('system', 'An error occurred: '.$e->getMessage());
        } finally {
            $this->isGenerating = false;
            $this->currentAction = '';
        }
    }

    /**
     * Handle generate bio action.
     */
    protected function handleGenerateBio(): void
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            $this->addMessage('system', 'Biolink not found');

            return;
        }

        $description = $biolink->getSeoDescription() ?? 'A professional bio page';

        $aix = app(AixContentService::class);
        $bio = $aix->generateBio($description);

        $this->recordUsage(1);

        $this->addMessage('assistant', "Here's a suggested bio:\n\n{$bio}");
        $this->addMessage('system', 'Would you like to apply this to your page?');
    }

    /**
     * Handle improve SEO action.
     */
    protected function handleImproveSeo(): void
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            $this->addMessage('system', 'Biolink not found');

            return;
        }

        $aix = app(AixContentService::class);
        $title = $aix->generateSeoTitle($biolink);
        $description = $aix->generateSeoDescription($biolink);

        $this->recordUsage(2);

        $this->addMessage('assistant', "Here are suggested SEO improvements:\n\nTitle: {$title}\n\nDescription: {$description}");
    }

    /**
     * Handle suggest improvements action.
     */
    protected function handleSuggestImprovements(): void
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            $this->addMessage('system', 'Biolink not found');

            return;
        }

        $aix = app(AixContentService::class);
        $suggestions = $aix->suggestImprovements($biolink);

        $this->recordUsage(1);

        $message = "Here are some suggestions to improve your bio page:\n\n";

        foreach ($suggestions as $i => $suggestion) {
            $priority = $suggestion['priority'] ?? 'medium';
            $category = $suggestion['category'] ?? 'general';
            $text = $suggestion['suggestion'] ?? '';

            $message .= ($i + 1).". [{$category}] {$text}\n";
        }

        $this->addMessage('assistant', $message);
    }

    /**
     * Handle generate social description action.
     */
    protected function handleGenerateSocial(): void
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            $this->addMessage('system', 'Biolink not found');

            return;
        }

        $aix = app(AixContentService::class);
        $social = $aix->generateSocialDescription($biolink);

        $this->recordUsage(1);

        $this->addMessage('assistant', "Here's a suggested social media description:\n\n{$social}");
    }

    /**
     * Send a chat message.
     */
    public function sendMessage(): void
    {
        if (empty(trim($this->chatMessage))) {
            return;
        }

        if (! $this->canUseAi()) {
            $this->addMessage('system', 'You have no AI credits remaining. Please upgrade your plan to continue using AI features.');

            return;
        }

        $message = $this->chatMessage;
        $this->chatMessage = '';

        $this->addMessage('user', $message);

        // For now, just echo back a simple response
        // This could be expanded to use the AI service for conversational assistance
        $this->addMessage('assistant', 'AI chat is coming soon. For now, try the quick actions above.');
    }

    /**
     * Add a message to chat history.
     */
    protected function addMessage(string $role, string $content): void
    {
        $this->chatHistory[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Clear chat history.
     */
    public function clearChat(): void
    {
        $this->chatHistory = [];
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('webpage::admin.ai-assistant');
    }
}
