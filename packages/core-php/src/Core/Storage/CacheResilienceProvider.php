<?php

declare(strict_types=1);

namespace Core\Storage;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

/**
 * Provides resilient cache/session configuration.
 *
 * Attempts to use Redis when available, gracefully falls back to database
 * if Redis is unavailable. This ensures the app works out of the box
 * without requiring Redis to be configured.
 */
class CacheResilienceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Run early, before other providers try to use cache
        if ($this->shouldFallbackToDatabase()) {
            $this->applyDatabaseFallback();
        }

        // Register resilient Redis driver that catches exceptions
        $this->registerResilientRedisDriver();
    }

    /**
     * Register the resilient-redis cache driver.
     *
     * This wraps all Redis cache operations in try-catch,
     * falling back to database if Redis fails mid-request.
     */
    protected function registerResilientRedisDriver(): void
    {
        $this->app->booting(function () {
            Cache::extend('resilient-redis', function ($app, $config) {
                $redis = $app['redis'];
                $prefix = $app['config']['cache.prefix'];
                $connection = $config['connection'] ?? 'default';

                return Cache::repository(
                    new ResilientRedisStore($redis, $prefix, $connection)
                );
            });
        });
    }

    /**
     * Check if we need to fall back to database storage.
     */
    protected function shouldFallbackToDatabase(): bool
    {
        // If explicitly configured to use database, respect that
        if (config('cache.default') === 'database') {
            return false;
        }

        // If not configured for Redis, no need to check
        if (config('cache.default') !== 'redis') {
            return false;
        }

        // Try to connect to Redis
        return ! $this->isRedisAvailable();
    }

    /**
     * Check if Redis is available and responding.
     */
    protected function isRedisAvailable(): bool
    {
        try {
            $redis = new \Redis;
            $host = config('database.redis.default.host', '127.0.0.1');
            $port = (int) config('database.redis.default.port', 6379);
            $timeout = 1.0; // 1 second timeout

            // Try to connect with short timeout
            if (! @$redis->connect($host, $port, $timeout)) {
                return false;
            }

            // Authenticate if password is set
            $password = config('database.redis.default.password');
            if ($password && ! @$redis->auth($password)) {
                $redis->close();

                return false;
            }

            // Verify with PING
            $pong = @$redis->ping();
            $redis->close();

            return $pong === true || $pong === '+PONG';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Switch cache and session to use database.
     */
    protected function applyDatabaseFallback(): void
    {
        // Log once so we know fallback is active
        if (! $this->app->runningInConsole()) {
            Log::warning('[CacheResilience] Redis unavailable, using database for cache/session');
        }

        // Override cache driver
        config(['cache.default' => 'database']);

        // Override session driver if it was set to Redis
        if (config('session.driver') === 'redis') {
            config(['session.driver' => 'database']);
        }

        // Override queue connection if it was set to Redis
        if (config('queue.default') === 'redis') {
            config(['queue.default' => 'database']);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
