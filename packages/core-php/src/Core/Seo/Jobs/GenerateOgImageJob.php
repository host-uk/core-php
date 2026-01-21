<?php

declare(strict_types=1);

namespace Core\Seo\Jobs;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\DynamicOgImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Generate OG Image Job
 *
 * Queue-based generation of Open Graph images for bio.
 * Regenerates images when biolink settings change.
 */
class GenerateOgImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $biolinkId,
        public string $template = 'default',
        public bool $force = false
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(DynamicOgImageService $ogService): void
    {
        $biolink = Page::find($this->biolinkId);

        if (! $biolink) {
            Log::warning("OG image generation skipped: biolink {$this->biolinkId} not found");

            return;
        }

        // Skip if disabled globally
        if (! config('bio.og_images.enabled', true)) {
            return;
        }

        // Skip if image exists and is not stale (unless forced)
        if (! $this->force && $ogService->exists($biolink) && ! $ogService->isStale($biolink)) {
            return;
        }

        try {
            $url = $ogService->generate($biolink, $this->template);

            Log::info("OG image generated for biolink {$biolink->id}", [
                'url' => $url,
                'template' => $this->template,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to generate OG image for biolink {$biolink->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['og-image', "biolink:{$this->biolinkId}"];
    }
}
