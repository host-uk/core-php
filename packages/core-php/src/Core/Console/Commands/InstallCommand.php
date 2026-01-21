<?php

declare(strict_types=1);

namespace Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Core PHP Framework Installation Command.
 *
 * Helps new users set up the framework with sensible defaults.
 * Run: php artisan core:install
 */
class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'core:install
                            {--force : Overwrite existing configuration}
                            {--no-interaction : Run without prompts using defaults}';

    /**
     * The console command description.
     */
    protected $description = 'Install and configure Core PHP Framework';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('  '.__('core::core.installer.title'));
        $this->info('  '.str_repeat('=', strlen(__('core::core.installer.title'))));
        $this->info('');

        // Step 1: Environment file
        if (! $this->setupEnvironment()) {
            return self::FAILURE;
        }

        // Step 2: Application settings
        $this->configureApplication();

        // Step 3: Database
        if ($this->option('no-interaction') || $this->confirm(__('core::core.installer.prompts.run_migrations'), true)) {
            $this->runMigrations();
        }

        // Step 4: Generate app key if needed
        $this->generateAppKey();

        // Step 5: Create storage link
        $this->createStorageLink();

        // Done!
        $this->info('');
        $this->info('  '.__('core::core.installer.complete'));
        $this->info('');
        $this->info('  '.__('core::core.installer.next_steps').':');
        $this->info('    1. Run: valet link core');
        $this->info('    2. Visit: http://core.test');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Set up the .env file.
     */
    protected function setupEnvironment(): bool
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (File::exists($envPath) && ! $this->option('force')) {
            $this->info('  [✓] '.__('core::core.installer.env_exists'));

            return true;
        }

        if (! File::exists($envExamplePath)) {
            $this->error('  [✗] '.__('core::core.installer.env_missing'));

            return false;
        }

        File::copy($envExamplePath, $envPath);
        $this->info('  [✓] '.__('core::core.installer.env_created'));

        return true;
    }

    /**
     * Configure application settings.
     */
    protected function configureApplication(): void
    {
        if ($this->option('no-interaction')) {
            $this->info('  [✓] '.__('core::core.installer.default_config'));

            return;
        }

        // App name
        $appName = $this->ask(__('core::core.installer.prompts.app_name'), __('core::core.brand.name'));
        $this->updateEnv('APP_BRAND_NAME', $appName);

        // Domain
        $domain = $this->ask(__('core::core.installer.prompts.domain'), 'core.test');
        $this->updateEnv('APP_DOMAIN', $domain);
        $this->updateEnv('APP_URL', "http://{$domain}");

        // Database
        $this->info('');
        $this->info('  Database Configuration:');
        $dbConnection = $this->choice(__('core::core.installer.prompts.db_driver'), ['sqlite', 'mysql', 'pgsql'], 0);

        if ($dbConnection === 'sqlite') {
            $this->updateEnv('DB_CONNECTION', 'sqlite');
            $this->updateEnv('DB_DATABASE', 'database/database.sqlite');

            // Create SQLite file
            $sqlitePath = database_path('database.sqlite');
            if (! File::exists($sqlitePath)) {
                File::put($sqlitePath, '');
                $this->info('  [✓] Created SQLite database');
            }
        } else {
            $this->updateEnv('DB_CONNECTION', $dbConnection);
            $dbHost = $this->ask(__('core::core.installer.prompts.db_host'), '127.0.0.1');
            $dbPort = $this->ask(__('core::core.installer.prompts.db_port'), $dbConnection === 'mysql' ? '3306' : '5432');
            $dbName = $this->ask(__('core::core.installer.prompts.db_name'), 'core');
            $dbUser = $this->ask(__('core::core.installer.prompts.db_user'), 'root');
            $dbPass = $this->secret(__('core::core.installer.prompts.db_password'));

            $this->updateEnv('DB_HOST', $dbHost);
            $this->updateEnv('DB_PORT', $dbPort);
            $this->updateEnv('DB_DATABASE', $dbName);
            $this->updateEnv('DB_USERNAME', $dbUser);
            $this->updateEnv('DB_PASSWORD', $dbPass ?? '');
        }

        $this->info('  [✓] '.__('core::core.installer.config_saved'));
    }

    /**
     * Run database migrations.
     */
    protected function runMigrations(): void
    {
        $this->info('');
        $this->info('  Running migrations...');

        $this->call('migrate', ['--force' => true]);

        $this->info('  [✓] '.__('core::core.installer.migrations_complete'));
    }

    /**
     * Generate application key if not set.
     */
    protected function generateAppKey(): void
    {
        $key = config('app.key');

        if (empty($key) || $key === 'base64:') {
            $this->call('key:generate');
            $this->info('  [✓] '.__('core::core.installer.key_generated'));
        } else {
            $this->info('  [✓] '.__('core::core.installer.key_exists'));
        }
    }

    /**
     * Create storage symlink.
     */
    protected function createStorageLink(): void
    {
        $publicStorage = public_path('storage');

        if (File::exists($publicStorage)) {
            $this->info('  [✓] '.__('core::core.installer.storage_link_exists'));

            return;
        }

        $this->call('storage:link');
        $this->info('  [✓] '.__('core::core.installer.storage_link_created'));
    }

    /**
     * Update a value in the .env file.
     */
    protected function updateEnv(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);

        // Quote value if it contains spaces
        if (str_contains($value, ' ')) {
            $value = "\"{$value}\"";
        }

        // Check if key exists
        if (preg_match("/^{$key}=/m", $content)) {
            // Update existing key
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        } else {
            // Add new key
            $content .= "\n{$key}={$value}";
        }

        File::put($envPath, $content);
    }
}
