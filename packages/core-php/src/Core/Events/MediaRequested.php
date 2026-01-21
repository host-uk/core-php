<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when media processing is requested.
 *
 * Modules listen to this event to provide media handling capabilities
 * such as image processing, video transcoding, CDN integration, etc.
 *
 * Allows lazy loading of heavy media processing dependencies.
 */
class MediaRequested extends LifecycleEvent
{
    protected array $processorRequests = [];

    /**
     * Register a media processor.
     */
    public function processor(string $type, string $class): void
    {
        $this->processorRequests[$type] = $class;
    }

    /**
     * Get all registered processors.
     */
    public function processorRequests(): array
    {
        return $this->processorRequests;
    }
}
