<?php

declare(strict_types=1);

namespace Core\Mod\Hub\Models;

use Illuminate\Database\Eloquent\Model;

class HoneypotHit extends Model
{
    protected $fillable = [
        'ip_address',
        'user_agent',
        'referer',
        'path',
        'method',
        'headers',
        'country',
        'city',
        'is_bot',
        'bot_name',
        'severity',
    ];

    protected $casts = [
        'headers' => 'array',
        'is_bot' => 'boolean',
    ];

    /**
     * Severity levels for honeypot hits.
     */
    public const SEVERITY_WARNING = 'warning';   // Ignored robots.txt (/teapot)
    public const SEVERITY_CRITICAL = 'critical'; // Active probing (/admin)

    /**
     * Determine severity based on path.
     */
    public static function severityForPath(string $path): string
    {
        // Paths that indicate active malicious probing
        $criticalPaths = [
            'admin',
            'wp-admin',
            'wp-login.php',
            'administrator',
            'phpmyadmin',
            '.env',
            '.git',
        ];

        $path = ltrim($path, '/');

        foreach ($criticalPaths as $critical) {
            if (str_starts_with($path, $critical)) {
                return self::SEVERITY_CRITICAL;
            }
        }

        return self::SEVERITY_WARNING;
    }

    /**
     * Known bad bot patterns.
     */
    protected static array $botPatterns = [
        'AhrefsBot' => 'Ahrefs',
        'SemrushBot' => 'Semrush',
        'MJ12bot' => 'Majestic',
        'DotBot' => 'Moz',
        'BLEXBot' => 'BLEXBot',
        'PetalBot' => 'Petal',
        'YandexBot' => 'Yandex',
        'bingbot' => 'Bing',
        'Googlebot' => 'Google',
        'Bytespider' => 'ByteDance',
        'GPTBot' => 'OpenAI',
        'CCBot' => 'Common Crawl',
        'ClaudeBot' => 'Anthropic',
        'anthropic-ai' => 'Anthropic',
        'DataForSeoBot' => 'DataForSEO',
        'serpstatbot' => 'Serpstat',
        'curl/' => 'cURL',
        'python-requests' => 'Python',
        'Go-http-client' => 'Go',
        'wget' => 'Wget',
        'scrapy' => 'Scrapy',
        'HeadlessChrome' => 'HeadlessChrome',
        'PhantomJS' => 'PhantomJS',
    ];

    /**
     * Detect if the user agent is a known bot.
     */
    public static function detectBot(?string $userAgent): ?string
    {
        if (empty($userAgent)) {
            return 'Unknown (no UA)';
        }

        foreach (self::$botPatterns as $pattern => $name) {
            if (stripos($userAgent, $pattern) !== false) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Scope for recent hits.
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope for a specific IP.
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope for bots only.
     */
    public function scopeBots($query)
    {
        return $query->where('is_bot', true);
    }

    /**
     * Scope for critical severity (blocklist candidates).
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    /**
     * Scope for warning severity.
     */
    public function scopeWarning($query)
    {
        return $query->where('severity', self::SEVERITY_WARNING);
    }

    /**
     * Get stats for the dashboard.
     */
    public static function getStats(): array
    {
        return [
            'total' => self::count(),
            'today' => self::whereDate('created_at', today())->count(),
            'this_week' => self::where('created_at', '>=', now()->subWeek())->count(),
            'unique_ips' => self::distinct('ip_address')->count('ip_address'),
            'bots' => self::where('is_bot', true)->count(),
            'top_ips' => self::selectRaw('ip_address, COUNT(*) as hits')
                ->groupBy('ip_address')
                ->orderByDesc('hits')
                ->limit(10)
                ->get(),
            'top_bots' => self::selectRaw('bot_name, COUNT(*) as hits')
                ->whereNotNull('bot_name')
                ->groupBy('bot_name')
                ->orderByDesc('hits')
                ->limit(10)
                ->get(),
        ];
    }
}
