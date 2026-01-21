<?php

declare(strict_types=1);

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\NotificationHandler;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\NotificationService;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Livewire component for managing biolink notification handlers.
 *
 * Provides CRUD functionality for notification handlers with support
 * for webhooks, email, Slack, Discord, and Telegram notifications.
 */
class NotificationHandlerManager extends Component
{
    // Current biolink
    public ?int $biolinkId = null;

    // Create/Edit modal state
    public bool $showModal = false;

    public bool $isEditing = false;

    public ?int $editingHandlerId = null;

    // Form fields
    public string $name = '';

    public string $type = 'webhook';

    public array $events = ['click'];

    public bool $isEnabled = true;

    // Type-specific settings
    public string $webhookUrl = '';

    public string $webhookSecret = '';

    public string $emailRecipients = '';

    public string $emailSubjectPrefix = 'BioHost';

    public string $slackWebhookUrl = '';

    public string $discordWebhookUrl = '';

    public string $telegramBotToken = '';

    public string $telegramChatId = '';

    // Test notification state
    public bool $testInProgress = false;

    public ?string $testResult = null;

    /**
     * Mount the component.
     */
    public function mount(?int $biolinkId = null): void
    {
        $this->biolinkId = $biolinkId;
    }

    /**
     * Get the bio.
     */
    #[Computed]
    public function biolink(): ?Page
    {
        if (! $this->biolinkId) {
            return null;
        }

        return Page::ownedByCurrentWorkspace()
            ->find($this->biolinkId);
    }

    /**
     * Get all notification handlers for this bio.
     */
    #[Computed]
    public function handlers()
    {
        if (! $this->biolink) {
            return collect();
        }

        return $this->biolink->notificationHandlers()
            ->withTrashed()
            ->latest()
            ->get();
    }

    /**
     * Get available handler types.
     */
    #[Computed]
    public function handlerTypes(): array
    {
        return NotificationHandler::getTypes();
    }

    /**
     * Get available event types.
     */
    #[Computed]
    public function eventTypes(): array
    {
        return NotificationHandler::getEvents();
    }

    /**
     * Open the create handler modal.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->editingHandlerId = null;
        $this->showModal = true;
    }

    /**
     * Open the edit handler modal.
     */
    public function openEditModal(int $handlerId): void
    {
        $handler = NotificationHandler::where('biolink_id', $this->biolinkId)
            ->findOrFail($handlerId);

        $this->isEditing = true;
        $this->editingHandlerId = $handlerId;

        $this->name = $handler->name;
        $this->type = $handler->type;
        $this->events = $handler->events ?? ['click'];
        $this->isEnabled = $handler->is_enabled;

        // Load type-specific settings
        switch ($handler->type) {
            case 'webhook':
                $this->webhookUrl = $handler->getSetting('url', '');
                $this->webhookSecret = $handler->getSetting('secret', '');
                break;

            case 'email':
                $recipients = $handler->getSetting('recipients', []);
                $this->emailRecipients = is_array($recipients) ? implode(', ', $recipients) : $recipients;
                $this->emailSubjectPrefix = $handler->getSetting('subject_prefix', 'BioHost');
                break;

            case 'slack':
                $this->slackWebhookUrl = $handler->getSetting('webhook_url', '');
                break;

            case 'discord':
                $this->discordWebhookUrl = $handler->getSetting('webhook_url', '');
                break;

            case 'telegram':
                $this->telegramBotToken = $handler->getSetting('bot_token', '');
                $this->telegramChatId = $handler->getSetting('chat_id', '');
                break;
        }

        $this->showModal = true;
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset the form fields.
     */
    protected function resetForm(): void
    {
        $this->name = '';
        $this->type = 'webhook';
        $this->events = ['click'];
        $this->isEnabled = true;

        $this->webhookUrl = '';
        $this->webhookSecret = '';
        $this->emailRecipients = '';
        $this->emailSubjectPrefix = 'BioHost';
        $this->slackWebhookUrl = '';
        $this->discordWebhookUrl = '';
        $this->telegramBotToken = '';
        $this->telegramChatId = '';

        $this->testResult = null;
        $this->resetValidation();
    }

    /**
     * Build settings array based on handler type.
     */
    protected function buildSettings(): array
    {
        return match ($this->type) {
            'webhook' => [
                'url' => $this->webhookUrl,
                'secret' => $this->webhookSecret ?: null,
            ],
            'email' => [
                'recipients' => array_map('trim', array_filter(explode(',', $this->emailRecipients))),
                'subject_prefix' => $this->emailSubjectPrefix,
            ],
            'slack' => [
                'webhook_url' => $this->slackWebhookUrl,
            ],
            'discord' => [
                'webhook_url' => $this->discordWebhookUrl,
            ],
            'telegram' => [
                'bot_token' => $this->telegramBotToken,
                'chat_id' => $this->telegramChatId,
            ],
            default => [],
        };
    }

    /**
     * Validate the form.
     */
    protected function validateForm(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:128'],
            'type' => ['required', 'in:webhook,email,slack,discord,telegram'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['in:click,block_click,form_submit,payment'],
        ];

        // Type-specific validation
        match ($this->type) {
            'webhook' => $rules['webhookUrl'] = ['required', 'url', 'max:2048'],
            'email' => $rules['emailRecipients'] = ['required', 'string'],
            'slack' => $rules['slackWebhookUrl'] = ['required', 'url', 'max:2048'],
            'discord' => $rules['discordWebhookUrl'] = ['required', 'url', 'max:2048'],
            'telegram' => array_merge($rules, [
                'telegramBotToken' => ['required', 'string', 'max:256'],
                'telegramChatId' => ['required', 'string', 'max:64'],
            ]),
            default => null,
        };

        $this->validate($rules, [
            'name.required' => 'Please enter a name for this handler.',
            'events.required' => 'Please select at least one event to trigger on.',
            'events.min' => 'Please select at least one event to trigger on.',
            'webhookUrl.required' => 'Please enter a webhook URL.',
            'webhookUrl.url' => 'Please enter a valid URL.',
            'emailRecipients.required' => 'Please enter at least one email address.',
            'slackWebhookUrl.required' => 'Please enter a Slack webhook URL.',
            'slackWebhookUrl.url' => 'Please enter a valid URL.',
            'discordWebhookUrl.required' => 'Please enter a Discord webhook URL.',
            'discordWebhookUrl.url' => 'Please enter a valid URL.',
            'telegramBotToken.required' => 'Please enter the Telegram bot token.',
            'telegramChatId.required' => 'Please enter the Telegram chat ID.',
        ]);

        // Additional email validation
        if ($this->type === 'email') {
            $emails = array_map('trim', explode(',', $this->emailRecipients));
            foreach ($emails as $email) {
                if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addError('emailRecipients', "'{$email}' is not a valid email address.");

                    return;
                }
            }
        }
    }

