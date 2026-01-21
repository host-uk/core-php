<?php

declare(strict_types=1);

namespace Core\Bouncer;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Manages IP blocklist with Redis caching.
 *
 * Blocklist is populated from:
 * - Honeypot critical hits (/admin probing)
 * - Manual entries
 *
 * Uses a Bloom filter-style approach: cache the blocklist as a set
 * for O(1) lookups, rebuild periodically from database.
 */
class BlocklistService
{
    protected const CACHE_KEY = 'bouncer:blocklist';
    protected const CACHE_TTL = 300; // 5 minutes

    /**
     * Check if IP is blocked.
     */
    public function isBlocked(string $ip): bool
    {
        $blocklist = $this->getBlocklist();

        return isset($blocklist[$ip]);
    }

    /**
     * Add IP to blocklist.
     */
    public function block(string $ip, string $reason = 'manual'): void
    {
        DB::table('blocked_ips')->updateOrInsert(
            ['ip_address' => $ip],
            [
                'reason' => $reason,
                'blocked_at' => now(),
                'expires_at' => now()->addDays(30),
            ]
        );

        $this->clearCache();
    }

    /**
     * Remove IP from blocklist.
     */
    public function unblock(string $ip): void
    {
        DB::table('blocked_ips')->where('ip_address', $ip)->delete();
        $this->clearCache();
    }

    /**
     * Get full blocklist (cached).
     */
    public function getBlocklist(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return DB::table('blocked_ips')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->pluck('reason', 'ip_address')
                ->toArray();
        });
    }

    /**
     * Sync blocklist from honeypot critical hits.
     *
     * Call this from a scheduled job or after honeypot hits.
     */
    public function syncFromHoneypot(): int
    {
        // Block IPs with critical severity hits in last 24h
        $criticalIps = DB::table('honeypot_hits')
            ->where('severity', 'critical')
            ->where('created_at', '>=', now()->subDay())
            ->distinct()
            ->pluck('ip_address');

        $count = 0;
        foreach ($criticalIps as $ip) {
            DB::table('blocked_ips')->updateOrInsert(
                ['ip_address' => $ip],
                [
                    'reason' => 'honeypot_critical',
                    'blocked_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]
            );
            $count++;
        }

        $this->clearCache();

        return $count;
    }

    /**
     * Clear the cache.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get stats for dashboard.
     */
    public function getStats(): array
    {
        return [
            'total_blocked' => DB::table('blocked_ips')->count(),
            'active_blocked' => DB::table('blocked_ips')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->count(),
            'by_reason' => DB::table('blocked_ips')
                ->selectRaw('reason, COUNT(*) as count')
                ->groupBy('reason')
                ->pluck('count', 'reason')
                ->toArray(),
        ];
    }
}
