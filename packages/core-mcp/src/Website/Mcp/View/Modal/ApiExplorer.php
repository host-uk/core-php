<?php

declare(strict_types=1);

namespace Core\Website\Mcp\View\Modal;

use Livewire\Component;

/**
 * Interactive API Explorer
 *
 * Provides a browser-based interface for exploring API endpoints
 * with live code snippets in multiple languages and real-time testing.
 */
class ApiExplorer extends Component
{
    public string $apiKey = '';

    public string $selectedEndpoint = '';

    public string $selectedLanguage = 'curl';

    public string $baseUrl = 'https://api.host.uk.com';

    // Request configuration
    public string $method = 'GET';

    public string $path = '/api/v1/workspaces';

    public string $bodyJson = '{}';

    // Response state
    public ?array $response = null;

    public ?string $error = null;

    public bool $isLoading = false;

    public int $responseTime = 0;

    // Available endpoints for quick selection
    public array $endpoints = [
        [
            'name' => 'List Workspaces',
            'method' => 'GET',
            'path' => '/api/v1/workspaces',
            'description' => 'Get all workspaces for the authenticated user',
            'body' => null,
        ],
        [
            'name' => 'Create Workspace',
            'method' => 'POST',
            'path' => '/api/v1/workspaces',
            'description' => 'Create a new workspace',
            'body' => ['name' => 'My Workspace', 'description' => 'A new workspace'],
        ],
    ];

    public function mount(): void
    {
        if (! empty($this->endpoints)) {
            $this->selectEndpoint(0);
        }
    }

    public function selectEndpoint(int $index): void
    {
        if (! isset($this->endpoints[$index])) {
            return;
        }

        $endpoint = $this->endpoints[$index];
        $this->selectedEndpoint = (string) $index;
        $this->method = $endpoint['method'];
        $this->path = $endpoint['path'];
        $this->bodyJson = $endpoint['body']
            ? json_encode($endpoint['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : '{}';
        $this->response = null;
        $this->error = null;
    }

    public function getCodeSnippet(): string
    {
        $key = $this->apiKey ?: 'YOUR_API_KEY';
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($this->path, '/');

        return match ($this->selectedLanguage) {
            'curl' => $this->getCurlSnippet($url, $key),
            'php' => $this->getPhpSnippet($url, $key),
            'javascript' => $this->getJsSnippet($url, $key),
            default => $this->getCurlSnippet($url, $key),
        };
    }

    protected function getCurlSnippet(string $url, string $key): string
    {
        $cmd = "curl -X {$this->method} \"{$url}\" \\\n";
        $cmd .= "  -H \"Authorization: Bearer {$key}\" \\\n";
        $cmd .= "  -H \"Content-Type: application/json\" \\\n";
        $cmd .= "  -H \"Accept: application/json\"";

        if (in_array($this->method, ['POST', 'PUT', 'PATCH']) && $this->bodyJson !== '{}') {
            $cmd .= " \\\n  -d '{$this->bodyJson}'";
        }

        return $cmd;
    }

    protected function getPhpSnippet(string $url, string $key): string
    {
        return <<<PHP
\$response = Http::withToken('{$key}')
    ->acceptJson()
    ->{$this->method}('{$url}');
PHP;
    }

    protected function getJsSnippet(string $url, string $key): string
    {
        return <<<JS
const response = await fetch('{$url}', {
  method: '{$this->method}',
  headers: {
    'Authorization': 'Bearer {$key}',
    'Content-Type': 'application/json',
  },
});
JS;
    }

    public function render()
    {
        return view('mcp::web.api-explorer', [
            'languages' => ['curl', 'php', 'javascript'],
            'snippet' => $this->getCodeSnippet(),
        ]);
    }
}
