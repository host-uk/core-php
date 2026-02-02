<?php

declare(strict_types=1);

namespace Core\Mod\Trees\Jobs;

use Core\Mod\Trees\Models\TreePlanting;
use Core\Mod\Trees\Models\TreePlantingStats;
use Core\Mod\Trees\Models\TreeReserve;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Job to mark a tree planting as confirmed and decrement the reserve.
 *
 * This job:
 * 1. Checks if reserve has trees available
 * 2. Marks the planting as confirmed
 * 3. Decrements the tree reserve
 * 4. Updates TreePlantingStats
 * 5. Triggers low reserve notification if needed
 *
 * If reserve is depleted, the planting remains in its current state.
 */
class PlantTreeWithTFTF implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    public function __construct(
        public TreePlanting $planting
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if already confirmed or planted
        if ($this->planting->isConfirmed() || $this->planting->isPlanted()) {
            Log::info('Tree planting already confirmed', [
                'planting_id' => $this->planting->id,
                'status' => $this->planting->status,
            ]);

            return;
        }

        DB::transaction(function () {
            $trees = $this->planting->trees;

            // Lock the reserve for update and check if there are enough trees
            $reserve = TreeReserve::query()->lockForUpdate()->first();

            if (! $reserve || $reserve->trees < $trees) {
                Log::warning('Tree reserve depleted, planting queued', [
                    'planting_id' => $this->planting->id,
                    'trees' => $trees,
                    'reserve' => $reserve ? $reserve->trees : 0,
                ]);

                // If not already queued, mark as queued
                if (! $this->planting->isQueued()) {
                    $this->planting->update(['status' => TreePlanting::STATUS_QUEUED]);
                }

                return;
            }

            // Decrement the reserve
            $reserve->decrement('trees', $trees);

            // Mark as confirmed
            $this->planting->update(['status' => TreePlanting::STATUS_CONFIRMED]);

            // Update stats
            TreePlantingStats::incrementOrCreate(
                $this->planting->provider ?? 'unknown',
                $this->planting->model,
                $trees,
                $this->planting->source === TreePlanting::SOURCE_AGENT_REFERRAL ? 1 : 0
            );

            Log::info('Tree planting confirmed', [
                'planting_id' => $this->planting->id,
                'trees' => $trees,
                'source' => $this->planting->source,
                'provider' => $this->planting->provider,
                'reserve_remaining' => $reserve->trees,
            ]);
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('PlantTreeWithTFTF job failed', [
            'planting_id' => $this->planting->id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
