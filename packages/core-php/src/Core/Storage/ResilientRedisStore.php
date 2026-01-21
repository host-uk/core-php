<?php

declare(strict_types=1);

namespace Core\Storage;

use Core\Storage\Events\RedisFallbackActivated;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Redis cache store with automatic database fallback.
 *
 * Wraps Redis operations in try-catch. If Redis fails,
 * falls back to database store for that operation.
 */
class ResilientRedisStore extends RedisStore
{
    protected ?DatabaseStore $fallbackStore = null;

    protected bool $fallbackActivated = false;

    /**
     * Get the fallback database store.
     */
    protected function getFallbackStore(): DatabaseStore
    {
        if ($this->fallbackStore === null) {
            $this->fallbackStore = new DatabaseStore(
                app('db')->connection(),
                'cache',
                app('config')->get('cache.prefix', '')
            );
        }

        return $this->fallbackStore;
    }

    /**
     * Handle Redis failure by logging and optionally dispatching an event.
     *
     * @throws \Throwable When silent_fallback is disabled
     */
    protected function handleRedisFailure(\Throwable $e): void
    {
        $silentFallback = config('core.storage.silent_fallback', true);

        if (! $silentFallback) {
            throw $e;
        }

        $this->logFallback($e);
        $this->dispatchFallbackEvent($e);
    }

    /**
     * Log the fallback (once per request).
     */
    protected function logFallback(\Throwable $e): void
    {
        if ($this->fallbackActivated) {
            return;
        }

        $logLevel = config('core.storage.fallback_log_level', 'warning');

        Log::log($logLevel, '[Cache] Redis unavailable, using database fallback', [
            'error' => $e->getMessage(),
            'exception_class' => get_class($e),
        ]);
    }

    /**
     * Dispatch the fallback event for monitoring/alerting (once per request).
     */
    protected function dispatchFallbackEvent(\Throwable $e): void
    {
        if ($this->fallbackActivated) {
            return;
        }

        $this->fallbackActivated = true;

        if (! config('core.storage.dispatch_fallback_events', true)) {
            return;
        }

        $dispatcher = app(Dispatcher::class);
        $dispatcher->dispatch(new RedisFallbackActivated(
            context: 'cache_operation',
            errorMessage: $e->getMessage(),
            fallbackDriver: 'database'
        ));
    }

    /**
     * Retrieve an item from the cache by key.
     */
    public function get($key): mixed
    {
        try {
            return parent::get($key);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->get($key);
        }
    }

    /**
     * Retrieve multiple items from the cache by key.
     */
    public function many(array $keys): array
    {
        try {
            return parent::many($keys);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->many($keys);
        }
    }

    /**
     * Store an item in the cache for a given number of seconds.
     */
    public function put($key, $value, $seconds): bool
    {
        try {
            return parent::put($key, $value, $seconds);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->put($key, $value, $seconds);
        }
    }

    /**
     * Store multiple items in the cache for a given number of seconds.
     */
    public function putMany(array $values, $seconds): bool
    {
        try {
            return parent::putMany($values, $seconds);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->putMany($values, $seconds);
        }
    }

    /**
     * Increment the value of an item in the cache.
     */
    public function increment($key, $value = 1): int|bool
    {
        try {
            return parent::increment($key, $value);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->increment($key, $value);
        }
    }

    /**
     * Decrement the value of an item in the cache.
     */
    public function decrement($key, $value = 1): int|bool
    {
        try {
            return parent::decrement($key, $value);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->decrement($key, $value);
        }
    }

    /**
     * Store an item in the cache indefinitely.
     */
    public function forever($key, $value): bool
    {
        try {
            return parent::forever($key, $value);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->forever($key, $value);
        }
    }

    /**
     * Remove an item from the cache.
     */
    public function forget($key): bool
    {
        try {
            return parent::forget($key);
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->forget($key);
        }
    }

    /**
     * Remove all items from the cache.
     */
    public function flush(): bool
    {
        try {
            return parent::flush();
        } catch (\Throwable $e) {
            $this->handleRedisFailure($e);

            return $this->getFallbackStore()->flush();
        }
    }
}
