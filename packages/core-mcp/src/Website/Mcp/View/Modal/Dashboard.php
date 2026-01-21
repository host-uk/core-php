<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * MCP Dashboard
 *
 * Overview of MCP servers, tools, and usage stats.
 */
#[Layout('mcp::layouts.app')]
class Dashboard extends Component
{
    use WithPagination;

    public string $serverFilter = '';

    public string $statusFilter = '';

    public function getStatsProperty(): array
    {
        // Override in your application to provide real stats
        return [
            'total_servers' => 0,
            'total_tools' => 0,
            'total_calls' => 0,
            'success_rate' => 0,
        ];
    }

    public function render()
    {
        return view('mcp::web.dashboard');
    }
}
