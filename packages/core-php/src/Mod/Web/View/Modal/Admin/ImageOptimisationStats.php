<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Media\Image\ImageOptimization;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin Image Optimisation Statistics component.
 *
 * Shows platform-wide image optimisation statistics.
 * Displays total optimisations, space saved, compression ratios.
 */
#[Layout('hub::admin.layouts.app')]
class ImageOptimisationStats extends Component
{
    use WithPagination;

    public ?int $workspaceFilter = null; // null = all workspaces

    public string $dateRange = '30'; // 7, 30, 90 days

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
        // Admin-only check would go here
        // if (!Auth::user()->isAdmin()) abort(403);
    }

    /**
     * Get overall statistics.
     */
    #[Computed]
    public function overallStats(): array
    {
        return ImageOptimization::getWorkspaceStats(
            $this->workspaceFilter ? Workspace::find($this->workspaceFilter) : null
        );
    }

    /**
     * Get workspace-specific statistics.
     */
    #[Computed]
    public function workspaceStats(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $query = ImageOptimization::query()
            ->select(
                'workspace_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(original_size) as total_original'),
                DB::raw('SUM(optimized_size) as total_optimized'),
                DB::raw('AVG(percentage_saved) as avg_percentage')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('workspace_id')
            ->orderByDesc('count')
            ->limit(10);

        if ($this->workspaceFilter) {
            $query->where('workspace_id', $this->workspaceFilter);
        }

        return $query->get()->map(function ($stat) {
            $workspace = Workspace::find($stat->workspace_id);
            $totalSaved = $stat->total_original - $stat->total_optimized;

            return [
                'workspace_id' => $stat->workspace_id,
                'workspace_name' => $workspace?->name ?? 'Unknown',
                'count' => $stat->count,
                'total_saved' => $totalSaved,
                'total_saved_human' => ImageOptimization::formatBytesStatic($totalSaved),
                'avg_percentage' => round($stat->avg_percentage, 1),
            ];
        })->toArray();
    }

    /**
     * Get optimisation trend over time.
     */
    #[Computed]
    public function optimisationTrend(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $query = ImageOptimization::query()
            ->where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date');

        if ($this->workspaceFilter) {
            $query->where('workspace_id', $this->workspaceFilter);
        }

        $data = $query->pluck('count', 'date')->toArray();

        return [
            'labels' => array_keys($data),
            'values' => array_values($data),
        ];
    }

    /**
     * Get space saved trend over time.
     */
    #[Computed]
    public function spaceSavedTrend(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $query = ImageOptimization::query()
            ->where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(original_size - optimized_size) as saved')
            )
            ->groupBy('date')
            ->orderBy('date');

        if ($this->workspaceFilter) {
            $query->where('workspace_id', $this->workspaceFilter);
        }

        $data = $query->pluck('saved', 'date')->toArray();

        // Convert bytes to MB for chart
        $values = array_map(fn ($bytes) => round($bytes / (1024 * 1024), 2), $data);

        return [
            'labels' => array_keys($data),
            'values' => array_values($values),
        ];
    }

    /**
     * Get driver usage statistics.
     */
    #[Computed]
    public function driverStats(): array
    {
        $days = (int) $this->dateRange;
        $startDate = now()->subDays($days);

        $query = ImageOptimization::query()
            ->where('created_at', '>=', $startDate)
            ->select('driver', DB::raw('COUNT(*) as count'))
            ->groupBy('driver')
            ->orderByDesc('count');

        if ($this->workspaceFilter) {
            $query->where('workspace_id', $this->workspaceFilter);
        }

        return $query->get()->map(function ($stat) {
            return [
                'driver' => $stat->driver ?? 'unknown',
                'count' => $stat->count,
            ];
        })->toArray();
    }

    /**
     * Get recent optimisations.
     */
    #[Computed]
    public function recentOptimisations()
    {
        $query = ImageOptimization::query()
            ->with('workspace')
            ->orderByDesc('created_at');

        if ($this->workspaceFilter) {
            $query->where('workspace_id', $this->workspaceFilter);
        }

        return $query->paginate(20);
    }

    /**
     * Get available workspaces for filter.
     */
    #[Computed]
    public function workspaces(): array
    {
        return Workspace::orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Set workspace filter.
     */
    public function setWorkspaceFilter(?int $workspaceId): void
    {
        $this->workspaceFilter = $workspaceId;
        $this->resetPage();
    }

    /**
     * Set date range.
     */
    public function setDateRange(string $range): void
    {
        $this->dateRange = $range;
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->workspaceFilter = null;
        $this->dateRange = '30';
        $this->resetPage();
    }

    public function render()
    {
        return view('webpage::admin.image-optimisation-stats')
            ->title('Image Optimisation Statistics');
    }
}
