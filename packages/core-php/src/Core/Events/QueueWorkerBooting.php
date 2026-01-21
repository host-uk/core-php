<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when a queue worker is starting up.
 *
 * Modules listen to this event to register job classes or
 * perform queue-specific initialisation.
 *
 * Only fired in queue worker context, not web requests.
 */
class QueueWorkerBooting extends LifecycleEvent
{
    protected array $jobRequests = [];

    /**
     * Register a job class.
     */
    public function job(string $class): void
    {
        $this->jobRequests[] = $class;
    }

    /**
     * Get all registered job classes.
     */
    public function jobRequests(): array
    {
        return $this->jobRequests;
    }
}