    /**
     * Save the handler (create or update).
     */
    public function save(): void
    {
        if (! $this->biolink) {
            $this->dispatch('notify', message: 'Biolink not found.', type: 'error');

            return;
        }

        $this->validateForm();

        $data = [
            'biolink_id' => $this->biolinkId,
            'workspace_id' => $this->biolink->workspace_id,
            'name' => $this->name,
            'type' => $this->type,
            'settings' => $this->buildSettings(),
            'events' => $this->events,
            'is_enabled' => $this->isEnabled,
        ];

        if ($this->isEditing && $this->editingHandlerId) {
            $handler = NotificationHandler::where('biolink_id', $this->biolinkId)
                ->findOrFail($this->editingHandlerId);

            $handler->update($data);
            $this->dispatch('notify', message: 'Notification handler updated.', type: 'success');
        } else {
            NotificationHandler::create($data);
            $this->dispatch('notify', message: 'Notification handler created.', type: 'success');
        }

        $this->closeModal();
    }

    /**
     * Toggle handler enabled status.
     */
    public function toggleEnabled(int $handlerId): void
    {
        $handler = NotificationHandler::where('biolink_id', $this->biolinkId)
            ->findOrFail($handlerId);

        $handler->update(['is_enabled' => ! $handler->is_enabled]);

        $status = $handler->is_enabled ? 'enabled' : 'disabled';
        $this->dispatch('notify', message: "Handler {$status}.", type: 'success');
    }

    /**
     * Reset failures and re-enable a handler.
     */
    public function resetHandler(int $handlerId): void
    {
        $handler = NotificationHandler::where('biolink_id', $this->biolinkId)
            ->findOrFail($handlerId);

        $handler->resetFailures();

        $this->dispatch('notify', message: 'Handler reset and re-enabled.', type: 'success');
    }

    /**
     * Delete a handler.
     */
    public function deleteHandler(int $handlerId): void
    {
        $handler = NotificationHandler::where('biolink_id', $this->biolinkId)
            ->findOrFail($handlerId);

        $handler->delete();

        $this->dispatch('notify', message: 'Handler deleted.', type: 'success');
    }

    /**
     * Send a test notification.
     */
    public function sendTest(int $handlerId): void
    {
        $handler = NotificationHandler::where('biolink_id', $this->biolinkId)
            ->findOrFail($handlerId);

        $this->testInProgress = true;
        $this->testResult = null;

        $service = app(NotificationService::class);
        $success = $service->sendTest($handler);

        $this->testInProgress = false;

        if ($success) {
            $this->testResult = 'success';
            $this->dispatch('notify', message: 'Test notification sent successfully.', type: 'success');
        } else {
            $this->testResult = 'failed';
            $this->dispatch('notify', message: 'Test notification failed. Check the handler settings.', type: 'error');
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('webpage::admin.notification-handler-manager')
            ->layout('hub::admin.layouts.app', ['title' => 'Notification Handlers']);
    }
}
