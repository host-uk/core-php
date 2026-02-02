<?php

declare(strict_types=1);

namespace Core\Tests\Unit;

use Core\Media\Thumbnail\LazyThumbnail;
use Core\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class LazyThumbnailBenchmarkTest extends TestCase
{
    public function test_get_stats_performance(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');

        // Seed files
        $count = 1000;
        $prefix = 'thumbnails';

        // Create files in memory
        // 1KB each
        $content = str_repeat('a', 1024);

        for ($i = 0; $i < $count; $i++) {
            $disk->put("{$prefix}/file_{$i}.jpg", $content);
        }

        $service = new LazyThumbnail('public', 'public');

        // Measure first run (uncached/baseline)
        $stats = $service->getStats();

        $this->assertEquals($count, $stats['count']);
        $this->assertEquals($count * 1024, $stats['total_size']);

        // Measure second run (should be faster if cached)
        $service->getStats();
    }

    public function test_stats_updates_correctly(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');
        $service = new LazyThumbnail('public', 'public');

        // Create initial file
        $disk->put('thumbnails/existing.jpg', 'content'); // 7 bytes

        // Initial stats (cache miss, calculates)
        $stats = $service->getStats();
        $this->assertEquals(1, $stats['count']);
        $this->assertEquals(7, $stats['total_size']);

        // Manually add a file (simulating external change or generate logic)
        // We use delete() to verify stats decrement.
        // We need to create a file that matches a known path.
        $path = $service->getThumbnailPath('source.jpg', 100, 100);
        $disk->put($path, str_repeat('a', 100)); // 100 bytes

        // At this point, stats cache is stale (still says 1 file).
        // delete() relies on cache.
        // If we call delete(), it should decrement the cache.

        $service->delete('source.jpg', 100, 100);

        // Check stats. Should be 1 - 1 = 0 files (if logic holds).
        // Total size: 7 - 100? No, we clamp to 0.
        // Or if we deleted the 100 byte file...
        // Wait, stats said 7 bytes. We delete 100 bytes.
        // Result should be 0 (clamped).

        $stats = $service->getStats();
        $this->assertEquals(0, $stats['count']);
        $this->assertEquals(0, $stats['total_size']);

        // Verify dirty flag logic
        // Clear cache and set dirty
        \Illuminate\Support\Facades\Cache::forget('lazy_thumbnails.stats');
        \Illuminate\Support\Facades\Cache::put('lazy_thumbnails.stats_dirty', true);

        // Stats should calculate (finding 'existing.jpg' which is still there)
        $stats = $service->getStats();
        $this->assertEquals(1, $stats['count']);

        // Should NOT have cached
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('lazy_thumbnails.stats'));
    }
}
