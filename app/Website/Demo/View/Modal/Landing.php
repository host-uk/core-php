<?php

declare(strict_types=1);

namespace Website\Demo\View\Modal;

use Livewire\Component;

/**
 * Demo Landing Page.
 *
 * Simple landing page for the demo website.
 */
class Landing extends Component
{
    public function render()
    {
        return view('demo::web.landing')
            ->layout('demo::layouts.app', [
                'title' => config('app.name', 'Core PHP'),
            ]);
    }
}
