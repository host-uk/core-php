<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeWebsiteCommand extends Command
{
    protected $signature = 'make:website {name : The name of the website module (e.g., Marketing)}';

    protected $description = 'Create a new website module for domain-scoped web properties';

    public function handle(Filesystem $files): int
    {
        $name = $this->argument('name');
        $slug = Str::kebab($name);

        $modulePath = app_path("Website/{$name}");

        if ($files->isDirectory($modulePath)) {
            $this->error("Website [{$name}] already exists!");

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
        ];

        // Boot.php
        $bootContent = $this->processStub(
            $files->get("{$stubPath}/Boot.php.stub"),
            $replacements
        );
        $files->put("{$modulePath}/Boot.php", $bootContent);

        // Create basic web routes file
        $webRoutes = <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| {$name} Website Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('{$slug}::index');
})->name('{$slug}.home');
PHP;

        $files->put("{$modulePath}/Routes/web.php", $webRoutes);

        // Create basic view
        $indexView = <<<BLADE
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}</title>
</head>
<body>
    <h1>{$name}</h1>
</body>
</html>
BLADE;

        $files->put("{$modulePath}/Views/index.blade.php", $indexView);

        $this->info("Website [{$name}] created successfully.");
        $this->line("  <comment>app/Website/{$name}/Boot.php</comment>");
        $this->line("  <comment>app/Website/{$name}/Routes/web.php</comment>");
        $this->line("  <comment>app/Website/{$name}/Views/index.blade.php</comment>");

        return self::SUCCESS;
    }

    protected function getStubPath(): string
    {
        $customPath = base_path('stubs/core/Website/Example');

        if (is_dir($customPath)) {
            return $customPath;
        }

        return __DIR__.'/../../../stubs/Website/Example';
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
