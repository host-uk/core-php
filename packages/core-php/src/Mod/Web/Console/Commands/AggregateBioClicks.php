<?php

namespace Core\Mod\Web\Console\Commands;

use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\ClickStat;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Aggregate raw biolink click data into the stats table.
 *
 * This command processes raw click events from `biolink_clicks` and
 * rolls them up into the `biolink_click_stats` table for efficient
 * dashboard queries.
 *
 * Run hourly via scheduler to keep stats up to date.
 */
class AggregateBioClicks extends Command
{
    protected $signature = 'bio:aggregate-clicks
                            {--hours=24 : Number of hours of data to process}
                            {--prune : Delete raw clicks older than retention period}
                            {--retention=90 : Days to retain raw click data}';

    protected $description = 'Aggregate raw biolink clicks into statistics table';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $startTime = now();

        $this->info("Aggregating biolink clicks for the last {$hours} hours...");

        // Calculate the time window
        $start = now()->subHours($hours)->startOfHour();
        $end = now()->endOfHour();

        // Get distinct biolinks with clicks in the period
        $biolinkIds = Click::whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('biolink_id');

        $this->info("Found {$biolinkIds->count()} biolinks with clicks to process.");

        $bar = $this->output->createProgressBar($biolinkIds->count());
        $bar->start();

        $totalStats = 0;

        foreach ($biolinkIds as $biolinkId) {
            $totalStats += $this->aggregateBiolink($biolinkId, $start, $end);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $elapsed = now()->diffInSeconds($startTime);
        $this->info("Created/updated {$totalStats} stat records in {$elapsed} seconds.");

        // Update denormalised counters on biolinks table
        $this->updateBiolinkCounters($biolinkIds->toArray());

        // Optionally prune old raw data
        if ($this->option('prune')) {
            $this->pruneOldClicks();
        }

        return Command::SUCCESS;
    }

    /**
     * Aggregate clicks for a single bio.
     */
    protected function aggregateBiolink(int $biolinkId, Carbon $start, Carbon $end): int
    {
        $statsCreated = 0;
        $driver = DB::getDriverName();

        // Get the hour expression based on database driver
        $hourExpr = match ($driver) {
            'sqlite' => "strftime('%H', created_at)",
            'pgsql' => 'EXTRACT(HOUR FROM created_at)',
            default => 'HOUR(created_at)', // mysql, mariadb
        };

        // Get raw clicks grouped by dimensions
        $clicks = Click::where('biolink_id', $biolinkId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("
                biolink_id,
                block_id,
                DATE(created_at) as date,
                {$hourExpr} as hour,
                country_code,
                device_type,
                referrer_host,
                utm_source,
                COUNT(*) as clicks,
                SUM(is_unique) as unique_clicks
            ")
            ->groupBy('biolink_id', 'block_id', 'date', 'hour', 'country_code', 'device_type', 'referrer_host', 'utm_source')
            ->get();

        foreach ($clicks as $click) {
            // Upsert the aggregated stat
            ClickStat::updateOrCreate(
                [
                    'biolink_id' => $click->biolink_id,
                    'block_id' => $click->block_id,
                    'date' => $click->date,
                    'hour' => (int) $click->hour,
                    'country_code' => $click->country_code,
                    'device_type' => $click->device_type,
                    'referrer_host' => $click->referrer_host,
                    'utm_source' => $click->utm_source,
                ],
                [
                    'clicks' => $click->clicks,
                    'unique_clicks' => $click->unique_clicks,
                ]
            );
            $statsCreated++;
        }

        // Also create daily totals (hour = null, no dimension breakdown)
        $dailyTotals = Click::where('biolink_id', $biolinkId)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                biolink_id,
                block_id,
                DATE(created_at) as date,
                COUNT(*) as clicks,
                SUM(is_unique) as unique_clicks
            ')
            ->groupBy('biolink_id', 'block_id', 'date')
            ->get();

        foreach ($dailyTotals as $total) {
            ClickStat::updateOrCreate(
                [
                    'biolink_id' => $total->biolink_id,
                    'block_id' => $total->block_id,
                    'date' => $total->date,
                    'hour' => null,
                    'country_code' => null,
                    'device_type' => null,
                    'referrer_host' => null,
                    'utm_source' => null,
                ],
                [
                    'clicks' => $total->clicks,
                    'unique_clicks' => $total->unique_clicks,
                ]
            );
            $statsCreated++;
        }

        return $statsCreated;
    }

    /**
     * Update denormalised click counters on biolinks table.
     */
    protected function updateBiolinkCounters(array $biolinkIds): void
    {
        if (empty($biolinkIds)) {
            return;
        }

        $this->info('Updating biolink click counters...');

        // Update each biolink with total counts from raw clicks
        foreach ($biolinkIds as $biolinkId) {
            $totals = Click::where('biolink_id', $biolinkId)
                ->selectRaw('COUNT(*) as clicks, SUM(is_unique) as unique_clicks, MAX(created_at) as last_click')
                ->first();

            DB::table('biolinks')
                ->where('id', $biolinkId)
                ->update([
                    'clicks' => $totals->clicks ?? 0,
                    'unique_clicks' => $totals->unique_clicks ?? 0,
                    'last_click_at' => $totals->last_click,
                ]);
        }

        $this->info('Counters updated for '.count($biolinkIds).' bio.');
    }

    /**
     * Prune old raw click data.
     */
    protected function pruneOldClicks(): void
    {
        $days = (int) $this->option('retention');
        $cutoff = now()->subDays($days);

        $this->info("Pruning raw clicks older than {$days} days...");

        $deleted = Click::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$deleted} old click records.");
    }
}
