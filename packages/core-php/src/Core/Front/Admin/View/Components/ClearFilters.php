<?php

declare(strict_types=1);

namespace Core\Front\Admin\View\Components;

use Illuminate\View\Component;

class ClearFilters extends Component
{
    public string $clearStatements;

    public function __construct(
        public array $fields = [],
        public string $label = 'Clear Filters',
    ) {
        $this->clearStatements = collect($this->fields)
            ->map(fn ($field) => "\$set('{$field}', '')")
            ->implode('; ');
    }

    public function render()
    {
        return view('admin::components.clear-filters');
    }
}
