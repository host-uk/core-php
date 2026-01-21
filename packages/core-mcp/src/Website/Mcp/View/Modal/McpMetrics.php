<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * MCP Metrics Dashboard
 *
 * Displays analytics and metrics for MCP tool usage.
 */
#[Layout('mcp::layouts.app')]
class McpMetrics extends Component
{
    public int $days = 7;

    public string $activeTab = 'overview';

    public function setDays(int $days): void
    {
        $this->days = $days;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getOverviewProperty(): array
    {
        // Override in your application
        return [
            'total_calls' => 0,
            'success_rate' => 0,
            'avg_duration' => 0,
        ];
    }

    public function render()
    {
        return view('mcp::web.mcp-metrics');
    }
}
