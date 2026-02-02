<?php

namespace Core\Config\Tests\Feature;

use Core\Config\ConfigExporter;
use Core\Config\ConfigService;
use Core\Config\ConfigVersioning;
use Core\Config\Models\ConfigProfile;
use Core\Config\Models\ConfigVersion;
use Core\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Mockery;

class ConfigVersioningPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../../Migrations');
    }

    public function test_pruning_performance()
    {
        // 1. Setup dependencies
        $mockExporter = Mockery::mock(ConfigExporter::class);
        $mockExporter->shouldReceive('exportJson')
            ->andReturn(json_encode(['values' => []]));

        $configService = app(ConfigService::class);

        $versioning = new ConfigVersioning($configService, $mockExporter);

        // set max versions to a small number to force pruning
        Config::set('core.config.max_versions', 50);
        // Re-instantiate to pick up config or use setter if available
        $versioning->setMaxVersions(50);

        // 2. Create a profile
        $profile = ConfigProfile::ensureSystem();

        // 3. Seed many versions (e.g., 500)
        // We insert them directly to avoid triggering pruning during setup
        $now = now();
        $versions = [];
        for ($i = 0; $i < 500; $i++) {
            $versions[] = [
                'profile_id' => $profile->id,
                'workspace_id' => null,
                'label' => "Version $i",
                'snapshot' => '{}',
                'author' => 'tester',
                'created_at' => $now->subMinutes(500 - $i), // Oldest first
            ];
        }
        ConfigVersion::insert($versions);

        $initialCount = ConfigVersion::count();
        $this->assertEquals(500, $initialCount);

        // 4. Measure pruning time
        $start = microtime(true);

        // createVersion triggers pruneOldVersions
        $versioning->createVersion(null, 'New Version');

        $duration = microtime(true) - $start;

        // 5. Verify
        $finalCount = ConfigVersion::count();
        $this->assertEquals(50, $finalCount, 'Should have pruned down to 50 versions');

        echo "\nPerformance: Pruning took ".number_format($duration * 1000, 2)."ms\n";
    }
}
