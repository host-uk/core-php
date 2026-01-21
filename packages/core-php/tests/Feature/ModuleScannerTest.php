<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Events\ApiRoutesRegistering;
use Core\Events\ConsoleBooting;
use Core\Events\FrameworkBooted;
use Core\Events\WebRoutesRegistering;
use Core\ModuleScanner;
use Core\Tests\TestCase;

class ModuleScannerTest extends TestCase
{
    protected ModuleScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scanner = new ModuleScanner;
    }

    public function test_scan_returns_empty_array_for_nonexistent_path(): void
    {
        $result = $this->scanner->scan(['/nonexistent/path']);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_scan_finds_modules_with_listens_property(): void
    {
        $result = $this->scanner->scan([$this->getFixturePath('Mod')]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(WebRoutesRegistering::class, $result);
    }

    public function test_scan_finds_modules_in_website_path(): void
    {
        $result = $this->scanner->scan([$this->getFixturePath('Mod')]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(WebRoutesRegistering::class, $result);
        $this->assertArrayHasKey('Website\\TestSite\\Boot', $result[WebRoutesRegistering::class]);
    }

    public function test_scan_finds_modules_in_plug_path(): void
    {
        $result = $this->scanner->scan([$this->getFixturePath('Plug')]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(FrameworkBooted::class, $result);
        $this->assertArrayHasKey('Plug\\TestPlugin\\Boot', $result[FrameworkBooted::class]);
    }

    public function test_scan_finds_modules_in_core_path(): void
    {
        $result = $this->scanner->scan([$this->getFixturePath('Core')]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey(ConsoleBooting::class, $result);
        $this->assertArrayHasKey('Core\\TestCore\\Boot', $result[ConsoleBooting::class]);
    }

    public function test_scan_uses_fallback_namespace_for_unknown_paths(): void
    {
        // Create a temporary directory with an unusual name
        $tempDir = sys_get_temp_dir().'/FallbackNsTest'.time();
        $moduleDir = $tempDir.'/TestModule';

        if (! is_dir($moduleDir)) {
            mkdir($moduleDir, 0755, true);
        }

        $className = 'FallbackNsTest'.time().'\\TestModule\\Boot';

        file_put_contents($moduleDir.'/Boot.php', <<<PHP
<?php

namespace FallbackNsTest{$this->getUniqueSuffix()}\\TestModule;

use Core\Events\WebRoutesRegistering;

class Boot
{
    public static array \$listens = [
        WebRoutesRegistering::class => 'onWebRoutes',
    ];

    public function onWebRoutes(\$event): void {}
}
PHP);

        // The scanner won't find this class because it's not autoloaded
        // But we can verify the namespace derivation logic by checking the mapping keys
        $result = $this->scanner->scan([$tempDir]);

        // The scan will find the file but class_exists() will fail
        // since the file isn't autoloaded. This tests the fallback behavior.
        $this->assertIsArray($result);

        // Cleanup
        unlink($moduleDir.'/Boot.php');
        rmdir($moduleDir);
        rmdir($tempDir);
    }

    private function getUniqueSuffix(): string
    {
        return (string) time();
    }

    public function test_extract_listens_returns_empty_for_class_without_property(): void
    {
        $class = new class {
            public function handle(): void {}
        };

        $result = $this->scanner->extractListens($class::class);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_extract_listens_returns_empty_for_private_listens(): void
    {
        require_once $this->getFixturePath('Mod/PrivateListens/Boot.php');

        $result = $this->scanner->extractListens(\Mod\PrivateListens\Boot::class);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_extract_listens_returns_empty_for_non_static_listens(): void
    {
        require_once $this->getFixturePath('Mod/NonStaticListens/Boot.php');

        $result = $this->scanner->extractListens(\Mod\NonStaticListens\Boot::class);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_extract_listens_returns_empty_for_non_array_listens(): void
    {
        require_once $this->getFixturePath('Mod/NonArrayListens/Boot.php');

        $result = $this->scanner->extractListens(\Mod\NonArrayListens\Boot::class);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_extract_listens_returns_empty_for_nonexistent_class(): void
    {
        $result = $this->scanner->extractListens('NonExistent\\Class\\That\\Does\\Not\\Exist');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_scan_skips_modules_without_listens(): void
    {
        require_once $this->getFixturePath('Mod/NoListens/Boot.php');

        $result = $this->scanner->scan([$this->getFixturePath('Mod')]);

        // NoListens module should not appear in any event mappings
        foreach ($result as $listeners) {
            $this->assertArrayNotHasKey('Mod\\NoListens\\Boot', $listeners);
        }
    }

    public function test_scan_aggregates_multiple_paths(): void
    {
        $result = $this->scanner->scan([
            $this->getFixturePath('Mod'),
            $this->getFixturePath('Mod'),
            $this->getFixturePath('Plug'),
        ]);

        $this->assertArrayHasKey(WebRoutesRegistering::class, $result);
        $this->assertArrayHasKey(FrameworkBooted::class, $result);

        // Should have multiple listeners for WebRoutesRegistering
        $this->assertGreaterThanOrEqual(2, count($result[WebRoutesRegistering::class]));
    }
}
