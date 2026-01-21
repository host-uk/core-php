<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Symfony\Component\Yaml\Yaml;

/**
 * MCP Tool Testing Playground
 *
 * A browser-based UI for testing MCP tool calls.
 * Allows users to select a server, pick a tool, and execute it with custom parameters.
 */
#[Layout('mcp::layouts.app')]
class McpPlayground extends Component
{
    public string $selectedServer = '';

    public string $selectedTool = '';

    public string $inputJson = '{}';

    public ?array $lastResult = null;

    public ?string $lastError = null;

    public bool $isExecuting = false;

    public int $executionTime = 0;

    public array $servers = [];

    public array $tools = [];

    protected $rules = [
        'selectedServer' => 'required|string',
        'selectedTool' => 'required|string',
        'inputJson' => 'required|json',
    ];

    public function mount(): void
    {
        $this->loadServers();

        if (! empty($this->servers)) {
            $this->selectedServer = $this->servers[0]['id'];
            $this->loadTools();
        }
    }

    public function updatedSelectedServer(): void
    {
        $this->loadTools();
        $this->selectedTool = '';
        $this->inputJson = '{}';
        $this->lastResult = null;
        $this->lastError = null;
    }

    public function updatedSelectedTool(): void
    {
        $this->prefillParameters();
        $this->lastResult = null;
        $this->lastError = null;
    }

    public function execute(): void
    {
        $this->validate();

        $this->isExecuting = true;
        $this->lastResult = null;
        $this->lastError = null;

        try {
            $params = json_decode($this->inputJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->lastError = 'Invalid JSON: '.json_last_error_msg();

                return;
            }

            // Mock execution for demo
            $this->lastResult = [
                'status' => 'success',
                'message' => 'Tool execution simulated',
                'params' => $params,
            ];
            $this->executionTime = rand(50, 200);

        } catch (\Exception $e) {
            $this->lastError = $e->getMessage();
        } finally {
            $this->isExecuting = false;
        }
    }

    public function formatJson(): void
    {
        try {
            $decoded = json_decode($this->inputJson, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->inputJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            }
        } catch (\Exception $e) {
            // Ignore formatting errors
        }
    }

    protected function loadServers(): void
    {
        $registry = $this->loadRegistry();

        $this->servers = collect($registry['servers'] ?? [])
            ->map(fn ($ref) => $this->loadServerYaml($ref['id']))
            ->filter()
            ->map(fn ($server) => [
                'id' => $server['id'],
                'name' => $server['name'],
                'tagline' => $server['tagline'] ?? '',
                'tool_count' => count($server['tools'] ?? []),
            ])
            ->values()
            ->all();
    }

    protected function loadTools(): void
    {
        if (empty($this->selectedServer)) {
            $this->tools = [];

            return;
        }

        $server = $this->loadServerYaml($this->selectedServer);

        $this->tools = collect($server['tools'] ?? [])
            ->map(fn ($tool) => [
                'name' => $tool['name'],
                'purpose' => $tool['purpose'] ?? '',
                'parameters' => $tool['parameters'] ?? [],
            ])
            ->values()
            ->all();
    }

    protected function prefillParameters(): void
    {
        if (empty($this->selectedTool)) {
            $this->inputJson = '{}';

            return;
        }

        $tool = collect($this->tools)->firstWhere('name', $this->selectedTool);

        if (! $tool || empty($tool['parameters'])) {
            $this->inputJson = '{}';

            return;
        }

        $params = [];
        foreach ($tool['parameters'] as $paramName => $paramDef) {
            if (is_array($paramDef)) {
                $type = $paramDef['type'] ?? 'string';
                $default = $paramDef['default'] ?? null;
                $required = $paramDef['required'] ?? false;

                if ($default !== null) {
                    $params[$paramName] = $default;
                } elseif ($required) {
                    $params[$paramName] = match ($type) {
                        'boolean' => false,
                        'integer', 'number' => 0,
                        'array' => [],
                        default => '',
                    };
                }
            }
        }

        $this->inputJson = json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function loadRegistry(): array
    {
        return Cache::remember('mcp:registry', 0, function () {
            $path = resource_path('mcp/registry.yaml');
            if (! file_exists($path)) {
                return ['servers' => []];
            }

            return Yaml::parseFile($path);
        });
    }

    protected function loadServerYaml(string $id): ?array
    {
        $path = resource_path("mcp/servers/{$id}.yaml");
        if (! file_exists($path)) {
            return null;
        }

        return Yaml::parseFile($path);
    }

    public function render()
    {
        return view('mcp::web.mcp-playground');
    }
}
