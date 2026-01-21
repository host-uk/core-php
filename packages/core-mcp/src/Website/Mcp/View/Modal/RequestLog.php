<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * MCP Request Log - view and replay API requests.
 */
#[Layout('mcp::layouts.app')]
class RequestLog extends Component
{
    use WithPagination;

    public string $serverFilter = '';

    public string $statusFilter = '';

    public ?int $selectedRequestId = null;

    public function updatedServerFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function selectRequest(int $id): void
    {
        $this->selectedRequestId = $id;
    }

    public function closeDetail(): void
    {
        $this->selectedRequestId = null;
    }

    public function render()
    {
        // Override to provide real request data
        return view('mcp::web.request-log', [
            'requests' => collect(),
            'servers' => collect(),
        ]);
    }
}
