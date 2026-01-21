<?php

declare(strict_types=1);

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Domain;
use Illuminate\Support\Facades\Log;

/**
 * Domain verification service for BioHost custom domains.
 *
 * Supports two verification methods:
 * 1. DNS TXT record: User adds TXT record at _biohost-verify.domain.com
 * 2. CNAME verification: Domain points to bio.host.uk.com
 *
 * The TXT record method is preferred as it proves ownership without
 * requiring the domain to be fully pointed at our servers yet.
 */
class DomainVerificationService
{
    /**
     * Expected CNAME target for domain verification.
     */
    public const CNAME_TARGET = 'bio.host.uk.com';

    /**
     * TXT record prefix for verification.
     */
    public const TXT_RECORD_PREFIX = '_biohost-verify';

    /**
     * Verify a domain using all available methods.
     *
     * Tries TXT record first (more reliable), then falls back to CNAME.
     */
    public function verify(Domain $domain): bool
    {
        // Try TXT record verification first
        if ($this->verifyTxtRecord($domain)) {
            $domain->markAsVerified();

            Log::info('[BioLink Domain] Domain verified via TXT record', [
                'domain' => $domain->host,
                'workspace_id' => $domain->workspace_id,
            ]);

            return true;
        }

        // Fall back to CNAME verification
        if ($this->verifyCname($domain)) {
            $domain->markAsVerified();

            Log::info('[BioLink Domain] Domain verified via CNAME', [
                'domain' => $domain->host,
                'workspace_id' => $domain->workspace_id,
            ]);

            return true;
        }

        // Verification failed
        $domain->markVerificationFailed();

        Log::info('[BioLink Domain] Domain verification failed', [
            'domain' => $domain->host,
            'workspace_id' => $domain->workspace_id,
        ]);

        return false;
    }

    /**
     * Verify domain via DNS TXT record.
     *
     * Looks for a TXT record at _biohost-verify.{domain} containing
     * the verification token in format: host-uk-verify={token}
     */
    public function verifyTxtRecord(Domain $domain): bool
    {
        if (empty($domain->verification_token)) {
            return false;
        }

        $subdomain = self::TXT_RECORD_PREFIX . '.' . $domain->host;
        $expectedValue = $domain->getDnsVerificationRecord();

        try {
            $records = $this->getDnsTxtRecords($subdomain);

            foreach ($records as $record) {
                // Clean up the record value (remove quotes, trim whitespace)
                $value = trim(trim($record), '"');

                if ($value === $expectedValue) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[BioLink Domain] TXT record lookup failed', [
                'domain' => $domain->host,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Verify domain via CNAME record pointing to our target.
     */
    public function verifyCname(Domain $domain): bool
    {
        try {
            $cnameTarget = $this->getDnsCnameRecord($domain->host);

            if ($cnameTarget === null) {
                return false;
            }

            // Normalise comparison (remove trailing dots)
            $cnameTarget = rtrim($cnameTarget, '.');
            $expectedTarget = rtrim(self::CNAME_TARGET, '.');

            return strcasecmp($cnameTarget, $expectedTarget) === 0;
        } catch (\Throwable $e) {
            Log::warning('[BioLink Domain] CNAME lookup failed', [
                'domain' => $domain->host,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if domain resolves to our servers via A record.
     *
     * This is a supplementary check - not used for verification,
     * but useful for status display to users.
     */
    public function checkDnsResolution(string $host): array
    {
        $result = [
            'resolves' => false,
            'ip_addresses' => [],
            'cname' => null,
            'txt_records' => [],
        ];

        try {
            // Check for CNAME
            $cname = $this->getDnsCnameRecord($host);
            if ($cname) {
                $result['cname'] = rtrim($cname, '.');
            }

            // Check for A records
            $aRecords = dns_get_record($host, DNS_A);
            if ($aRecords) {
                foreach ($aRecords as $record) {
                    $result['ip_addresses'][] = $record['ip'] ?? null;
                }
                $result['resolves'] = true;
            }

            // Check for TXT verification record
            $txtHost = self::TXT_RECORD_PREFIX . '.' . $host;
            $result['txt_records'] = $this->getDnsTxtRecords($txtHost);
        } catch (\Throwable $e) {
            Log::debug('[BioLink Domain] DNS resolution check failed', [
                'domain' => $host,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Get the DNS instructions for a domain.
     */
    public function getDnsInstructions(Domain $domain): array
    {
        return [
            'cname' => [
                'type' => 'CNAME',
                'host' => $domain->host,
                'target' => self::CNAME_TARGET,
                'description' => 'Point your domain to ' . self::CNAME_TARGET,
            ],
            'txt' => [
                'type' => 'TXT',
                'host' => self::TXT_RECORD_PREFIX . '.' . $domain->host,
                'value' => $domain->getDnsVerificationRecord(),
                'description' => 'Add a TXT record to verify domain ownership',
            ],
        ];
    }

    /**
     * Validate a domain name format.
     */
    public function validateDomainFormat(string $host): bool
    {
        // Remove protocol if present
        $host = preg_replace('~^https?://~', '', $host);
        $host = rtrim($host, '/');

        // Basic domain validation
        if (empty($host) || strlen($host) > 253) {
            return false;
        }

        // Check for valid domain format
        if (! preg_match('/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i', $host)) {
            return false;
        }

        return true;
    }

    /**
     * Normalise a domain host (lowercase, no protocol, no trailing slash).
     */
    public function normaliseHost(string $host): string
    {
        $host = strtolower($host);
        $host = preg_replace('~^https?://~', '', $host);
        $host = rtrim($host, '/');

        return $host;
    }

    /**
     * Check if a domain is reserved or blacklisted.
     */
    public function isDomainReserved(string $host): bool
    {
        $reservedDomains = [
            'host.uk.com',
            'bio.host.uk.com',
            'link.host.uk.com',
            'social.host.uk.com',
            'analytics.host.uk.com',
            'trust.host.uk.com',
            'notify.host.uk.com',
            'lnktr.fyi',
        ];

        $host = $this->normaliseHost($host);

        foreach ($reservedDomains as $reserved) {
            if ($host === $reserved || str_ends_with($host, '.' . $reserved)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get TXT records for a domain.
     */
    protected function getDnsTxtRecords(string $host): array
    {
        $records = @dns_get_record($host, DNS_TXT);

        if (! $records) {
            return [];
        }

        return array_map(fn ($record) => $record['txt'] ?? '', $records);
    }

    /**
     * Get CNAME record for a domain.
     */
    protected function getDnsCnameRecord(string $host): ?string
    {
        $records = @dns_get_record($host, DNS_CNAME);

        if (! $records || empty($records[0]['target'])) {
            return null;
        }

        return $records[0]['target'];
    }
}
