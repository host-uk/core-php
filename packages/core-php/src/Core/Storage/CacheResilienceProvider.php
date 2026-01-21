<?php

declare(strict_types=1);

namespace Core\Storage;

use Core\Storage\Events\RedisFallbackActivated;
use Illuminate\Contracts\Events\Dispatcher;
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
     *
     * Supports both phpredis extension and Predis library.
     */
    protected function isRedisAvailable(): bool
    {
        try {
            $host = config('database.redis.default.host', '127.0.0.1');
            $port = (int) config('database.redis.default.port', 6379);
            $password = config('database.redis.default.password');
            $timeout = 1.0; // 1 second timeout

            // Try phpredis extension first (faster)
            if (extension_loaded('redis')) {
                return $this->checkPhpRedis($host, $port, $password, $timeout);
            }

            // Fall back to Predis library
            if (class_exists(\Predis\Client::class)) {
                return $this->checkPredis($host, $port, $password, $timeout);
            }

            // No Redis client available
            return false;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check Redis availability using phpredis extension.
     */
    protected function checkPhpRedis(string $host, int $port, ?string $password, float $timeout): bool
    {
        try {
            $redis = new \Redis;

            if (! @$redis->connect($host, $port, $timeout)) {
                return false;
            }

            if ($password && ! @$redis->auth($password)) {
                $redis->close();

                return false;
            }

            $pong = @$redis->ping();
            $redis->close();

            return $pong === true || $pong === '+PONG';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Check Redis availability using Predis library.
     */
    protected function checkPredis(string $host, int $port, ?string $password, float $timeout): bool
    {
        try {
            $options = [
                'scheme' => 'tcp',
                'host' => $host,
                'port' => $port,
                'timeout' => $timeout,
            ];

            if ($password) {
                $options['password'] = $password;
            }

            $client = new \Predis\Client($options, [
                'exceptions' => true,
            ]);

            $pong = $client->ping();
            $client->disconnect();

            return $pong->getPayload() === 'PONG';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Switch cache and session to use database.
     */
    protected function applyDatabaseFallback(): void
    {
        $logLevel = config('core.storage.fallback_log_level', 'warning');

        // Log so we know fallback is active
        if (! $this->app->runningInConsole()) {
            Log::log($logLevel, '[CacheResilience] Redis unavailable at boot, using database for cache/session');
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

        // Dispatch event for monitoring/alerting
        $this->dispatchFallbackEvent();
    }

    /**
     * Dispatch the fallback event for monitoring/alerting.
     */
    protected function dispatchFallbackEvent(): void
    {
        if (! config('core.storage.dispatch_fallback_events', true)) {
            return;
        }

        // Dispatch after the app is booted to ensure event listeners are registered
        $this->app->booted(function () {
            $dispatcher = $this->app->make(Dispatcher::class);
            $dispatcher->dispatch(new RedisFallbackActivated(
                context: 'boot',
                errorMessage: 'Redis unavailable during application boot',
                fallbackDriver: 'database'
            ));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
