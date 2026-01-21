<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Submission;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Manage biolink form submissions.
 *
 * Lists, filters, and exports submissions from collector blocks.
 */
class SubmissionsManager extends Component
{
    use WithPagination;

    public int $biolinkId;

    // Filters
    public ?string $filterType = null;

    public ?int $filterBlockId = null;

    public ?string $dateFrom = null;

    public ?string $dateTo = null;

    // UI state
    public bool $showDeleteConfirm = false;

    public ?int $deleteSubmissionId = null;

    /**
     * Mount the component.
     *
     * Accepts either numeric ID (from hub routes) or URL slug (from lt.hn routes).
     */
    public function mount(int|string $id): void
    {
        $biolink = $this->resolveBiolink($id);
        $this->biolinkId = $biolink->id;
    }

    /**
     * Resolve biolink from ID or URL slug.
     */
    protected function resolveBiolink(int|string $id): Page
    {
        if (is_numeric($id)) {
            return Page::where('user_id', Auth::id())->findOrFail((int) $id);
        }

        return Page::where('url', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    /**
     * Get the bio.
     */
    #[Computed]
    public function biolink(): ?Page
    {
        return Page::with(['blocks' => fn ($q) => $q->whereIn('type', [
            'email_collector',
            'phone_collector',
            'contact_collector',
        ])])->find($this->biolinkId);
    }

    /**
     * Get collector blocks for filtering.
     */
    #[Computed]
    public function collectorBlocks(): array
    {
        return $this->biolink?->blocks?->pluck('type', 'id')->toArray() ?? [];
    }

    /**
     * Get submissions with filters applied.
     */
    #[Computed]
    public function submissions()
    {
        $query = Submission::where('biolink_id', $this->biolinkId)
            ->with('block')
            ->orderByDesc('created_at');

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterBlockId) {
            $query->where('block_id', $this->filterBlockId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return $query->paginate(25);
    }

    /**
     * Get submission counts by type.
     */
    #[Computed]
    public function countsByType(): array
    {
        return Submission::where('biolink_id', $this->biolinkId)
            ->selectRaw('type, count(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->filterType = null;
        $this->filterBlockId = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->resetPage();
    }

    /**
     * Set type filter.
     */
    public function setTypeFilter(?string $type): void
    {
        $this->filterType = $type;
        $this->resetPage();
    }

    /**
     * Confirm deletion.
     */
    public function confirmDelete(int $id): void
    {
        $this->deleteSubmissionId = $id;
        $this->showDeleteConfirm = true;
    }

    /**
     * Cancel deletion.
     */
    public function cancelDelete(): void
    {
        $this->deleteSubmissionId = null;
        $this->showDeleteConfirm = false;
    }

    /**
     * Delete a submission.
     */
    public function deleteSubmission(): void
    {
        if (! $this->deleteSubmissionId) {
            return;
        }

        $submission = Submission::where('biolink_id', $this->biolinkId)
            ->find($this->deleteSubmissionId);

        if ($submission) {
            $submission->delete();
            $this->dispatch('notify', message: 'Submission deleted', type: 'success');
        }

        $this->cancelDelete();
    }

    /**
     * Export submissions as CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $query = Submission::where('biolink_id', $this->biolinkId)
            ->with('block')
            ->orderByDesc('created_at');

        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        if ($this->filterBlockId) {
            $query->where('block_id', $this->filterBlockId);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $submissions = $query->get();

        $filename = 'submissions_'.$this->biolink->url.'_'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($submissions) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, ['Type', 'Name', 'Email', 'Phone', 'Message', 'Country', 'Submitted At']);

            // Data rows
            foreach ($submissions as $submission) {
                fputcsv($handle, [
                    $submission->type,
                    $submission->name,
                    $submission->email,
                    $submission->phone,
                    $submission->message,
                    $submission->country_code,
                    $submission->created_at->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render()
    {
        return view('webpage::admin.submissions-manager')
            ->layout('client::layouts.app', [
                'title' => 'Submissions: '.($this->biolink->url ?? ''),
                'bioUrl' => $this->biolink->url ?? '',
            ]);
    }
}
