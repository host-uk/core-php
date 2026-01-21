<?php

declare(strict_types=1);

namespace Core\Front\Admin\View\Components;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\View\Component;

class ManagerTable extends Component
{
    public array $processedColumns;

    public function __construct(
        public array $columns = [],
        public array $rows = [],
        public ?Paginator $pagination = null,
        public string $empty = 'No items found.',
        public string $emptyIcon = 'inbox',
    ) {
        $this->processedColumns = $this->processColumns();
    }

    protected function processColumns(): array
    {
        return array_map(fn ($column) => [
            'label' => is_array($column) ? $column['label'] : $column,
            'align' => is_array($column) ? ($column['align'] ?? 'left') : 'left',
            'alignClass' => $this->alignClass(
                is_array($column) ? ($column['align'] ?? 'left') : 'left'
            ),
        ], $this->columns);
    }

    public function cellAlignClass(int $cellIndex): string
    {
        $columnDef = $this->columns[$cellIndex] ?? [];
        $align = is_array($columnDef) ? ($columnDef['align'] ?? 'left') : 'left';

        return $this->alignClass($align);
    }

    protected function alignClass(string $align): string
    {
        return match ($align) {
            'center' => 'text-center',
            'right' => 'text-right',
            default => 'text-left',
        };
    }

    public function render()
    {
        return view('admin::components.manager-table');
    }
}
