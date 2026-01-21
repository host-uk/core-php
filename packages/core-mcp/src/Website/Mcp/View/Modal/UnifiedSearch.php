<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Unified Search Component
 *
 * Single search interface across all system components:
 * MCP tools, API endpoints, patterns, assets, todos, and plans.
 */
#[Layout('mcp::layouts.app')]
class UnifiedSearch extends Component
{
    public string $query = '';

    public array $selectedTypes = [];

    public int $limit = 50;

    public function updatedQuery(): void
    {
        // Debounce handled by wire:model.debounce
    }

    public function toggleType(string $type): void
    {
        if (in_array($type, $this->selectedTypes)) {
            $this->selectedTypes = array_values(array_diff($this->selectedTypes, [$type]));
        } else {
            $this->selectedTypes[] = $type;
        }
    }

    public function clearFilters(): void
    {
        $this->selectedTypes = [];
    }

    public function getResultsProperty(): Collection
    {
        if (strlen($this->query) < 2) {
            return collect();
        }

        // Override in your application to provide real search
        return collect();
    }

    public function getTypesProperty(): array
    {
        return [
            'mcp_tool' => 'MCP Tools',
            'api_endpoint' => 'API Endpoints',
            'pattern' => 'Patterns',
        ];
    }

    public function render()
    {
        return view('mcp::web.unified-search');
    }
}
