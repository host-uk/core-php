<?php

declare(strict_types=1);

namespace Core\Mod\Web\Console\Commands;

use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Services\DomainVerificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled command to verify pending BioHost custom domains.
 *
 * This command checks all pending domains against their DNS records
 * and updates their verification status. Run hourly to catch DNS
 * propagation for newly added domains.
 *
 * Usage:
 *   php artisan bio:verify-domains           # Verify all pending
 *   php artisan bio:verify-domains --all     # Re-verify all domains
 *   php artisan bio:verify-domains --domain=example.com  # Verify specific
 */
class VerifyBioDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bio:verify-domains
        {--all : Verify all domains, not just pending ones}
        {--domain= : Verify a specific domain by host}
        {--dry-run : Show what would happen without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify pending BioHost custom domains via DNS';

    protected DomainVerificationService $verificationService;

    public function __construct(DomainVerificationService $verificationService)
    {
        parent::__construct();
        $this->verificationService = $verificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $specificDomain = $this->option('domain');

        if ($isDryRun) {
            $this->info('Dry run mode - no changes will be made.');
        }

        // Build query
        $query = Domain::query();

        if ($specificDomain) {
            $query->where('host', $specificDomain);
        } elseif (! $this->option('all')) {
            // Only pending domains by default
            $query->where('verification_status', Domain::VERIFICATION_PENDING);
        }

        $domains = $query->get();

        if ($domains->isEmpty()) {
            $this->info('No domains to verify.');

            return self::SUCCESS;
        }

        $this->info("Checking {$domains->count()} domain(s)...");
        $this->newLine();

        $verified = 0;
        $failed = 0;
        $unchanged = 0;

        foreach ($domains as $domain) {
            $this->line("Checking: {$domain->host}");

            if ($isDryRun) {
                $dnsStatus = $this->verificationService->checkDnsResolution($domain->host);
                $this->outputDnsStatus($dnsStatus);
                $unchanged++;

                continue;
            }

            $previousStatus = $domain->verification_status;
            $success = $this->verificationService->verify($domain);

            if ($success) {
                $this->info('  -> Verified successfully');
                $verified++;

                Log::info('[BioLink Domain] Domain verified by scheduled task', [
                    'domain' => $domain->host,
                    'workspace_id' => $domain->workspace_id,
                ]);
            } elseif ($previousStatus === Domain::VERIFICATION_VERIFIED) {
                // Already verified, stayed verified
                $unchanged++;
            } else {
                $this->warn('  -> Verification failed');
                $failed++;
            }
        }

        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Verified', $verified],
                ['Failed', $failed],
                ['Unchanged', $unchanged],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Output DNS status details.
     */
    protected function outputDnsStatus(array $status): void
    {
        $this->line('  Resolves: '.($status['resolves'] ? 'Yes' : 'No'));

        if ($status['cname']) {
            $this->line("  CNAME: {$status['cname']}");
        }

        if (! empty($status['ip_addresses'])) {
            $this->line('  IPs: '.implode(', ', $status['ip_addresses']));
        }

        if (! empty($status['txt_records'])) {
            foreach ($status['txt_records'] as $txt) {
                $this->line("  TXT: {$txt}");
            }
        }
    }
}
