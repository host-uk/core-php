<?php

declare(strict_types=1);

namespace Core\Tests;

use Core\CoreServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            CoreServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('core.module_paths', [
            $this->getFixturePath('Mod'),
        ]);
    }

    protected function getFixturePath(string $path = ''): string
    {
        return __DIR__.'/Fixtures'.($path ? "/{$path}" : '');
    }
}
