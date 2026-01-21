<?php

declare(strict_types=1);

namespace Core\Mod\Api\Controllers;

use Core\Front\Controller;
use Core\Mod\Mcp\Models\McpApiRequest;
use Core\Mod\Mcp\Models\McpToolCall;
use Core\Mod\Mcp\Services\McpWebhookDispatcher;
use Core\Mod\Api\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Yaml\Yaml;

/**
 * MCP HTTP API Controller.
 *
 * Provides HTTP bridge to MCP servers for external integrations.
 */
class McpApiController extends Controller
{
    /**
     * List all available MCP servers.
     *
     * GET /api/v1/mcp/servers
     */
    public function servers(Request $request): JsonResponse
    {
        $registry = $this->loadRegistry();

        $servers = collect($registry['servers'] ?? [])
            ->map(fn ($ref) => $this->loadServerSummary($ref['id']))
            ->filter()
            ->values();

        return response()->json([
            'servers' => $servers,
            'count' => $servers->count(),
        ]);
    }

    /**
     * Get server details with tools and resources.
     *
     * GET /api/v1/mcp/servers/{id}
     */
    public function server(Request $request, string $id): JsonResponse
    {
        $server = $this->loadServerFull($id);

        if (! $server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        return response()->json($server);
    }

    /**
     * List tools for a specific server.
     *
     * GET /api/v1/mcp/servers/{id}/tools
     */
    public function tools(Request $request, string $id): JsonResponse
    {
        $server = $this->loadServerFull($id);

        if (! $server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        return response()->json([
            'server' => $id,
            'tools' => $server['tools'] ?? [],
            'count' => count($server['tools'] ?? []),
        ]);
    }

    /**
     * Execute a tool on an MCP server.
     *
     * POST /api/v1/mcp/tools/call
     */
    public function callTool(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server' => 'required|string',
            'tool' => 'required|string',
            'arguments' => 'nullable|array',
        ]);

        $server = $this->loadServerFull($validated['server']);
        if (! $server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        // Verify tool exists
        $toolDef = collect($server['tools'] ?? [])->firstWhere('name', $validated['tool']);
        if (! $toolDef) {
            return response()->json(['error' => 'Tool not found'], 404);
        }

        // Get API key for logging
        $apiKey = $request->attributes->get('api_key');
        $workspace = $apiKey?->workspace;

        $startTime = microtime(true);

        try {
            // Execute the tool via artisan command
            $result = $this->executeToolViaArtisan(
                $validated['server'],
                $validated['tool'],
                $validated['arguments'] ?? []
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log the call
            $this->logToolCall($apiKey, $validated, $result, $durationMs, true);

            // Dispatch webhooks
            $this->dispatchWebhook($apiKey, $validated, true, $durationMs);

            $response = [
                'success' => true,
                'server' => $validated['server'],
                'tool' => $validated['tool'],
                'result' => $result,
                'duration_ms' => $durationMs,
            ];

            // Log full request for debugging/replay
            $this->logApiRequest($request, $validated, 200, $response, $durationMs, $apiKey);

            return response()->json($response);
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logToolCall($apiKey, $validated, null, $durationMs, false, $e->getMessage());

            // Dispatch webhooks (even on failure)
            $this->dispatchWebhook($apiKey, $validated, false, $durationMs, $e->getMessage());

            $response = [
                'success' => false,
                'error' => $e->getMessage(),
                'server' => $validated['server'],
                'tool' => $validated['tool'],
            ];

            // Log full request for debugging/replay
            $this->logApiRequest($request, $validated, 500, $response, $durationMs, $apiKey, $e->getMessage());

            return response()->json($response, 500);
        }
    }

    /**
     * Read a resource from an MCP server.
     *
     * GET /api/v1/mcp/resources/{uri}
     */
    public function resource(Request $request, string $uri): JsonResponse
    {
        // Parse URI format: server://resource/path
        if (! preg_match('/^([a-z0-9-]+):\/\/(.+)$/', $uri, $matches)) {
            return response()->json(['error' => 'Invalid resource URI format'], 400);
        }

        $serverId = $matches[1];
        $resourcePath = $matches[2];

        $server = $this->loadServerFull($serverId);
        if (! $server) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        try {
            $result = $this->readResourceViaArtisan($serverId, $resourcePath);

            return response()->json([
                'uri' => $uri,
                'content' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'uri' => $uri,
            ], 500);
        }
    }

    /**
     * Execute tool via artisan MCP server command.
     */
    protected function executeToolViaArtisan(string $server, string $tool, array $arguments): mixed
    {
        $commandMap = [
            'hosthub-agent' => 'mcp:agent-server',
            'socialhost' => 'mcp:socialhost-server',
            'biohost' => 'mcp:biohost-server',
            'commerce' => 'mcp:commerce-server',
            'supporthost' => 'mcp:support-server',
            'upstream' => 'mcp:upstream-server',
        ];

        $command = $commandMap[$server] ?? null;
        if (! $command) {
            throw new \RuntimeException("Unknown server: {$server}");
        }

        // Build MCP request
        $mcpRequest = [
            'jsonrpc' => '2.0',
            'id' => uniqid(),
            'method' => 'tools/call',
            'params' => [
                'name' => $tool,
                'arguments' => $arguments,
            ],
        ];

        // Execute via process
        $process = proc_open(
            ['php', 'artisan', $command],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path()
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('Failed to start MCP server process');
        }

        fwrite($pipes[0], json_encode($mcpRequest)."\n");
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        $response = json_decode($output, true);

        if (isset($response['error'])) {
            throw new \RuntimeException($response['error']['message'] ?? 'Tool execution failed');
        }

        return $response['result'] ?? null;
    }

    /**
     * Read resource via artisan MCP server command.
     */
    protected function readResourceViaArtisan(string $server, string $path): mixed
    {
        // Similar to executeToolViaArtisan but with resources/read method
        // Simplified for now - can expand later
        return ['path' => $path, 'content' => 'Resource reading not yet implemented'];
    }

    /**
     * Log full API request for debugging and replay.
     */
    protected function logApiRequest(
        Request $request,
        array $validated,
        int $status,
        array $response,
        int $durationMs,
        ?ApiKey $apiKey,
        ?string $error = null
    ): void {
        try {
            McpApiRequest::log(
                method: $request->method(),
                path: '/tools/call',
                requestBody: $validated,
                responseStatus: $status,
                responseBody: $response,
                durationMs: $durationMs,
                workspaceId: $apiKey?->workspace_id,
                apiKeyId: $apiKey?->id,
                serverId: $validated['server'],
                toolName: $validated['tool'],
                errorMessage: $error,
                ipAddress: $request->ip(),
                headers: $request->headers->all()
            );
        } catch (\Throwable $e) {
            // Don't let logging failures affect API response
            report($e);
        }
    }

    /**
     * Dispatch webhook for tool execution.
     */
    protected function dispatchWebhook(
        ?ApiKey $apiKey,
        array $request,
        bool $success,
        int $durationMs,
        ?string $error = null
    ): void {
        if (! $apiKey?->workspace_id) {
            return;
        }

        try {
            $dispatcher = new McpWebhookDispatcher;
            $dispatcher->dispatchToolExecuted(
                workspaceId: $apiKey->workspace_id,
                serverId: $request['server'],
                toolName: $request['tool'],
                arguments: $request['arguments'] ?? [],
                success: $success,
                durationMs: $durationMs,
                errorMessage: $error
            );
        } catch (\Throwable $e) {
            // Don't let webhook failures affect API response
            report($e);
        }
    }

    /**
     * Log tool call for analytics.
     */
    protected function logToolCall(
        ?ApiKey $apiKey,
        array $request,
        mixed $result,
        int $durationMs,
        bool $success,
        ?string $error = null
    ): void {
        McpToolCall::log(
            serverId: $request['server'],
            toolName: $request['tool'],
            params: $request['arguments'] ?? [],
            success: $success,
            durationMs: $durationMs,
            errorMessage: $error,
            workspaceId: $apiKey?->workspace_id
        );
    }

    // Registry loading methods (shared with McpRegistryController)

    protected function loadRegistry(): array
    {
        return Cache::remember('mcp:registry', 600, function () {
            $path = resource_path('mcp/registry.yaml');

            return file_exists($path) ? Yaml::parseFile($path) : ['servers' => []];
        });
    }

    protected function loadServerFull(string $id): ?array
    {
        return Cache::remember("mcp:server:{$id}", 600, function () use ($id) {
            $path = resource_path("mcp/servers/{$id}.yaml");

            return file_exists($path) ? Yaml::parseFile($path) : null;
        });
    }

    protected function loadServerSummary(string $id): ?array
    {
        $server = $this->loadServerFull($id);
        if (! $server) {
            return null;
        }

        return [
            'id' => $server['id'],
            'name' => $server['name'],
            'tagline' => $server['tagline'] ?? '',
            'status' => $server['status'] ?? 'available',
            'tool_count' => count($server['tools'] ?? []),
            'resource_count' => count($server['resources'] ?? []),
        ];
    }
}
