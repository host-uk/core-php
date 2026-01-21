<?php

declare(strict_types=1);

namespace Core\Website\Api\Services;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class OpenApiGenerator
{
    /**
     * Generate OpenAPI 3.0 specification from Laravel routes.
     */
    public function generate(): array
    {
        return [
            'openapi' => '3.0.0',
            'info' => $this->buildInfo(),
            'servers' => $this->buildServers(),
            'tags' => $this->buildTags(),
            'paths' => $this->buildPaths(),
            'components' => $this->buildComponents(),
        ];
    }

    protected function buildInfo(): array
    {
        return [
            'title' => config('app.name', 'API'),
            'description' => config('core.api.description', 'API Documentation'),
            'version' => config('core.api.version', '1.0.0'),
            'contact' => config('core.api.contact', []),
        ];
    }

    protected function buildServers(): array
    {
        return [
            [
                'url' => config('app.url').'/api',
                'description' => 'Production API',
            ],
        ];
    }

    protected function buildTags(): array
    {
        return config('core.api.tags', []);
    }

    protected function buildPaths(): array
    {
        $paths = [];

        foreach (RouteFacade::getRoutes() as $route) {
            /** @var Route $route */
            if (! $this->isApiRoute($route)) {
                continue;
            }

            $path = $this->normalisePath($route->uri());
            $methods = array_filter($route->methods(), fn ($m) => $m !== 'HEAD');

            foreach ($methods as $method) {
                $method = strtolower($method);
                $paths[$path][$method] = $this->buildOperation($route, $method);
            }
        }

        ksort($paths);

        return $paths;
    }

    protected function isApiRoute(Route $route): bool
    {
        $uri = $route->uri();

        // Must start with 'api/' or be exactly 'api'
        if (! str_starts_with($uri, 'api/') && $uri !== 'api') {
            return false;
        }

        // Skip sanctum routes
        if (str_contains($uri, 'sanctum')) {
            return false;
        }

        return true;
    }

    protected function normalisePath(string $uri): string
    {
        // Remove 'api' prefix, keep leading slash
        $path = '/'.ltrim(Str::after($uri, 'api/'), '/');

        // Convert Laravel route parameters to OpenAPI format
        $path = preg_replace('/\{([^}]+)\}/', '{$1}', $path);

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    protected function buildOperation(Route $route, string $method): array
    {
        $name = $route->getName() ?? '';
        $tag = $this->inferTag($route);

        $operation = [
            'tags' => [$tag],
            'summary' => $this->generateSummary($route, $method),
            'operationId' => $name ?: Str::camel($method.'_'.str_replace('/', '_', $route->uri())),
            'responses' => [
                '200' => ['description' => 'Successful response'],
            ],
        ];

        // Add parameters for path variables
        $parameters = $this->buildParameters($route);
        if (! empty($parameters)) {
            $operation['parameters'] = $parameters;
        }

        // Add request body for POST/PUT/PATCH
        if (in_array($method, ['post', 'put', 'patch'])) {
            $operation['requestBody'] = [
                'required' => true,
                'content' => [
                    'application/json' => [
                        'schema' => ['type' => 'object'],
                    ],
                ],
            ];
        }

        // Add security based on middleware
        $security = $this->inferSecurity($route);
        if (! empty($security)) {
            $operation['security'] = $security;
        }

        return $operation;
    }

    protected function inferTag(Route $route): string
    {
        $uri = $route->uri();
        $name = $route->getName() ?? '';

        // Match by route name prefix - configurable
        $tagMap = config('core.api.tag_map.routes', []);

        foreach ($tagMap as $prefix => $tag) {
            if (str_starts_with($name, $prefix)) {
                return $tag;
            }
        }

        // Match by URI prefix (check start of path after 'api/')
        $path = preg_replace('#^api/#', '', $uri);
        $uriTagMap = config('core.api.tag_map.uris', []);

        foreach ($uriTagMap as $prefix => $tag) {
            if (str_starts_with($path, $prefix)) {
                return $tag;
            }
        }

        return 'General';
    }

    protected function generateSummary(Route $route, string $method): string
    {
        $name = $route->getName();

        if ($name) {
            // Convert route name to human-readable summary
            $parts = explode('.', $name);
            $action = array_pop($parts);

            return Str::title(str_replace(['-', '_'], ' ', $action));
        }

        // Generate from URI and method
        $uri = Str::afterLast($route->uri(), '/');

        return Str::title($method.' '.str_replace(['-', '_'], ' ', $uri));
    }

    protected function buildParameters(Route $route): array
    {
        $parameters = [];
        preg_match_all('/\{([^}]+)\}/', $route->uri(), $matches);

        foreach ($matches[1] as $param) {
            $optional = str_ends_with($param, '?');
            $paramName = rtrim($param, '?');

            $parameters[] = [
                'name' => $paramName,
                'in' => 'path',
                'required' => ! $optional,
                'schema' => ['type' => 'string'],
            ];
        }

        return $parameters;
    }

    protected function inferSecurity(Route $route): array
    {
        $middleware = $route->middleware();

        if (in_array('auth', $middleware) || in_array('auth:sanctum', $middleware)) {
            return [['bearerAuth' => []]];
        }

        foreach ($middleware as $m) {
            if (str_contains($m, 'ApiKey')) {
                return [['apiKeyAuth' => []]];
            }
        }

        return [];
    }

    protected function buildComponents(): array
    {
        return [
            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'Sanctum authentication token',
                ],
                'apiKeyAuth' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key',
                    'description' => 'API key for service-to-service authentication',
                ],
            ],
        ];
    }
}
