<?php

declare(strict_types=1);

namespace Core\Mod\Web\Jobs;

use Core\Mod\Web\Models\NotificationHandler;
use Core\Mod\Web\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued job to send a biolink notification.
 *
 * This job is dispatched when a biolink event occurs and handlers are configured.
 * It processes notifications asynchronously to avoid blocking user requests.
 */
class SendBioLinkNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public NotificationHandler $handler,
        public array $payload
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        // Refresh the handler to get current state
        $this->handler->refresh();

        // Skip if handler is no longer enabled
        if (! $this->handler->is_enabled) {
            Log::info('BioLink notification skipped - handler disabled', [
                'handler_id' => $this->handler->id,
            ]);

            return;
        }

        // Skip if handler is auto-disabled due to failures
        if ($this->handler->isAutoDisabled()) {
            Log::info('BioLink notification skipped - handler auto-disabled', [
                'handler_id' => $this->handler->id,
                'consecutive_failures' => $this->handler->consecutive_failures,
            ]);

            return;
        }

        // Attempt to send the notification
        $success = $notificationService->send($this->handler, $this->payload);

        if (! $success && $this->attempts() < $this->tries) {
            // Will be retried by the queue system
            Log::info('BioLink notification will be retried', [
                'handler_id' => $this->handler->id,
                'attempt' => $this->attempts(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BioLink notification job failed permanently', [
            'handler_id' => $this->handler->id,
            'handler_type' => $this->handler->type,
            'biolink_id' => $this->handler->biolink_id,
            'event' => $this->payload['event'] ?? 'unknown',
            'error' => $exception->getMessage(),
        ]);

        // Record the failure on the handler
        $this->handler->recordFailure();
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'biolink-notification',
            'handler:'.$this->handler->id,
            'type:'.$this->handler->type,
            'biolink:'.$this->handler->biolink_id,
        ];
    }
}
