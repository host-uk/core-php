<?php

declare(strict_types=1);

namespace Core\Search;

use Core\Mod\Agentic\Models\AgentPlan;
use Core\Mod\Uptelligence\Models\Asset;
use Core\Mod\Uptelligence\Models\Pattern;
use Core\Mod\Uptelligence\Models\UpstreamTodo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Unified Search - single search across all system components.
 *
 * Searches MCP server tools/resources, API endpoints, patterns, assets,
 * vendor todos, and agent plans.
 */
class Unified
{
    public const TYPE_MCP_TOOL = 'mcp_tool';

    public const TYPE_MCP_RESOURCE = 'mcp_resource';

    public const TYPE_API_ENDPOINT = 'api_endpoint';

    public const TYPE_PATTERN = 'pattern';

    public const TYPE_ASSET = 'asset';

    public const TYPE_TODO = 'todo';

    public const TYPE_PLAN = 'plan';

    /**
     * Perform unified search across all sources.
     */
    public function search(string $query, array $types = [], int $limit = 50): Collection
    {
        $query = strtolower(trim($query));

        if (empty($query)) {
            return collect();
        }

        $results = collect();

        // Determine which types to search
        $searchAll = empty($types);

        if ($searchAll || in_array(self::TYPE_MCP_TOOL, $types)) {
            $results = $results->merge($this->searchMcpTools($query));
        }

        if ($searchAll || in_array(self::TYPE_MCP_RESOURCE, $types)) {
            $results = $results->merge($this->searchMcpResources($query));
        }

        if ($searchAll || in_array(self::TYPE_API_ENDPOINT, $types)) {
            $results = $results->merge($this->searchApiEndpoints($query));
        }

        if ($searchAll || in_array(self::TYPE_PATTERN, $types)) {
            $results = $results->merge($this->searchPatterns($query));
        }

        if ($searchAll || in_array(self::TYPE_ASSET, $types)) {
            $results = $results->merge($this->searchAssets($query));
        }

        if ($searchAll || in_array(self::TYPE_TODO, $types)) {
            $results = $results->merge($this->searchTodos($query));
        }

        if ($searchAll || in_array(self::TYPE_PLAN, $types)) {
            $results = $results->merge($this->searchPlans($query));
        }

        // Sort by relevance score and limit
        return $results
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * Search MCP tools from server YAML files.
     */
    protected function searchMcpTools(string $query): Collection
    {
        $results = collect();
        $servers = $this->loadMcpServers();

        foreach ($servers as $server) {
            foreach ($server['tools'] ?? [] as $tool) {
                $name = strtolower($tool['name'] ?? '');
                $purpose = strtolower($tool['purpose'] ?? '');

                $score = $this->calculateScore($query, [$name, $purpose]);

                if ($score > 0) {
                    $results->push([
                        'type' => self::TYPE_MCP_TOOL,
                        'icon' => 'wrench',
                        'title' => $tool['name'],
                        'subtitle' => $server['name'],
                        'description' => $tool['purpose'] ?? '',
                        'url' => '#mcp-'.$server['id'].'-'.$tool['name'],
                        'score' => $score,
                        'meta' => [
                            'server_id' => $server['id'],
                            'parameters' => array_keys($tool['parameters'] ?? []),
                        ],
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Search MCP resources from server YAML files.
     */
    protected function searchMcpResources(string $query): Collection
    {
        $results = collect();
        $servers = $this->loadMcpServers();

        foreach ($servers as $server) {
            foreach ($server['resources'] ?? [] as $resource) {
                $name = strtolower($resource['name'] ?? '');
                $uri = strtolower($resource['uri'] ?? '');
                $description = strtolower($resource['description'] ?? '');

                $score = $this->calculateScore($query, [$name, $uri, $description]);

                if ($score > 0) {
                    $results->push([
                        'type' => self::TYPE_MCP_RESOURCE,
                        'icon' => 'document',
                        'title' => $resource['name'] ?? $resource['uri'],
                        'subtitle' => $server['name'],
                        'description' => $resource['description'] ?? '',
                        'url' => '#mcp-'.$server['id'],
                        'score' => $score,
                        'meta' => [
                            'server_id' => $server['id'],
                            'uri' => $resource['uri'],
                        ],
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Search API endpoints from scramble config.
     */
    protected function searchApiEndpoints(string $query): Collection
    {
        $results = collect();

        // Define known endpoints (in production, parse from OpenAPI spec)
        $endpoints = [
            ['method' => 'GET', 'path' => '/api/v1/workspaces', 'description' => 'List all workspaces'],
            ['method' => 'POST', 'path' => '/api/v1/workspaces', 'description' => 'Create a new workspace'],
            ['method' => 'GET', 'path' => '/api/v1/workspaces/{id}', 'description' => 'Get workspace details'],
            ['method' => 'PUT', 'path' => '/api/v1/workspaces/{id}', 'description' => 'Update workspace'],
            ['method' => 'DELETE', 'path' => '/api/v1/workspaces/{id}', 'description' => 'Delete workspace'],
            ['method' => 'GET', 'path' => '/api/v1/biolinks', 'description' => 'List bio links'],
            ['method' => 'POST', 'path' => '/api/v1/biolinks', 'description' => 'Create bio link'],
            ['method' => 'GET', 'path' => '/api/v1/links', 'description' => 'List short links'],
            ['method' => 'POST', 'path' => '/api/v1/links', 'description' => 'Create short link'],
            ['method' => 'GET', 'path' => '/api/v1/analytics/summary', 'description' => 'Get analytics summary'],
        ];

        foreach ($endpoints as $endpoint) {
            $path = strtolower($endpoint['path']);
            $description = strtolower($endpoint['description']);

            $score = $this->calculateScore($query, [$path, $description, $endpoint['method']]);

            if ($score > 0) {
                $results->push([
                    'type' => self::TYPE_API_ENDPOINT,
                    'icon' => 'globe-alt',
                    'title' => "{$endpoint['method']} {$endpoint['path']}",
                    'subtitle' => 'API Endpoint',
                    'description' => $endpoint['description'],
                    'url' => '/docs/api',
                    'score' => $score,
                    'meta' => [
                        'method' => $endpoint['method'],
                        'path' => $endpoint['path'],
                    ],
                ]);
            }
        }

        return $results;
    }

    /**
     * Search patterns from database.
     */
    protected function searchPatterns(string $query): Collection
    {
        if (! class_exists(Pattern::class)) {
            return collect();
        }

        try {
            return Pattern::where('name', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->orWhere('category', 'like', "%{$query}%")
                ->limit(20)
                ->get()
                ->map(fn ($pattern) => [
                    'type' => self::TYPE_PATTERN,
                    'icon' => 'puzzle-piece',
                    'title' => $pattern->name,
                    'subtitle' => $pattern->category,
                    'description' => $pattern->description ?? '',
                    'url' => '#',
                    'score' => $this->calculateScore($query, [
                        strtolower($pattern->name),
                        strtolower($pattern->description ?? ''),
                    ]),
                    'meta' => [
                        'id' => $pattern->id,
                        'category' => $pattern->category,
                    ],
                ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Search assets from database.
     */
    protected function searchAssets(string $query): Collection
    {
        if (! class_exists(Asset::class)) {
            return collect();
        }

        try {
            return Asset::where('name', 'like', "%{$query}%")
                ->orWhere('slug', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(20)
                ->get()
                ->map(fn ($asset) => [
                    'type' => self::TYPE_ASSET,
                    'icon' => 'cube',
                    'title' => $asset->name,
                    'subtitle' => $asset->type,
                    'description' => $asset->description ?? '',
                    'url' => '#',
                    'score' => $this->calculateScore($query, [
                        strtolower($asset->name),
                        strtolower($asset->slug),
                    ]),
                    'meta' => [
                        'id' => $asset->id,
                        'type' => $asset->type,
                        'version' => $asset->installed_version,
                    ],
                ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Search upstream todos from database.
     */
    protected function searchTodos(string $query): Collection
    {
        if (! class_exists(UpstreamTodo::class)) {
            return collect();
        }

        try {
            return UpstreamTodo::where('title', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(20)
                ->get()
                ->map(fn ($todo) => [
                    'type' => self::TYPE_TODO,
                    'icon' => 'clipboard-list',
                    'title' => $todo->title,
                    'subtitle' => $todo->vendor?->name ?? 'Vendor Todo',
                    'description' => Str::limit($todo->description ?? '', 100),
                    'url' => '#',
                    'score' => $this->calculateScore($query, [
                        strtolower($todo->title),
                        strtolower($todo->description ?? ''),
                    ]),
                    'meta' => [
                        'id' => $todo->id,
                        'status' => $todo->status,
                        'priority' => $todo->priority,
                    ],
                ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Search agent plans from database.
     */
    protected function searchPlans(string $query): Collection
    {
        try {
            return AgentPlan::where('title', 'like', "%{$query}%")
                ->orWhere('slug', 'like', "%{$query}%")
                ->orWhere('description', 'like', "%{$query}%")
                ->limit(20)
                ->get()
                ->map(fn ($plan) => [
                    'type' => self::TYPE_PLAN,
                    'icon' => 'map',
                    'title' => $plan->title,
                    'subtitle' => "Plan: {$plan->status}",
                    'description' => $plan->description ?? '',
                    'url' => '#',
                    'score' => $this->calculateScore($query, [
                        strtolower($plan->title),
                        strtolower($plan->slug),
                    ]),
                    'meta' => [
                        'slug' => $plan->slug,
                        'status' => $plan->status,
                        'progress' => $plan->getProgress(),
                    ],
                ]);
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * Calculate relevance score for a result.
     */
    protected function calculateScore(string $query, array $fields): float
    {
        $score = 0;
        $words = explode(' ', $query);

        foreach ($fields as $index => $field) {
            if (empty($field)) {
                continue;
            }

            // Exact match in field
            if (str_contains($field, $query)) {
                $score += 10 - $index; // Earlier fields weighted higher
            }

            // Word matches
            foreach ($words as $word) {
                if (strlen($word) > 2 && str_contains($field, $word)) {
                    $score += 3;
                }
            }

            // Starts with query
            if (str_starts_with($field, $query)) {
                $score += 5;
            }
        }

        return $score;
    }

    /**
     * Load all MCP servers from YAML files.
     */
    protected function loadMcpServers(): array
    {
        return Cache::remember('unified_search:mcp_servers', 300, function () {
            $servers = [];

            $registryPath = resource_path('mcp/registry.yaml');
            if (! file_exists($registryPath)) {
                return $servers;
            }

            $registry = Yaml::parseFile($registryPath);

            foreach ($registry['servers'] ?? [] as $ref) {
                $serverPath = resource_path("mcp/servers/{$ref['id']}.yaml");
                if (file_exists($serverPath)) {
                    $servers[] = Yaml::parseFile($serverPath);
                }
            }

            return $servers;
        });
    }

    /**
     * Get available search types for filtering.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_MCP_TOOL => ['name' => 'MCP Tools', 'icon' => 'wrench'],
            self::TYPE_MCP_RESOURCE => ['name' => 'MCP Resources', 'icon' => 'document'],
            self::TYPE_API_ENDPOINT => ['name' => 'API Endpoints', 'icon' => 'globe-alt'],
            self::TYPE_PATTERN => ['name' => 'Patterns', 'icon' => 'puzzle-piece'],
            self::TYPE_ASSET => ['name' => 'Assets', 'icon' => 'cube'],
            self::TYPE_TODO => ['name' => 'Todos', 'icon' => 'clipboard-list'],
            self::TYPE_PLAN => ['name' => 'Plans', 'icon' => 'map'],
        ];
    }
}
