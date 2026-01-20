<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModCommand extends Command
{
    protected $signature = 'make:mod {name : The name of the module (e.g., Commerce)}';

    protected $description = 'Create a new module with Boot.php and route files';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $slug = Str::kebab($name);
        $upperSlug = Str::upper(Str::snake($name));

        $modulePath = app_path("Mod/{$name}");

        if ($files->isDirectory($modulePath)) {
            $this->error("Module [{$name}] already exists!");

            return self::FAILURE;
        }

        // Create directory structure
        $files->makeDirectory($modulePath, 0755, true);
        $files->makeDirectory("{$modulePath}/Routes", 0755, true);
        $files->makeDirectory("{$modulePath}/Views", 0755, true);

        // Copy and process stubs
        $stubPath = $this->getStubPath();

        $replacements = [
            '{{ name }}' => $name,
            '{{ slug }}' => $slug,
            '{{ upper_slug }}' => $upperSlug,
        ];

        // Boot.php
        $bootContent = $this->processStub(
            $files->get("{$stubPath}/Boot.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/Boot.php", $bootContent);

        // Routes
        $webRoutesContent = $this->processStub(
            $files->get("{$stubPath}/Routes/web.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/Routes/web.php", $webRoutesContent);

        $adminRoutesContent = $this->processStub(
            $files->get("{$stubPath}/Routes/admin.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/Routes/admin.php", $adminRoutesContent);

        $apiRoutesContent = $this->processStub(
            $files->get("{$stubPath}/Routes/api.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/Routes/api.php", $apiRoutesContent);

        // Config
        $configContent = $this->processStub(
            $files->get("{$stubPath}/config.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/config.php", $configContent);

        $this->info("Module [{$name}] created successfully.");
        $this->line("  <comment>app/Mod/{$name}/Boot.php</comment>");
        $this->line("  <comment>app/Mod/{$name}/Routes/web.php</comment>");
        $this->line("  <comment>app/Mod/{$name}/Routes/admin.php</comment>");
        $this->line("  <comment>app/Mod/{$name}/Routes/api.php</comment>");
        $this->line("  <comment>app/Mod/{$name}/config.php</comment>");

        return self::SUCCESS;
    }

    protected function getStubPath(): string
    {
        $customPath = base_path('stubs/core/Mod/Example');

        if (is_dir($customPath)) {
            return $customPath;
        }

        return __DIR__.'/../../../stubs/Mod/Example';
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
