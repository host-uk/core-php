<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when resolving a domain to a website provider.
 *
 * Mod Boot classes listen for this event and register themselves
 * if their domain pattern matches the incoming host.
 */
class DomainResolving
{
    protected ?string $matchedProvider = null;

    public function __construct(
        public readonly string $host
    ) {}

    /**
     * Check if host matches a domain pattern.
     */
    public function matches(string $pattern): bool
    {
        $normalised = strtolower(parse_url('http://'.$this->host, PHP_URL_HOST) ?? $this->host);

        return (bool) preg_match($pattern, $normalised);
    }

    /**
     * Register as the matching provider.
     */
    public function register(string $providerClass): void
    {
        $this->matchedProvider = $providerClass;
    }

    /**
     * Get the matched provider (if any).
     */
    public function matchedProvider(): ?string
    {
        return $this->matchedProvider;
    }
}
