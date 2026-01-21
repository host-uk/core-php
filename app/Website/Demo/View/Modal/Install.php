<?php

declare(strict_types=1);

namespace Website\Demo\View\Modal;

use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Installation Wizard Component.
 *
 * Guides users through initial application setup.
 */
class Install extends Component
{
    public int $step = 1;

    public array $checks = [];

    // Step 2: Admin user details
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $createDemo = true;

    public ?string $error = null;

    public ?string $success = null;

    public function mount(): void
    {
        $this->runChecks();

        // If already installed, redirect
        if ($this->isInstalled()) {
            $this->redirect('/', navigate: true);
        }
    }

    public function runChecks(): void
    {
        $this->checks = [
            'php' => [
                'label' => 'PHP Version',
                'description' => 'PHP 8.2 or higher required',
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'value' => PHP_VERSION,
            ],
            'database' => [
                'label' => 'Database Connection',
                'description' => 'MySQL/MariaDB/SQLite connection',
                'passed' => $this->checkDatabase(),
                'value' => $this->getDatabaseInfo(),
            ],
            'migrations' => [
                'label' => 'Database Tables',
                'description' => 'Core tables created',
                'passed' => $this->checkMigrations(),
                'value' => $this->checkMigrations() ? 'Ready' : 'Pending',
            ],
            'storage' => [
                'label' => 'Storage Writable',
                'description' => 'storage/ directory is writable',
                'passed' => is_writable(storage_path()),
                'value' => is_writable(storage_path()) ? 'Writable' : 'Not writable',
            ],
            'cache' => [
                'label' => 'Cache Writable',
                'description' => 'bootstrap/cache/ is writable',
                'passed' => is_writable(base_path('bootstrap/cache')),
                'value' => is_writable(base_path('bootstrap/cache')) ? 'Writable' : 'Not writable',
            ],
        ];
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getDatabaseInfo(): string
    {
        try {
            $driver = config('database.default');
            $database = config("database.connections.{$driver}.database");

            return ucfirst($driver).': '.$database;
        } catch (\Exception $e) {
            return 'Not configured';
        }
    }

    protected function checkMigrations(): bool
    {
        try {
            return Schema::hasTable('users');
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function isInstalled(): bool
    {
        try {
            return Schema::hasTable('users') && DB::table('users')->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function runMigrations(): void
    {
        $this->error = null;

        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->runChecks();
            $this->success = 'Migrations completed successfully!';
        } catch (\Exception $e) {
            $this->error = 'Migration failed: '.$e->getMessage();
        }
    }

    public function nextStep(): void
    {
        $this->error = null;
        $this->success = null;

        if ($this->step === 1) {
            // Validate all checks pass
            $allPassed = collect($this->checks)->every(fn ($check) => $check['passed']);

            if (! $allPassed) {
                $this->error = 'Please resolve all requirements before continuing.';

                return;
            }
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        $this->error = null;
        $this->success = null;
        $this->step = max(1, $this->step - 1);
    }

    public function createUser(): void
    {
        $this->error = null;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Create admin user
            $user = User::create([
                'name' => $this->name,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'email_verified_at' => now(),
            ]);

            // Create demo user if requested
            if ($this->createDemo) {
                User::create([
                    'name' => 'Demo User',
                    'email' => 'demo@example.com',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]);
            }

            // Log in as the new admin
            Auth::login($user);

            $this->step = 3;
        } catch (\Exception $e) {
            $this->error = 'Failed to create user: '.$e->getMessage();
        }
    }

    public function finish(): void
    {
        $this->redirect('/hub', navigate: true);
    }

    #[Layout('demo::layouts.app', ['title' => 'Install'])]
    public function render()
    {
        return view('demo::web.install');
    }
}
