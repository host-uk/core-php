<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when MCP tools are being registered.
 *
 * Modules listen to this event to register their MCP tool handlers.
 * Each handler class must implement the McpToolHandler interface
 * (to be provided by the consuming application).
 *
 * Fired at MCP server startup (stdio transport) or when MCP routes
 * are accessed (HTTP transport).
 */
class McpToolsRegistering extends LifecycleEvent
{
    protected array $handlers = [];

    /**
     * Register an MCP tool handler class.
     */
    public function handler(string $handlerClass): void
    {
        $this->handlers[] = $handlerClass;
    }

    /**
     * Get all registered handler classes.
     */
    public function handlers(): array
    {
        return $this->handlers;
    }
}
