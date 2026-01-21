<?php

declare(strict_types=1);

namespace Core\Front\Admin\View\Components;

use Illuminate\View\Component;

class Search extends Component
{
    public string $wireModel;

    public function __construct(
        public string $placeholder = 'Search...',
        public ?string $model = null,
    ) {
        $this->wireModel = $this->model ? "wire:model.live.debounce.300ms=\"{$this->model}\"" : '';
    }

    public function render()
    {
        return view('admin::components.search');
    }
}
