<?php

declare(strict_types=1);

namespace Mod\Mcp\Services;

use Illuminate\Support\Collection;
use Mod\Api\Models\ApiKey;
use Mod\Mcp\Tools\Agent\Contracts\AgentToolInterface;

/**
 * Registry for MCP Agent Server tools.
 *
 * Provides discovery, permission checking, and execution
 * of registered agent tools.
 */
class AgentToolRegistry
{
    /**
     * Registered tools indexed by name.
     *
     * @var array<string, AgentToolInterface>
     */
    protected array $tools = [];

    /**
     * Register a tool.
     */
    public function register(AgentToolInterface $tool): self
    {
        $this->tools[$tool->name()] = $tool;

        return $this;
    }

    /**
     * Register multiple tools at once.
     *
     * @param  array<AgentToolInterface>  $tools
     */
    public function registerMany(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->register($tool);
        }

        return $this;
    }

    /**
     * Check if a tool is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Get a tool by name.
     */
    public function get(string $name): ?AgentToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered tools.
     *
     * @return Collection<string, AgentToolInterface>
     */
    public function all(): Collection
    {
        return collect($this->tools);
    }

    /**
     * Get tools filtered by category.
     *
     * @return Collection<string, AgentToolInterface>
     */
    public function byCategory(string $category): Collection
    {
        return $this->all()->filter(
            fn (AgentToolInterface $tool) => $tool->category() === $category
        );
    }

    /**
     * Get tools accessible by an API key.
     *
     * @return Collection<string, AgentToolInterface>
     */
    public function forApiKey(ApiKey $apiKey): Collection
    {
        return $this->all()->filter(function (AgentToolInterface $tool) use ($apiKey) {
            // Check if API key has required scopes
            foreach ($tool->requiredScopes() as $scope) {
                if (! $apiKey->hasScope($scope)) {
                    return false;
                }
            }

            // Check if API key has tool-level permission
            return $this->apiKeyCanAccessTool($apiKey, $tool->name());
        });
    }

    /**
     * Check if an API key can access a specific tool.
     */
    public function apiKeyCanAccessTool(ApiKey $apiKey, string $toolName): bool
    {
        $allowedTools = $apiKey->tool_scopes ?? null;

        // Null means all tools allowed
        if ($allowedTools === null) {
            return true;
        }

        return in_array($toolName, $allowedTools, true);
    }

    /**
     * Execute a tool with permission checking.
     *
     * @param  string  $name  Tool name
     * @param  array  $args  Tool arguments
     * @param  array  $context  Execution context
     * @param  ApiKey|null  $apiKey  Optional API key for permission checking
     * @return array Tool result
     *
     * @throws \InvalidArgumentException If tool not found
     * @throws \RuntimeException If permission denied
     */
    public function execute(string $name, array $args, array $context = [], ?ApiKey $apiKey = null): array
    {
        $tool = $this->get($name);

        if (! $tool) {
            throw new \InvalidArgumentException("Unknown tool: {$name}");
        }

        // Permission check if API key provided
        if ($apiKey !== null) {
            // Check scopes
            foreach ($tool->requiredScopes() as $scope) {
                if (! $apiKey->hasScope($scope)) {
                    throw new \RuntimeException(
                        "Permission denied: API key missing scope '{$scope}' for tool '{$name}'"
                    );
                }
            }

            // Check tool-level permission
            if (! $this->apiKeyCanAccessTool($apiKey, $name)) {
                throw new \RuntimeException(
                    "Permission denied: API key does not have access to tool '{$name}'"
                );
            }
        }

        return $tool->handle($args, $context);
    }

    /**
     * Get all tools as MCP tool definitions.
     *
     * @param  ApiKey|null  $apiKey  Filter by API key permissions
     */
    public function toMcpDefinitions(?ApiKey $apiKey = null): array
    {
        $tools = $apiKey !== null
            ? $this->forApiKey($apiKey)
            : $this->all();

        return $tools->map(fn (AgentToolInterface $tool) => $tool->toMcpDefinition())
            ->values()
            ->all();
    }

    /**
     * Get tool categories with counts.
     */
    public function categories(): Collection
    {
        return $this->all()
            ->groupBy(fn (AgentToolInterface $tool) => $tool->category())
            ->map(fn ($tools) => $tools->count());
    }

    /**
     * Get all tool names.
     *
     * @return array<string>
     */
    public function names(): array
    {
        return array_keys($this->tools);
    }

    /**
     * Get tool count.
     */
    public function count(): int
    {
        return count($this->tools);
    }
}
