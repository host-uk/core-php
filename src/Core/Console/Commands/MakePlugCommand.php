<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakePlugCommand extends Command
{
    protected $signature = 'make:plug {name : The name of the plugin (e.g., Stripe)}';

    protected $description = 'Create a new plugin module for third-party integrations';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $slug = Str::kebab($name);

        $modulePath = app_path("Plug/{$name}");

        if ($files->isDirectory($modulePath)) {
            $this->error("Plugin [{$name}] already exists!");

            return self::FAILURE;
        }

        // Create directory structure
        $files->makeDirectory($modulePath, 0755, true);

        // Copy and process stubs
        $stubPath = $this->getStubPath();

        $replacements = [
            '{{ name }}' => $name,
            '{{ slug }}' => $slug,
        ];

        // Boot.php
        $bootContent = $this->processStub(
            $files->get("{$stubPath}/Boot.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/Boot.php", $bootContent);

        $this->info("Plugin [{$name}] created successfully.");
        $this->line("  <comment>app/Plug/{$name}/Boot.php</comment>");

        return self::SUCCESS;
    }

    protected function getStubPath(): string
    {
        $customPath = base_path('stubs/core/Plug/Example');

        if (is_dir($customPath)) {
            return $customPath;
        }

        return __DIR__.'/../../../stubs/Plug/Example';
    }

    protected function processStub(string $content, array $replacements): string
    {
        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $content
        );
    }
}
