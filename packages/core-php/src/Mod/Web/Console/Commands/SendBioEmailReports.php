<?php

declare(strict_types=1);

namespace Core\Mod\Web\Console\Commands;

use Core\Mod\Web\Mail\BioReport;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send scheduled email reports for bio.
 *
 * Queries biolinks with email reporting enabled and sends formatted
 * analytics summaries to configured recipients.
 *
 * Usage:
 *   php artisan biolink:email-reports --frequency=daily
 *   php artisan biolink:email-reports --frequency=weekly
 *   php artisan biolink:email-reports --frequency=monthly
 */
class SendBioEmailReports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'biolink:email-reports
        {--frequency=daily : Report frequency (daily, weekly, monthly)}
        {--dry-run : Show what would be sent without actually sending}
        {--biolink= : Send report for a specific biolink ID only}';

    /**
     * The console command description.
     */
    protected $description = 'Send scheduled email reports for biolinks';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsService $analyticsService): int
    {
        $frequency = $this->option('frequency');
        $dryRun = $this->option('dry-run');
        $specificBiolinkId = $this->option('biolink');

        if (! in_array($frequency, ['daily', 'weekly', 'monthly'])) {
            $this->error("Invalid frequency: {$frequency}. Use daily, weekly, or monthly.");

            return self::FAILURE;
        }

        $this->info("Sending {$frequency} biolink reports...");

        // Calculate date range based on frequency
        [$startDate, $endDate] = $this->getDateRange($frequency);

        $this->line("Date range: {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}");

        // Query biolinks with email reports enabled for this frequency
        $query = Page::query()
            ->whereNotNull('email_report_settings')
            ->where('is_enabled', true);

        if ($specificBiolinkId) {
            $query->where('id', $specificBiolinkId);
        }

        $biolinks = $query->get()->filter(function ($biolink) use ($frequency) {
            $settings = $biolink->email_report_settings;

            if (! is_array($settings)) {
                return false;
            }

            return ($settings['enabled'] ?? false)
                && ($settings['frequency'] ?? 'weekly') === $frequency
                && ! empty($settings['recipients']);
        });

        if ($biolinks->isEmpty()) {
            $this->info('No biolinks with email reports enabled for this frequency.');

            return self::SUCCESS;
        }

        $this->info("Found {$biolinks->count()} biolink(s) to report on.");

        $sent = 0;
        $failed = 0;

        foreach ($biolinks as $biolink) {
            $settings = $biolink->email_report_settings;
            $recipients = $settings['recipients'] ?? [];

            if (empty($recipients)) {
                continue;
            }

            // Ensure recipients is an array
            if (is_string($recipients)) {
                $recipients = array_map('trim', explode(',', $recipients));
            }

            $this->line("Processing: /{$biolink->url} ({$biolink->id})");

            try {
                // Get analytics data for the period
                $analytics = $this->gatherAnalytics($biolink, $startDate, $endDate, $analyticsService);

                if ($dryRun) {
                    $this->info('  [DRY RUN] Would send to: '.implode(', ', $recipients));
                    $this->line("  Summary: {$analytics['summary']['clicks']} clicks, {$analytics['summary']['unique_clicks']} unique");
                    $sent++;

                    continue;
                }

                // Send the report email
                foreach ($recipients as $recipient) {
                    if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                        $this->warn("  Skipping invalid email: {$recipient}");

                        continue;
                    }

                    Mail::to($recipient)->send(new BioReport(
                        biolink: $biolink,
                        analytics: $analytics,
                        startDate: $startDate,
                        endDate: $endDate,
                        frequency: $frequency
                    ));

                    $this->line("  Sent to: {$recipient}");
                }

                $sent++;
            } catch (\Exception $e) {
                $this->error("  Failed: {$e->getMessage()}");
                Log::error('BioLink email report failed', [
                    'biolink_id' => $biolink->id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Complete: {$sent} sent, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Get the date range for the report frequency.
     */
    protected function getDateRange(string $frequency): array
    {
        $endDate = now()->subDay()->endOfDay(); // Yesterday

        $startDate = match ($frequency) {
            'daily' => $endDate->copy()->startOfDay(),
            'weekly' => $endDate->copy()->subDays(6)->startOfDay(),
            'monthly' => $endDate->copy()->subDays(29)->startOfDay(),
            default => $endDate->copy()->subDays(6)->startOfDay(),
        };

        return [$startDate, $endDate];
    }

    /**
     * Gather analytics data for a bio.
     */
    protected function gatherAnalytics(
        Page $biolink,
        Carbon $startDate,
        Carbon $endDate,
        AnalyticsService $analyticsService
    ): array {
        return [
            'summary' => $analyticsService->getSummary($biolink, $startDate, $endDate),
            'clicks_over_time' => $analyticsService->getClicksOverTime($biolink, $startDate, $endDate),
            'countries' => $analyticsService->getClicksByCountry($biolink, $startDate, $endDate, 5),
            'devices' => $analyticsService->getClicksByDevice($biolink, $startDate, $endDate),
            'browsers' => $analyticsService->getClicksByBrowser($biolink, $startDate, $endDate, 5),
            'referrers' => $analyticsService->getClicksByReferrer($biolink, $startDate, $endDate, 5),
            'utm_sources' => $analyticsService->getClicksByUtmSource($biolink, $startDate, $endDate, 5),
        ];
    }
}
