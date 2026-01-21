<?php

declare(strict_types=1);

namespace Website\Hub\View\Modal\Admin;

use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Core\Mod\Social\Models\Account;
use Core\Mod\Social\Models\Post;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Global search component with ⌘K keyboard shortcut.
 *
 * Searches across biolinks, domains, social accounts, and posts.
 * Accessible from any page via keyboard shortcut or search button.
 */
class GlobalSearch extends Component
{
    public bool $open = false;

    public string $query = '';

    public int $selectedIndex = 0;

    /**
     * Open the search modal.
     */
    #[On('open-global-search')]
    public function openSearch(): void
    {
        $this->open = true;
        $this->query = '';
        $this->selectedIndex = 0;
    }

    /**
     * Close the search modal.
     */
    public function closeSearch(): void
    {
        $this->open = false;
        $this->query = '';
        $this->selectedIndex = 0;
    }

    /**
     * Handle query changes - reset selection index.
     */
    public function updatedQuery(): void
    {
        $this->selectedIndex = 0;
    }

    /**
     * Navigate up in results.
     */
    public function navigateUp(): void
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    /**
     * Navigate down in results.
     */
    public function navigateDown(): void
    {
        $allResults = $this->flatResults;
        if ($this->selectedIndex < count($allResults) - 1) {
            $this->selectedIndex++;
        }
    }

    /**
     * Select the current result.
     */
    public function selectCurrent(): void
    {
        $allResults = $this->flatResults;
        if (isset($allResults[$this->selectedIndex])) {
            $result = $allResults[$this->selectedIndex];
            $this->navigateTo($result);
        }
    }

    /**
     * Navigate to a specific result.
     */
    public function navigateTo(array $result): void
    {
        $this->closeSearch();

        $this->dispatch('navigate-to-url', url: $result['url']);
    }

    /**
     * Get search results grouped by type.
     */
    #[Computed]
    public function results(): array
    {
        if (strlen($this->query) < 2) {
            return [];
        }

        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $workspace = $user->defaultHostWorkspace();

        return [
            'biolinks' => $this->searchBiolinks($user->id),
            'domains' => $this->searchDomains($user->id),
            'accounts' => $workspace ? $this->searchAccounts($workspace->id) : [],
            'posts' => $workspace ? $this->searchPosts($workspace->id) : [],
        ];
    }

    /**
     * Get flattened results for keyboard navigation.
     */
    #[Computed]
    public function flatResults(): array
    {
        $flat = [];

        foreach ($this->results as $type => $items) {
            foreach ($items as $item) {
                $flat[] = $item;
            }
        }

        return $flat;
    }

    /**
     * Search bio.
     */
    protected function searchBiolinks(int $userId): array
    {
        $escapedQuery = $this->escapeLikeWildcards($this->query);

        return Page::where('user_id', $userId)
            ->where(function ($query) use ($escapedQuery) {
                $query->where('url', 'like', "%{$escapedQuery}%")
                    ->orWhereRaw("JSON_EXTRACT(settings, '$.title') LIKE ?", ["%{$escapedQuery}%"]);
            })
            ->limit(5)
            ->get()
            ->map(fn ($biolink) => [
                'type' => 'biolink',
                'icon' => 'link',
                'title' => $biolink->settings['title'] ?? $biolink->url,
                'subtitle' => "/{$biolink->url}",
                'url' => route('bio.edit', $biolink),
            ])
            ->toArray();
    }

    /**
     * Search domains.
     */
    protected function searchDomains(int $userId): array
    {
        $escapedQuery = $this->escapeLikeWildcards($this->query);

        return Domain::where('user_id', $userId)
            ->where('host', 'like', "%{$escapedQuery}%")
            ->limit(5)
            ->get()
            ->map(fn ($domain) => [
                'type' => 'domain',
                'icon' => 'globe-alt',
                'title' => $domain->host,
                'subtitle' => $domain->is_verified ? 'Verified' : 'Pending verification',
                'url' => route('domains.index'),
            ])
            ->toArray();
    }

    /**
     * Search social accounts.
     */
    protected function searchAccounts(int $workspaceId): array
    {
        $escapedQuery = $this->escapeLikeWildcards($this->query);

        return Account::where('workspace_id', $workspaceId)
            ->where(function ($query) use ($escapedQuery) {
                $query->where('name', 'like', "%{$escapedQuery}%")
                    ->orWhere('username', 'like', "%{$escapedQuery}%");
            })
            ->limit(5)
            ->get()
            ->map(fn ($account) => [
                'type' => 'account',
                'icon' => 'user-circle',
                'title' => $account->name,
                'subtitle' => "@{$account->username} · {$account->provider}",
                'url' => route('social.accounts.index'),
            ])
            ->toArray();
    }

    /**
     * Search social posts.
     */
    protected function searchPosts(int $workspaceId): array
    {
        $escapedQuery = $this->escapeLikeWildcards($this->query);

        return Post::where('workspace_id', $workspaceId)
            ->whereRaw("JSON_EXTRACT(content, '$.default.body') LIKE ?", ["%{$escapedQuery}%"])
            ->limit(5)
            ->get()
            ->map(fn ($post) => [
                'type' => 'post',
                'icon' => 'document-text',
                'title' => str($post->content['default']['body'] ?? '')->limit(50)->toString(),
                'subtitle' => $post->scheduled_at?->format('d M Y H:i') ?? 'Draft',
                'url' => route('social.posts.edit', $post),
            ])
            ->toArray();
    }

    public function render()
    {
        return view('hub::admin.global-search');
    }

    /**
     * Escape LIKE wildcard characters to prevent unintended matches.
     */
    protected function escapeLikeWildcards(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }
}
