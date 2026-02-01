<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Helpers\SystemLogs;
use Core\Tests\TestCase;
use InvalidArgumentException;

class SystemLogsTest extends TestCase
{
    protected SystemLogs $systemLogs;

    protected function setUp(): void
    {
        parent::setUp();
        $this->systemLogs = new SystemLogs;
    }

    public function test_get_file_path_returns_valid_path_for_simple_filename(): void
    {
        $path = $this->systemLogs->getFilePath('laravel.log');

        $this->assertStringEndsWith('laravel.log', $path);
        $this->assertStringContainsString('logs', $path);
    }

    public function test_get_file_path_allows_alphanumeric_filenames(): void
    {
        $path = $this->systemLogs->getFilePath('laravel-2024-01-15.log');

        $this->assertStringEndsWith('laravel-2024-01-15.log', $path);
    }

    public function test_get_file_path_allows_underscores(): void
    {
        $path = $this->systemLogs->getFilePath('debug_output.log');

        $this->assertStringEndsWith('debug_output.log', $path);
    }

    public function test_get_file_path_rejects_directory_traversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log filename');

        $this->systemLogs->getFilePath('../../../etc/passwd');
    }

    public function test_get_file_path_rejects_double_dot_sequences(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log filename');

        $this->systemLogs->getFilePath('..%2F..%2Fetc%2Fpasswd');
    }

    public function test_get_file_path_rejects_absolute_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log filename');

        $this->systemLogs->getFilePath('/etc/passwd');
    }

    public function test_get_file_path_rejects_backslash_paths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log filename');

        $this->systemLogs->getFilePath('..\\..\\windows\\system32\\config\\sam');
    }

    public function test_get_file_path_rejects_null_bytes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log filename');

        $this->systemLogs->getFilePath("laravel.log\x00.txt");
    }

    public function test_get_file_path_rejects_spaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log filename');

        $this->systemLogs->getFilePath('laravel log.log');
    }

    public function test_base_path_for_logs_ends_with_directory_separator(): void
    {
        $basePath = $this->systemLogs->basePathForLogs();

        $this->assertStringEndsWith(DIRECTORY_SEPARATOR, $basePath);
    }
}
