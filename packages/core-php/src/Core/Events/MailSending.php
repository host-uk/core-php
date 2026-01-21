<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when mail functionality is needed.
 *
 * Modules listen to this event to register mail templates,
 * custom mailers, or mail-related services.
 *
 * Allows lazy loading of mail dependencies until email
 * actually needs to be sent.
 */
class MailSending extends LifecycleEvent
{
    protected array $mailableRequests = [];

    /**
     * Register a mailable class.
     */
    public function mailable(string $class): void
    {
        $this->mailableRequests[] = $class;
    }

    /**
     * Get all registered mailable classes.
     */
    public function mailableRequests(): array
    {
        return $this->mailableRequests;
    }
}
