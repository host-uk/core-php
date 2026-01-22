<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when search functionality is requested.
 *
 * Modules listen to this event to register searchable models
 * or search providers.
 *
 * Allows lazy loading of search indexing dependencies.
 */
class SearchRequested extends LifecycleEvent
{
    protected array $searchableRequests = [];

    /**
     * Register a searchable model.
     */
    public function searchable(string $model): void
    {
        $this->searchableRequests[] = $model;
    }

    /**
     * Get all registered searchable models.
     */
    public function searchableRequests(): array
    {
        return $this->searchableRequests;
    }
}
