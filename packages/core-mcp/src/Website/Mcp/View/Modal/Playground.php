<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\Yaml\Yaml;

/**
 * MCP Playground - interactive tool testing in the browser.
 */
#[Layout('mcp::layouts.app')]
class Playground extends Component
{
    public string $selectedServer = '';

    public string $selectedTool = '';

    public array $arguments = [];

    public string $response = '';

    public bool $loading = false;

    public string $apiKey = '';

    public ?string $error = null;

    public array $servers = [];

    public array $tools = [];

    public ?array $toolSchema = null;

    public function mount(): void
    {
        $this->loadServers();
    }

    public function loadServers(): void
    {
        try {
            $registry = $this->loadRegistry();
            $this->servers = collect($registry['servers'] ?? [])
                ->map(fn ($ref) => $this->loadServerSummary($ref['id']))
                ->filter()
                ->values()
                ->toArray();
        } catch (\Throwable $e) {
            $this->error = 'Failed to load servers';
            $this->servers = [];
        }
    }

    public function updatedSelectedServer(): void
    {
        $this->error = null;
        $this->selectedTool = '';
        $this->toolSchema = null;
        $this->arguments = [];
        $this->response = '';

        if (! $this->selectedServer) {
            $this->tools = [];

            return;
        }

        try {
            $server = $this->loadServerFull($this->selectedServer);
            $this->tools = $server['tools'] ?? [];
        } catch (\Throwable $e) {
            $this->error = 'Failed to load server tools';
            $this->tools = [];
        }
    }

    public function updatedSelectedTool(): void
    {
        $this->error = null;
        $this->arguments = [];
        $this->response = '';

        if (! $this->selectedTool) {
            $this->toolSchema = null;

            return;
        }

        try {
            $this->toolSchema = collect($this->tools)->firstWhere('name', $this->selectedTool);

            $params = $this->toolSchema['inputSchema']['properties'] ?? [];
            foreach ($params as $name => $schema) {
                $this->arguments[$name] = $schema['default'] ?? '';
            }
        } catch (\Throwable $e) {
            $this->error = 'Failed to load tool schema';
            $this->toolSchema = null;
        }
    }

    public function execute(): void
    {
        if (! $this->selectedServer || ! $this->selectedTool) {
            return;
        }

        $this->loading = true;
        $this->response = '';
        $this->error = null;

        try {
            $args = array_filter($this->arguments, fn ($v) => $v !== '' && $v !== null);

            $payload = [
                'server' => $this->selectedServer,
                'tool' => $this->selectedTool,
                'arguments' => $args,
            ];

            // Show request format (actual execution requires API key + backend)
            $this->response = json_encode([
                'request' => $payload,
                'note' => 'Add an API key above to execute this request live.',
                'curl' => sprintf(
                    "curl -X POST %s/api/v1/mcp/tools/call \\\n  -H \"Authorization: Bearer YOUR_API_KEY\" \\\n  -H \"Content-Type: application/json\" \\\n  -d '%s'",
                    config('app.url'),
                    json_encode($payload, JSON_UNESCAPED_SLASHES)
                ),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            $this->response = json_encode([
                'error' => $e->getMessage(),
            ], JSON_PRETTY_PRINT);
        } finally {
            $this->loading = false;
        }
    }

    public function render()
    {
        return view('mcp::web.playground', [
            'isAuthenticated' => auth()->check(),
            'workspace' => null,
        ]);
    }

    protected function loadRegistry(): array
    {
        $path = resource_path('mcp/registry.yaml');

        return file_exists($path) ? Yaml::parseFile($path) : ['servers' => []];
    }

    protected function loadServerFull(string $id): ?array
    {
        $path = resource_path("mcp/servers/{$id}.yaml");

        return file_exists($path) ? Yaml::parseFile($path) : null;
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
        ];
    }
}
