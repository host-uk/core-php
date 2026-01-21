<?php

declare(strict_types=1);

namespace Core\Mail;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Email Shield Service
 *
 * Validates email addresses and blocks disposable email domains.
 * Tracks validation statistics for monitoring.
 */
class EmailShield
{
    /**
     * Cache key for disposable domains list.
     */
    protected const CACHE_KEY = 'email_shield:disposable_domains';

    /**
     * Cache key prefix for MX record lookups.
     */
    protected const MX_CACHE_KEY_PREFIX = 'email_shield:mx:';

    /**
     * Cache duration in seconds (24 hours).
     */
    protected const CACHE_DURATION = 86400;

    /**
     * Cache duration for MX lookups in seconds (1 hour).
     */
    protected const MX_CACHE_DURATION = 3600;

    /**
     * URL for disposable domains list updates.
     */
    protected const DISPOSABLE_DOMAINS_URL = 'https://raw.githubusercontent.com/disposable-email-domains/disposable-email-domains/master/disposable_email_blocklist.conf';

    /**
     * Path to disposable domains list file.
     */
    protected const DOMAINS_FILE = 'email-shield/disposable-domains.txt';

    /**
     * Set of disposable domains (loaded from cache or file).
     *
     * @var array<string, bool>
     */
    protected array $disposableDomains = [];

    /**
     * Create a new EmailShield instance.
     */
    public function __construct()
    {
        $this->loadDisposableDomains();
    }

    /**
     * Validate an email address.
     *
     * @param  string  $email  The email address to validate
     */
    public function validate(string $email): EmailValidationResult
    {
        // Basic format validation
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->recordStats(isValid: false, isDisposable: false);

            return EmailValidationResult::invalid('Invalid email format');
        }

        // Extract domain
        $domain = $this->extractDomain($email);
        if (! $domain) {
            $this->recordStats(isValid: false, isDisposable: false);

            return EmailValidationResult::invalid('Could not extract domain from email');
        }

        // Check if disposable
        if ($this->isDisposable($domain)) {
            $this->recordStats(isValid: false, isDisposable: true);

            return EmailValidationResult::disposable($domain);
        }

        // Email is valid
        $this->recordStats(isValid: true, isDisposable: false);

        return EmailValidationResult::valid($domain);
    }

    /**
     * Check if a domain is disposable.
     *
     * @param  string  $domain  The domain to check
     */
    public function isDisposable(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        return isset($this->disposableDomains[$domain]);
    }

    /**
     * Record validation statistics.
     *
     * @param  bool  $isValid  Whether the email was valid
     * @param  bool  $isDisposable  Whether the email was disposable
     */
    public function recordStats(bool $isValid, bool $isDisposable = false): void
    {
        if ($isDisposable) {
            EmailShieldStat::incrementDisposable();
        } elseif ($isValid) {
            EmailShieldStat::incrementValid();
        } else {
            EmailShieldStat::incrementInvalid();
        }
    }

    /**
     * Get validation statistics for a date range.
     *
     * @param  Carbon  $from  Start date
     * @param  Carbon  $to  End date
     * @return array{total_valid: int, total_invalid: int, total_disposable: int, total_checked: int}
     */
    public function getStats(Carbon $from, Carbon $to): array
    {
        return EmailShieldStat::getStatsForRange($from, $to);
    }

    /**
     * Extract domain from email address.
     */
    protected function extractDomain(string $email): ?string
    {
        $parts = explode('@', $email);

        if (count($parts) !== 2) {
            return null;
        }

        return strtolower(trim($parts[1]));
    }

    /**
     * Load disposable domains from cache or file.
     */
    protected function loadDisposableDomains(): void
    {
        $this->disposableDomains = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_DURATION,
            function () {
                return $this->loadDisposableDomainsFromFile();
            }
        );
    }

    /**
     * Load disposable domains from file.
     *
     * @return array<string, bool>
     */
    protected function loadDisposableDomainsFromFile(): array
    {
        $filePath = storage_path('app/'.self::DOMAINS_FILE);

        if (! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);

        $domains = [];
        foreach ($lines as $line) {
            $domain = strtolower(trim($line));

            // Skip empty lines and comments
            if ($domain === '' || str_starts_with($domain, '#')) {
                continue;
            }

            $domains[$domain] = true;
        }

        return $domains;
    }

    /**
     * Refresh the disposable domains cache.
     */
    public function refreshCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loadDisposableDomains();
    }

    /**
     * Get the count of loaded disposable domains.
     */
    public function getDisposableDomainsCount(): int
    {
        return count($this->disposableDomains);
    }

    /**
     * Check if a domain has valid MX records (with caching).
     *
     * @param  string  $domain  The domain to check
     */
    public function hasMxRecords(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        $cacheKey = self::MX_CACHE_KEY_PREFIX.$domain;

        return Cache::remember(
            $cacheKey,
            self::MX_CACHE_DURATION,
            function () use ($domain): bool {
                return $this->performMxLookup($domain);
            }
        );
    }

    /**
     * Perform actual MX record lookup.
     */
    protected function performMxLookup(string $domain): bool
    {
        $mxRecords = [];

        // Suppress warnings as getmxrr returns false on failure
        $result = @getmxrr($domain, $mxRecords);

        return $result && count($mxRecords) > 0;
    }

    /**
     * Clear the MX cache for a specific domain.
     */
    public function clearMxCache(string $domain): void
    {
        $domain = strtolower(trim($domain));
        Cache::forget(self::MX_CACHE_KEY_PREFIX.$domain);
    }

    /**
     * Update the disposable domains list from remote source.
     *
     * Downloads the latest list from the configured URL and updates
     * both the local file and cache.
     *
     * @param  string|null  $url  Optional custom URL for the domains list
     * @return bool True if update was successful
     */
    public function updateDisposableDomainsList(?string $url = null): bool
    {
        $url = $url ?? self::DISPOSABLE_DOMAINS_URL;

        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::warning('EmailShield: Failed to fetch disposable domains list', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return false;
            }

            $content = $response->body();
            $lines = explode("\n", $content);

            // Validate we got a reasonable list
            $validDomains = array_filter($lines, function ($line) {
                $domain = strtolower(trim($line));

                return $domain !== '' && ! str_starts_with($domain, '#');
            });

            if (count($validDomains) < 100) {
                Log::warning('EmailShield: Disposable domains list seems too small', [
                    'count' => count($validDomains),
                ]);

                return false;
            }

            // Save to file
            $filePath = storage_path('app/'.self::DOMAINS_FILE);
            $directory = dirname($filePath);

            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($filePath, $content);

            // Refresh cache
            $this->refreshCache();

            Log::info('EmailShield: Updated disposable domains list', [
                'count' => count($validDomains),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('EmailShield: Exception updating disposable domains list', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the timestamp when the disposable domains file was last modified.
     */
    public function getDisposableDomainsLastUpdated(): ?Carbon
    {
        $filePath = storage_path('app/'.self::DOMAINS_FILE);

        if (! File::exists($filePath)) {
            return null;
        }

        return Carbon::createFromTimestamp(File::lastModified($filePath));
    }
}
