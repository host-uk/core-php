<?php

namespace Core\Mod\Web\Console\Commands;

use Illuminate\Console\Command;
use Core\Mod\Web\Database\Seeders\BioDemoSeeder;
use Core\Mod\Web\Models\Page;

class SeedBioDemos extends Command
{
    protected $signature = 'bio:seed-demos
                            {--fresh : Delete existing demo pages first}
                            {--list : List all demo pages}';

    protected $description = 'Create Vi-themed demo pages showcasing all Bio block types';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listDemoPages();
        }

        if ($this->option('fresh')) {
            $count = Page::where('url', 'like', 'demo-%')->count();
            if ($count > 0) {
                $this->info("Removing {$count} existing demo pages...");
                Page::where('url', 'like', 'demo-%')->delete();
            }
        }

        $this->info('Seeding Vi demo pages...');

        $seeder = new BioDemoSeeder;
        $seeder->setCommand($this);
        $seeder->run();

        $this->newLine();
        $this->info('Demo pages created successfully!');

        return $this->listDemoPages();
    }

    protected function listDemoPages(): int
    {
        $demos = Page::where('url', 'like', 'demo-%')
            ->orderBy('url')
            ->get(['url', 'settings']);

        if ($demos->isEmpty()) {
            $this->warn('No demo pages found. Run without --list to create them.');

            return Command::SUCCESS;
        }

        // Bio pages are served via the Mod/LtHn module
        $domain = app()->environment('production') ? 'https://lt.hn' : 'https://lthn.test';
        $domain = rtrim($domain, '/');

        $this->table(
            ['Page', 'URL'],
            $demos->map(fn ($b) => [
                $b->settings['page_title'] ?? $b->url,
                $domain.'/'.$b->url,
            ])
        );

        $this->newLine();
        $this->info("Total: {$demos->count()} demo pages");
        $this->line("Index: {$domain}/demo-index");

        return Command::SUCCESS;
    }
}
