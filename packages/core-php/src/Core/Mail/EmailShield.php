<?php

declare(strict_types=1);

namespace Core\Mail;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

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
     * Cache duration in seconds (24 hours).
     */
    protected const CACHE_DURATION = 86400;

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
}
