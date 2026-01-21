<?php

namespace Core\Mod\Web\Mcp;

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

abstract class BaseTool extends Tool
{
    /**
     * Get the default workspace for a user.
     */
    protected function getWorkspaceForUser(?int $userId): ?Workspace
    {
        if (! $userId) {
            return null;
        }

        $user = User::find($userId);

        return $user?->defaultHostWorkspace();
    }

    /**
     * Return a JSON response.
     */
    protected function json(mixed $data): Response
    {
        return Response::text(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Return an error response.
     */
    protected function error(string $message, array $details = []): Response
    {
        $response = ['error' => $message];

        if (! empty($details)) {
            $response['details'] = $details;
        }

        return $this->json($response);
    }
}
