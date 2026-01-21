<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\NotificationHandler;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\NotificationService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class NotificationTools extends BaseBioTool
{
    protected string $name = 'notification_tools';

    protected string $description = 'Manage notification handlers for bio pages';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');

        return match ($action) {
            'list' => $this->listNotificationHandlers($request->get('biolink_id')),
            'create' => $this->createNotificationHandler($request),
            'update' => $this->updateNotificationHandler($request),
            'delete' => $this->deleteNotificationHandler($request->get('handler_id')),
            'test' => $this->testNotificationHandler($request->get('handler_id')),
            default => $this->error('Invalid action', ['available' => ['list', 'create', 'update', 'delete', 'test']]),
        };
    }

    protected function listNotificationHandlers(?int $biolinkId): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $handlers = $biolink->notificationHandlers()->get();

        return $this->json([
            'handlers' => $handlers->map(fn (NotificationHandler $handler) => [
                'id' => $handler->id,
                'name' => $handler->name,
                'type' => $handler->type,
                'type_label' => $handler->getTypeLabel(),
                'events' => $handler->events,
                'is_enabled' => $handler->is_enabled,
                'trigger_count' => $handler->trigger_count,
                'last_triggered_at' => $handler->last_triggered_at?->toIso8601String(),
                'consecutive_failures' => $handler->consecutive_failures,
                'is_auto_disabled' => $handler->isAutoDisabled(),
                'created_at' => $handler->created_at->toIso8601String(),
            ]),
            'total' => $handlers->count(),
            'available_types' => NotificationHandler::getTypes(),
            'available_events' => NotificationHandler::getEvents(),
        ]);
    }

    protected function createNotificationHandler(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $type = $request->get('type');
        $name = $request->get('name');
        $events = $request->get('events', []);
        $settings = $request->get('settings', []);

        if (! $type || ! $name) {
            return $this->error('type and name are required');
        }

        if (! array_key_exists($type, NotificationHandler::getTypes())) {
            return $this->error('Invalid handler type', ['available_types' => array_keys(NotificationHandler::getTypes())]);
        }

        $handler = NotificationHandler::create([
            'biolink_id' => $biolinkId,
            'workspace_id' => $biolink->workspace_id,
            'name' => $name,
            'type' => $type,
            'events' => $events,
            'settings' => $settings,
            'is_enabled' => true,
        ]);

        $validationErrors = $handler->validateSettings();
        if (! empty($validationErrors)) {
            return $this->json([
                'ok' => true,
                'handler_id' => $handler->id,
                'warnings' => $validationErrors,
                'message' => 'Handler created but has configuration warnings.',
            ]);
        }

        return $this->json([
            'ok' => true,
            'handler_id' => $handler->id,
            'name' => $handler->name,
            'type' => $handler->type,
        ]);
    }

    protected function updateNotificationHandler(Request $request): Response
    {
        $handlerId = $request->get('handler_id');
        if (! $handlerId) {
            return $this->error('handler_id is required');
        }

        $handler = NotificationHandler::find($handlerId);
        if (! $handler) {
            return $this->error('Handler not found');
        }

        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = $request->get('name');
        }
        if ($request->has('events')) {
            $updateData['events'] = $request->get('events');
        }
        if ($request->has('settings')) {
            $currentSettings = $handler->settings?->toArray() ?? [];
            $updateData['settings'] = array_merge($currentSettings, $request->get('settings'));
        }
        if ($request->has('is_enabled')) {
            $updateData['is_enabled'] = (bool) $request->get('is_enabled');
        }

        if (($updateData['is_enabled'] ?? false) && $handler->isAutoDisabled()) {
            $handler->resetFailures();
        }

        $handler->update($updateData);

        return $this->json([
            'ok' => true,
            'handler_id' => $handler->id,
        ]);
    }

    protected function deleteNotificationHandler(?int $handlerId): Response
    {
        if (! $handlerId) {
            return $this->error('handler_id is required');
        }

        $handler = NotificationHandler::find($handlerId);
        if (! $handler) {
            return $this->error('Handler not found');
        }

        $name = $handler->name;
        $handler->delete();

        return $this->json([
            'ok' => true,
            'deleted_handler' => $name,
        ]);
    }

    protected function testNotificationHandler(?int $handlerId): Response
    {
        if (! $handlerId) {
            return $this->error('handler_id is required');
        }

        $handler = NotificationHandler::with('biolink')->find($handlerId);
        if (! $handler) {
            return $this->error('Handler not found');
        }

        $notificationService = app(NotificationService::class);
        $success = $notificationService->sendTest($handler);

        return $this->json([
            'ok' => $success,
            'handler_id' => $handlerId,
            'message' => $success
                ? 'Test notification sent successfully.'
                : 'Test notification failed. Check handler configuration.',
        ]);
    }
}
