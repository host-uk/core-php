<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Module\ModuleScanner;
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
    }

    public function test_extract_listens_returns_empty_for_class_without_property(): void
    {
        // Create a temporary class without $listens
        $class = new class {
            public function handle(): void {}
        };

        $result = $this->scanner->extractListens($class::class);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_namespace_map_can_be_configured(): void
    {
        $scanner = (new ModuleScanner)->setNamespaceMap([
            'CustomMod' => 'App\\CustomMod',
        ]);

        $this->assertInstanceOf(ModuleScanner::class, $scanner);
    }
}
