<?php

declare(strict_types=1);

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Jobs\SendBioLinkNotification;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\NotificationHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Service for dispatching biolink notifications.
 *
 * Handles notification delivery to various channels:
 * - Webhooks with optional HMAC signing
 * - Email notifications
 * - Slack webhooks
 * - Discord webhooks
 * - Telegram Bot API
 */
class NotificationService
{
    /**
     * User agent for outgoing HTTP requests.
     */
    protected const USER_AGENT = 'BioHost-Notifications/1.0 (Host UK)';

    /**
     * HTTP timeout in seconds.
     */
    protected const HTTP_TIMEOUT = 10;

    /**
     * Dispatch notifications for a biolink event.
     *
     * Finds all active handlers configured for the event and queues notifications.
     */
    public function dispatch(Page $biolink, string $event, array $data = []): int
    {
        $handlers = $biolink->getActiveHandlersForEvent($event);

        if ($handlers->isEmpty()) {
            return 0;
        }

        $payload = $this->buildPayload($biolink, $event, $data);

        foreach ($handlers as $handler) {
            SendBioLinkNotification::dispatch($handler, $payload);
        }

        return $handlers->count();
    }

    /**
     * Build the notification payload.
     */
    public function buildPayload(Page $biolink, string $event, array $data = []): array
    {
        return [
            'event' => $event,
            'biolink' => [
                'id' => $biolink->id,
                'url' => $biolink->url,
                'full_url' => $biolink->full_url,
                'type' => $biolink->type,
            ],
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Send a notification via the appropriate channel.
     *
     * @return bool Whether the notification was sent successfully
     */
    public function send(NotificationHandler $handler, array $payload): bool
    {
        $success = match ($handler->type) {
            NotificationHandler::TYPE_WEBHOOK => $this->sendWebhook($handler, $payload),
            NotificationHandler::TYPE_EMAIL => $this->sendEmail($handler, $payload),
            NotificationHandler::TYPE_SLACK => $this->sendSlack($handler, $payload),
            NotificationHandler::TYPE_DISCORD => $this->sendDiscord($handler, $payload),
            NotificationHandler::TYPE_TELEGRAM => $this->sendTelegram($handler, $payload),
            default => false,
        };

        if ($success) {
            $handler->recordSuccess();
        } else {
            $handler->recordFailure();
        }

        return $success;
    }

    /**
     * Send a webhook notification.
     */
    public function sendWebhook(NotificationHandler $handler, array $payload): bool
    {
        $url = $handler->getSetting('url');
        $secret = $handler->getSetting('secret');

        if (empty($url)) {
            Log::warning('BioLink notification webhook missing URL', [
                'handler_id' => $handler->id,
            ]);

            return false;
        }

        try {
            $jsonPayload = json_encode($payload);
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => self::USER_AGENT,
                'X-BioHost-Event' => $payload['event'] ?? 'unknown',
                'X-BioHost-Delivery' => Str::uuid()->toString(),
            ];

            // Add HMAC signature if secret is configured
            if (! empty($secret)) {
                $signature = hash_hmac('sha256', $jsonPayload, $secret);
                $headers['X-BioHost-Signature'] = 'sha256='.$signature;
            }

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders($headers)
                ->withBody($jsonPayload, 'application/json')
                ->post($url);

            if (! $response->successful()) {
                Log::warning('BioLink webhook notification failed', [
                    'handler_id' => $handler->id,
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink webhook notification error', [
                'handler_id' => $handler->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send an email notification.
     */
    public function sendEmail(NotificationHandler $handler, array $payload): bool
    {
        $recipients = $handler->getSetting('recipients', []);
        $subjectPrefix = $handler->getSetting('subject_prefix', 'BioHost');

        if (empty($recipients)) {
            Log::warning('BioLink notification email missing recipients', [
                'handler_id' => $handler->id,
            ]);

            return false;
        }

        // Ensure recipients is an array
        if (is_string($recipients)) {
            $recipients = array_map('trim', explode(',', $recipients));
        }

        try {
            $event = $payload['event'] ?? 'notification';
            $biolinkUrl = $payload['biolink']['url'] ?? 'unknown';

            $subject = match ($event) {
                'click' => "[{$subjectPrefix}] New page view on /{$biolinkUrl}",
                'block_click' => "[{$subjectPrefix}] Block clicked on /{$biolinkUrl}",
                'form_submit' => "[{$subjectPrefix}] New form submission on /{$biolinkUrl}",
                'payment' => "[{$subjectPrefix}] Payment received on /{$biolinkUrl}",
                default => "[{$subjectPrefix}] Notification for /{$biolinkUrl}",
            };

            $body = $this->formatEmailBody($payload);

            foreach ($recipients as $email) {
                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                Mail::raw($body, function ($message) use ($email, $subject) {
                    $message->to($email)
                        ->subject($subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink email notification error', [
                'handler_id' => $handler->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Format the email body from payload.
     */
    protected function formatEmailBody(array $payload): string
    {
        $event = $payload['event'] ?? 'notification';
        $biolink = $payload['biolink'] ?? [];
        $data = $payload['data'] ?? [];
        $timestamp = $payload['timestamp'] ?? now()->toIso8601String();

        $lines = [];

        // Event description
        $lines[] = match ($event) {
            'click' => 'A visitor viewed your BioHost page.',
            'block_click' => 'A visitor clicked a block on your BioHost page.',
            'form_submit' => 'A visitor submitted a form on your BioHost page.',
            'payment' => 'You received a payment on your BioHost page.',
            default => 'An event occurred on your BioHost page.',
        };

        $lines[] = '';
        $lines[] = '---';
        $lines[] = '';

        // Biolink details
        if (! empty($biolink['full_url'])) {
            $lines[] = "Page: {$biolink['full_url']}";
        }

        // Event-specific data
        if (! empty($data)) {
            $lines[] = '';
            $lines[] = 'Details:';

            if (! empty($data['country_code'])) {
                $lines[] = "  Country: {$data['country_code']}";
            }
            if (! empty($data['device_type'])) {
                $lines[] = "  Device: {$data['device_type']}";
            }
            if (! empty($data['referrer'])) {
                $lines[] = "  Referrer: {$data['referrer']}";
            }
            if (! empty($data['block_type'])) {
                $lines[] = "  Block Type: {$data['block_type']}";
            }

            // Form submission data
            if (! empty($data['submission'])) {
                $lines[] = '';
                $lines[] = 'Submission Data:';
                foreach ($data['submission'] as $key => $value) {
                    if (is_scalar($value)) {
                        $lines[] = "  {$key}: {$value}";
                    }
                }
            }

            // Payment data
            if (! empty($data['amount'])) {
                $lines[] = "  Amount: {$data['currency']}{$data['amount']}";
            }
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = "Timestamp: {$timestamp}";
        $lines[] = '';
        $lines[] = 'This notification was sent by BioHost (Host UK).';

        return implode("\n", $lines);
    }

    /**
     * Send a Slack notification.
     */
    public function sendSlack(NotificationHandler $handler, array $payload): bool
    {
        $webhookUrl = $handler->getSetting('webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('BioLink notification Slack missing webhook URL', [
                'handler_id' => $handler->id,
            ]);

            return false;
        }

        try {
            $event = $payload['event'] ?? 'notification';
            $biolink = $payload['biolink'] ?? [];
            $data = $payload['data'] ?? [];

            $emoji = match ($event) {
                'click' => ':eyes:',
                'block_click' => ':point_up:',
                'form_submit' => ':incoming_envelope:',
                'payment' => ':moneybag:',
                default => ':bell:',
            };

            $text = match ($event) {
                'click' => "New page view on */{$biolink['url']}*",
                'block_click' => "Block clicked on */{$biolink['url']}*",
                'form_submit' => "New form submission on */{$biolink['url']}*",
                'payment' => "Payment received on */{$biolink['url']}*",
                default => "Notification for */{$biolink['url']}*",
            };

            $slackPayload = [
                'text' => "{$emoji} {$text}",
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => "{$emoji} {$text}",
                        ],
                    ],
                ],
            ];

            // Add context block with details
            $contextElements = [];

            if (! empty($biolink['full_url'])) {
                $contextElements[] = [
                    'type' => 'mrkdwn',
                    'text' => "<{$biolink['full_url']}|View Page>",
                ];
            }

            if (! empty($data['country_code'])) {
                $contextElements[] = [
                    'type' => 'plain_text',
                    'text' => ":flag-{$data['country_code']}: {$data['country_code']}",
                ];
            }

            if (! empty($data['device_type'])) {
                $deviceIcon = match ($data['device_type']) {
                    'mobile' => ':iphone:',
                    'tablet' => ':ipad:',
                    'desktop' => ':computer:',
                    default => ':globe_with_meridians:',
                };
                $contextElements[] = [
                    'type' => 'plain_text',
                    'text' => "{$deviceIcon} {$data['device_type']}",
                ];
            }

            if (! empty($contextElements)) {
                $slackPayload['blocks'][] = [
                    'type' => 'context',
                    'elements' => $contextElements,
                ];
            }

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ])
                ->post($webhookUrl, $slackPayload);

            if (! $response->successful()) {
                Log::warning('BioLink Slack notification failed', [
                    'handler_id' => $handler->id,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink Slack notification error', [
                'handler_id' => $handler->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a Discord notification.
     */
    public function sendDiscord(NotificationHandler $handler, array $payload): bool
    {
        $webhookUrl = $handler->getSetting('webhook_url');

        if (empty($webhookUrl)) {
            Log::warning('BioLink notification Discord missing webhook URL', [
                'handler_id' => $handler->id,
            ]);

            return false;
        }

        try {
            $event = $payload['event'] ?? 'notification';
            $biolink = $payload['biolink'] ?? [];
            $data = $payload['data'] ?? [];

            $color = match ($event) {
                'click' => 0x3B82F6, // Blue
                'block_click' => 0x8B5CF6, // Purple
                'form_submit' => 0x10B981, // Green
                'payment' => 0xF59E0B, // Amber
                default => 0x6B7280, // Grey
            };

            $title = match ($event) {
                'click' => 'New Page View',
                'block_click' => 'Block Clicked',
                'form_submit' => 'Form Submission',
                'payment' => 'Payment Received',
                default => 'Notification',
            };

            $description = match ($event) {
                'click' => "A visitor viewed your BioHost page **/{$biolink['url']}**",
                'block_click' => "A visitor clicked a block on **/{$biolink['url']}**",
                'form_submit' => "A new form submission on **/{$biolink['url']}**",
                'payment' => "You received a payment on **/{$biolink['url']}**",
                default => "Event on **/{$biolink['url']}**",
            };

            $fields = [];

            if (! empty($data['country_code'])) {
                $fields[] = [
                    'name' => 'Country',
                    'value' => $data['country_code'],
                    'inline' => true,
                ];
            }

            if (! empty($data['device_type'])) {
                $fields[] = [
                    'name' => 'Device',
                    'value' => ucfirst($data['device_type']),
                    'inline' => true,
                ];
            }

            if (! empty($data['referrer'])) {
                $fields[] = [
                    'name' => 'Referrer',
                    'value' => $data['referrer'],
                    'inline' => true,
                ];
            }

            $discordPayload = [
                'embeds' => [
                    [
                        'title' => $title,
                        'description' => $description,
                        'color' => $color,
                        'fields' => $fields,
                        'url' => $biolink['full_url'] ?? null,
                        'timestamp' => $payload['timestamp'] ?? now()->toIso8601String(),
                        'footer' => [
                            'text' => 'BioHost by Host UK',
                        ],
                    ],
                ],
            ];

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => self::USER_AGENT,
                ])
                ->post($webhookUrl, $discordPayload);

            if (! $response->successful()) {
                Log::warning('BioLink Discord notification failed', [
                    'handler_id' => $handler->id,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink Discord notification error', [
                'handler_id' => $handler->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a Telegram notification.
     */
    public function sendTelegram(NotificationHandler $handler, array $payload): bool
    {
        $botToken = $handler->getSetting('bot_token');
        $chatId = $handler->getSetting('chat_id');

        if (empty($botToken) || empty($chatId)) {
            Log::warning('BioLink notification Telegram missing bot_token or chat_id', [
                'handler_id' => $handler->id,
            ]);

            return false;
        }

        try {
            $event = $payload['event'] ?? 'notification';
            $biolink = $payload['biolink'] ?? [];
            $data = $payload['data'] ?? [];

            $emoji = match ($event) {
                'click' => "\xF0\x9F\x91\x80", // Eyes
                'block_click' => "\xF0\x9F\x91\x86", // Point up
                'form_submit' => "\xF0\x9F\x93\xA9", // Envelope
                'payment' => "\xF0\x9F\x92\xB0", // Money bag
                default => "\xF0\x9F\x94\x94", // Bell
            };

            $title = match ($event) {
                'click' => 'New Page View',
                'block_click' => 'Block Clicked',
                'form_submit' => 'Form Submission',
                'payment' => 'Payment Received',
                default => 'Notification',
            };

            $lines = [
                "{$emoji} <b>{$title}</b>",
                '',
            ];

            $lines[] = match ($event) {
                'click' => "A visitor viewed your BioHost page <b>/{$biolink['url']}</b>",
                'block_click' => "A visitor clicked a block on <b>/{$biolink['url']}</b>",
                'form_submit' => "New form submission on <b>/{$biolink['url']}</b>",
                'payment' => "Payment received on <b>/{$biolink['url']}</b>",
                default => "Event on <b>/{$biolink['url']}</b>",
            };

            // Add details
            if (! empty($data['country_code'])) {
                $lines[] = "\xF0\x9F\x8C\x8D Country: {$data['country_code']}";
            }
            if (! empty($data['device_type'])) {
                $lines[] = "\xF0\x9F\x93\xB1 Device: ".ucfirst($data['device_type']);
            }
            if (! empty($data['referrer'])) {
                $lines[] = "\xF0\x9F\x94\x97 Referrer: {$data['referrer']}";
            }

            if (! empty($biolink['full_url'])) {
                $lines[] = '';
                $lines[] = "<a href=\"{$biolink['full_url']}\">View Page</a>";
            }

            $text = implode("\n", $lines);

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $response = Http::timeout(self::HTTP_TIMEOUT)
                ->withHeaders([
                    'User-Agent' => self::USER_AGENT,
                ])
                ->post($url, [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);

            if (! $response->successful()) {
                Log::warning('BioLink Telegram notification failed', [
                    'handler_id' => $handler->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            $result = $response->json();

            if (! ($result['ok'] ?? false)) {
                Log::warning('BioLink Telegram API error', [
                    'handler_id' => $handler->id,
                    'error' => $result['description'] ?? 'Unknown error',
                ]);

                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BioLink Telegram notification error', [
                'handler_id' => $handler->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send a test notification to verify handler configuration.
     */
    public function sendTest(NotificationHandler $handler): bool
    {
        $payload = [
            'event' => 'test',
            'biolink' => [
                'id' => $handler->biolink_id,
                'url' => $handler->biolink?->url ?? 'test-page',
                'full_url' => $handler->biolink?->full_url ?? 'https://link.host.uk.com/test-page',
                'type' => 'biolink',
            ],
            'data' => [
                'message' => 'This is a test notification from BioHost.',
                'country_code' => 'GB',
                'device_type' => 'desktop',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->send($handler, $payload);
    }
}
