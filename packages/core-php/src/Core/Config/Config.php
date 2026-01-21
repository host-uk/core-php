<?php

declare(strict_types=1);

namespace Core\Config;

use Core\Mod\Social\Contracts\Config as ConfigContract;
use Core\Mod\Social\Models\Config as ConfigModel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

/**
 * Abstract base class for configuration form providers.
 *
 * Provides a standardised interface for managing configuration settings
 * with validation, caching, and database persistence.
 */
abstract class Config implements ConfigContract
{
    /**
     * Create a new config instance.
     */
    public function __construct(public readonly ?Request $request = null) {}

    /**
     * Save configuration data from request or provided array.
     *
     * @param  array<string, mixed>  $data  Override data (optional)
     */
    public function save(array $data = []): void
    {
        foreach ($this->form() as $name => $_) {
            $payload = Arr::get($data, $name, $this->request?->input($name));

            $this->persistData($name, $payload);
        }
    }

    /**
     * Persist data to database and cache.
     *
     * @param  string  $name  Configuration field name
     * @param  mixed  $payload  Value to store
     */
    public function persistData(string $name, mixed $payload): void
    {
        $this->insert($name, $payload);
        $this->putCache($name, $payload);
    }

    /**
     * Insert or update configuration in database.
     *
     * @param  string  $name  Configuration field name
     * @param  mixed  $payload  Value to store
     */
    public function insert(string $name, mixed $payload): void
    {
        ConfigModel::updateOrCreate(
            ['name' => $name, 'group' => $this->group()],
            ['payload' => $payload]
        );
    }

    /**
     * Get a configuration value.
     *
     * Checks cache first, then database, finally falls back to default from form().
     *
     * @param  string  $name  Configuration field name
     */
    public function get(string $name): mixed
    {
        return $this->getCache($name, function () use ($name) {
            $payload = ConfigModel::get(
                property: "{$this->group()}.{$name}",
                default: Arr::get($this->form(), $name)
            );

            $this->putCache($name, $payload);

            return $payload;
        });
    }

    /**
     * Get all configuration values for this group.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Arr::map($this->form(), function ($_, $name) {
            return $this->get($name);
        });
    }

    /**
     * Store a value in cache.
     *
     * @param  string  $name  Configuration field name
     * @param  mixed  $default  Value to cache
     */
    public function putCache(string $name, mixed $default = null): void
    {
        Cache::put($this->resolveCacheKey($name), $default);
    }

    /**
     * Retrieve a value from cache.
     *
     * @param  string  $name  Configuration field name
     * @param  mixed  $default  Default value or closure to execute if not cached
     */
    public function getCache(string $name, mixed $default = null): mixed
    {
        return Cache::get($this->resolveCacheKey($name), $default);
    }

    /**
     * Remove cache entries for this configuration group.
     *
     * @param  string|null  $name  Specific field name, or null to clear all
     */
    public function forgetCache(?string $name = null): void
    {
        if (! $name) {
            foreach (array_keys($this->form()) as $fieldName) {
                $this->forgetCache($fieldName);
            }

            return;
        }

        Cache::forget($this->resolveCacheKey($name));
    }

    /**
     * Build cache key for a configuration field.
     *
     * @param  string  $key  Configuration field name
     */
    private function resolveCacheKey(string $key): string
    {
        $prefix = config('social.cache_prefix', 'social');

        return "{$prefix}.configs.{$this->group()}.{$key}";
    }
}
