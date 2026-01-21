<?php

declare(strict_types=1);

namespace Core\Front\Admin\View\Components;

use Illuminate\View\Component;

class ActivityFeed extends Component
{
    public function __construct(
        public string $title = 'Recent Activity',
        public ?string $action = null,
        public string $actionLabel = 'View All',
        public array $items = [],
        public string $empty = 'No recent activity',
        public string $emptyIcon = 'clock',
    ) {}

    public function itemIcon(array $item): string
    {
        return $item['icon'] ?? 'circle';
    }

    public function itemColor(array $item): string
    {
        return $item['color'] ?? 'gray';
    }

    public function render()
    {
        return view('admin::components.activity-feed');
    }
}
