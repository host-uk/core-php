<?php

namespace Core\Mod\Web\Jobs;

use Core\Mod\Web\Models\Submission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Send notification for a biolink form submission.
 *
 * Supports:
 * - Webhook POST to configured URL
 * - Email notification to configured address
 */
class SendSubmissionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times to retry.
     */
    public int $tries = 3;

    /**
     * Backoff in seconds between retries.
     */
    public array $backoff = [30, 60, 120];

    public function __construct(
        public Submission $submission
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $submission = $this->submission->fresh(['block', 'biolink']);

        if (! $submission || ! $submission->block) {
            return;
        }

        $webhookUrl = $submission->block->getSetting('webhook_url');
        $notifyEmail = $submission->block->getSetting('notify_email');

        $success = true;

        // Send webhook
        if ($webhookUrl) {
            $success = $this->sendWebhook($webhookUrl, $submission) && $success;
        }

        // Send email
        if ($notifyEmail) {
            $success = $this->sendEmail($notifyEmail, $submission) && $success;
        }

        if ($success) {
            $submission->markNotified();
        }
    }

    /**
     * Send webhook notification.
     */
    protected function sendWebhook(string $url, Submission $submission): bool
    {
        try {
            $payload = [
                'event' => 'bio.submission',
                'type' => $submission->type,
                'data' => $submission->data->toArray(),
                'block_id' => $submission->block_id,
                'biolink_id' => $submission->biolink_id,
                'biolink_url' => $submission->biolink->url ?? null,
                'submitted_at' => $submission->created_at->toIso8601String(),
            ];

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HostUK-BioLink/1.0',
                    'X-BioLink-Event' => 'submission',
                ])
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('BioLink submission webhook failed', [
                    'url' => $url,
                    'status' => $response->status(),
                    'submission_id' => $submission->id,
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink submission webhook error', [
                'url' => $url,
                'error' => $e->getMessage(),
                'submission_id' => $submission->id,
            ]);

            return false;
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmail(string $email, Submission $submission): bool
    {
        try {
            $biolinkUrl = $submission->biolink->url ?? 'Unknown';
            $blockType = $submission->block->type ?? 'Unknown';

            $subject = match ($submission->type) {
                'email' => "New email subscriber on {$biolinkUrl}",
                'phone' => "New phone subscriber on {$biolinkUrl}",
                'contact' => "New contact message on {$biolinkUrl}",
                default => "New submission on {$biolinkUrl}",
            };

            $data = $submission->data->toArray();
            $lines = [];

            if (! empty($data['name'])) {
                $lines[] = "Name: {$data['name']}";
            }
            if (! empty($data['email'])) {
                $lines[] = "Email: {$data['email']}";
            }
            if (! empty($data['phone'])) {
                $lines[] = "Phone: {$data['phone']}";
            }
            if (! empty($data['message'])) {
                $lines[] = "Message:\n{$data['message']}";
            }

            $body = implode("\n", $lines);
            $body .= "\n\n---\nSubmitted: {$submission->created_at->format('j M Y, g:ia')}";
            $body .= "\nBiolink: {$biolinkUrl}";

            Mail::raw($body, function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink submission email error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'submission_id' => $submission->id,
            ]);

            return false;
        }
    }
}
