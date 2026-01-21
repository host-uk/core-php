<?php

use Core\Mod\Web\Jobs\SendBioLinkNotification;
use Core\Mod\Web\Mail\BioReport;
use Core\Mod\Web\Models\NotificationHandler;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\NotificationService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

    $this->biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'test-biolink',
        'is_enabled' => true,
    ]);
});

describe('notification handler creation', function () {
    it('can create a webhook notification handler', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'My Webhook',
            'type' => 'webhook',
            'settings' => [
                'url' => 'https://example.com/webhook',
                'secret' => 'test-secret',
            ],
            'events' => ['click', 'form_submit'],
            'is_enabled' => true,
        ]);

        expect($handler)->toBeInstanceOf(NotificationHandler::class)
            ->and($handler->type)->toBe('webhook')
            ->and($handler->getSetting('url'))->toBe('https://example.com/webhook')
            ->and($handler->events)->toContain('click', 'form_submit');
    });

    it('can create an email notification handler', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Email Alerts',
            'type' => 'email',
            'settings' => [
                'recipients' => ['user@example.com', 'admin@example.com'],
                'subject_prefix' => 'BioHost',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->type)->toBe('email')
            ->and($handler->getSetting('recipients'))->toContain('user@example.com');
    });

    it('can create a slack notification handler', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Slack Notifications',
            'type' => 'slack',
            'settings' => [
                'webhook_url' => 'https://hooks.slack.com/services/xxx/yyy/zzz',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->type)->toBe('slack')
            ->and($handler->getSetting('webhook_url'))->toContain('hooks.slack.com');
    });

    it('can create a discord notification handler', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Discord Notifications',
            'type' => 'discord',
            'settings' => [
                'webhook_url' => 'https://discord.com/api/webhooks/123/abc',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->type)->toBe('discord')
            ->and($handler->getSetting('webhook_url'))->toContain('discord.com');
    });

    it('can create a telegram notification handler', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Telegram Notifications',
            'type' => 'telegram',
            'settings' => [
                'bot_token' => '123456789:ABCdefGHIjklMNO',
                'chat_id' => '-1001234567890',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->type)->toBe('telegram')
            ->and($handler->getSetting('bot_token'))->not->toBeEmpty()
            ->and($handler->getSetting('chat_id'))->not->toBeEmpty();
    });
});

describe('notification handler updates', function () {
    it('can update handler settings', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Original Name',
            'type' => 'webhook',
            'settings' => ['url' => 'https://old.example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $handler->update([
            'name' => 'Updated Name',
            'settings' => ['url' => 'https://new.example.com/webhook'],
            'events' => ['click', 'form_submit'],
        ]);

        expect($handler->fresh()->name)->toBe('Updated Name')
            ->and($handler->fresh()->getSetting('url'))->toBe('https://new.example.com/webhook')
            ->and($handler->fresh()->events)->toContain('form_submit');
    });

    it('can toggle enabled status', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Toggle Test',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->is_enabled)->toBeTrue();

        $handler->update(['is_enabled' => false]);
        expect($handler->fresh()->is_enabled)->toBeFalse();
    });
});

describe('notification handler deletion', function () {
    it('can soft delete a handler', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'To Delete',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $handlerId = $handler->id;
        $handler->delete();

        expect(NotificationHandler::find($handlerId))->toBeNull()
            ->and(NotificationHandler::withTrashed()->find($handlerId))->not->toBeNull();
    });
});

describe('webhook dispatch', function () {
    it('sends webhook with correct payload', function () {
        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Webhook Test',
            'type' => 'webhook',
            'settings' => [
                'url' => 'https://example.com/webhook',
                'secret' => 'test-secret',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $payload = $service->buildPayload($this->biolink, 'click', ['country_code' => 'GB']);

        $result = $service->sendWebhook($handler, $payload);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook'
                && $request->hasHeader('X-BioHost-Event', 'click')
                && $request->hasHeader('X-BioHost-Signature');
        });
    });

    it('handles webhook failure gracefully', function () {
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Server error'], 500),
        ]);

        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Failing Webhook',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $payload = $service->buildPayload($this->biolink, 'click', []);

        $result = $service->sendWebhook($handler, $payload);

        expect($result)->toBeFalse();
    });
});

describe('slack dispatch', function () {
    it('sends slack message with correct format', function () {
        Http::fake([
            'hooks.slack.com/*' => Http::response('ok', 200),
        ]);

        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Slack Test',
            'type' => 'slack',
            'settings' => ['webhook_url' => 'https://hooks.slack.com/services/xxx/yyy/zzz'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $payload = $service->buildPayload($this->biolink, 'click', ['country_code' => 'GB']);

        $result = $service->sendSlack($handler, $payload);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'hooks.slack.com')
                && isset($request->data()['text'])
                && isset($request->data()['blocks']);
        });
    });
});

describe('discord dispatch', function () {
    it('sends discord embed with correct format', function () {
        Http::fake([
            'discord.com/*' => Http::response(['id' => '123'], 200),
        ]);

        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Discord Test',
            'type' => 'discord',
            'settings' => ['webhook_url' => 'https://discord.com/api/webhooks/123/abc'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $payload = $service->buildPayload($this->biolink, 'click', ['country_code' => 'US']);

        $result = $service->sendDiscord($handler, $payload);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'discord.com')
                && isset($request->data()['embeds'])
                && count($request->data()['embeds']) > 0;
        });
    });
});

describe('telegram dispatch', function () {
    it('sends telegram message with correct format', function () {
        Http::fake([
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => []], 200),
        ]);

        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Telegram Test',
            'type' => 'telegram',
            'settings' => [
                'bot_token' => '123456789:ABCdefGHIjklMNO',
                'chat_id' => '-1001234567890',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $payload = $service->buildPayload($this->biolink, 'click', ['country_code' => 'DE']);

        $result = $service->sendTelegram($handler, $payload);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.telegram.org')
                && isset($request->data()['chat_id'])
                && isset($request->data()['text'])
                && $request->data()['parse_mode'] === 'HTML';
        });
    });
});

describe('email notification', function () {
    it('sends email notification to configured recipients', function () {
        // Use a mock that captures the Mail::raw calls
        $sentEmails = [];

        Mail::shouldReceive('raw')
            ->twice()
            ->andReturnUsing(function ($body, $callback) use (&$sentEmails) {
                $message = new class
                {
                    public $to;

                    public $subject;

                    public function to($email)
                    {
                        $this->to = $email;

                        return $this;
                    }

                    public function subject($subject)
                    {
                        $this->subject = $subject;

                        return $this;
                    }

                    public function from($address, $name)
                    {
                        return $this;
                    }
                };
                $callback($message);
                $sentEmails[] = $message->to;
            });

        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Email Test',
            'type' => 'email',
            'settings' => [
                'recipients' => ['user@example.com', 'admin@example.com'],
                'subject_prefix' => 'BioHost',
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $payload = $service->buildPayload($this->biolink, 'click', ['country_code' => 'GB']);

        $result = $service->sendEmail($handler, $payload);

        expect($result)->toBeTrue()
            ->and($sentEmails)->toContain('user@example.com', 'admin@example.com');
    });
});

describe('event filtering', function () {
    it('only triggers on configured events', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Click Only Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'], // Only click events
            'is_enabled' => true,
        ]);

        expect($handler->shouldTriggerFor('click'))->toBeTrue()
            ->and($handler->shouldTriggerFor('form_submit'))->toBeFalse()
            ->and($handler->shouldTriggerFor('payment'))->toBeFalse();
    });

    it('triggers on multiple configured events', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Multi-Event Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click', 'form_submit', 'payment'],
            'is_enabled' => true,
        ]);

        expect($handler->shouldTriggerFor('click'))->toBeTrue()
            ->and($handler->shouldTriggerFor('form_submit'))->toBeTrue()
            ->and($handler->shouldTriggerFor('payment'))->toBeTrue()
            ->and($handler->shouldTriggerFor('block_click'))->toBeFalse();
    });
});

describe('disabled handlers', function () {
    it('does not fire when disabled', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Disabled Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => false, // Disabled
        ]);

        expect($handler->shouldTriggerFor('click'))->toBeFalse();
    });

    it('auto-disables after consecutive failures', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Failing Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
            'consecutive_failures' => 0,
        ]);

        // Simulate 5 failures
        for ($i = 0; $i < 5; $i++) {
            $handler->recordFailure();
        }

        $handler->refresh();

        expect($handler->is_enabled)->toBeFalse()
            ->and($handler->isAutoDisabled())->toBeTrue()
            ->and($handler->shouldTriggerFor('click'))->toBeFalse();
    });

    it('can reset failures and re-enable', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Reset Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => false,
            'consecutive_failures' => 5,
        ]);

        expect($handler->isAutoDisabled())->toBeTrue();

        $handler->resetFailures();
        $handler->refresh();

        expect($handler->is_enabled)->toBeTrue()
            ->and($handler->consecutive_failures)->toBe(0)
            ->and($handler->shouldTriggerFor('click'))->toBeTrue();
    });
});

describe('notification dispatch', function () {
    it('dispatches notifications for a biolink event', function () {
        Queue::fake();

        // Create handlers
        NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Handler 1',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook1'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Handler 2',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook2'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);
        $count = $service->dispatch($this->biolink, 'click', ['country_code' => 'GB']);

        expect($count)->toBe(2);
        Queue::assertPushed(SendBioLinkNotification::class, 2);
    });

    it('only dispatches to handlers for the specific event', function () {
        Queue::fake();

        NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Click Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook1'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Form Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook2'],
            'events' => ['form_submit'],
            'is_enabled' => true,
        ]);

        $service = app(NotificationService::class);

        // Dispatch click event - only click handler should be triggered
        $clickCount = $service->dispatch($this->biolink, 'click', []);
        expect($clickCount)->toBe(1);

        // Dispatch form_submit event - only form handler should be triggered
        $formCount = $service->dispatch($this->biolink, 'form_submit', []);
        expect($formCount)->toBe(1);

        Queue::assertPushed(SendBioLinkNotification::class, 2);
    });
});

describe('email reports', function () {
    it('sends email report for biolink', function () {
        Mail::fake();

        // Set up email report settings
        $this->biolink->update([
            'email_report_settings' => [
                'enabled' => true,
                'frequency' => 'weekly',
                'recipients' => ['report@example.com'],
            ],
        ]);

        $report = new BioReport(
            biolink: $this->biolink,
            analytics: [
                'summary' => ['clicks' => 100, 'unique_clicks' => 75],
                'countries' => [
                    ['country_code' => 'GB', 'country_name' => 'United Kingdom', 'clicks' => 50],
                ],
                'devices' => [
                    ['device_type' => 'mobile', 'clicks' => 60],
                    ['device_type' => 'desktop', 'clicks' => 40],
                ],
                'referrers' => [],
            ],
            startDate: now()->subDays(7),
            endDate: now(),
            frequency: 'weekly'
        );

        Mail::to('report@example.com')->send($report);

        // Report implements ShouldQueue, so it gets queued
        Mail::assertQueued(BioReport::class, function ($mail) {
            return $mail->biolink->id === $this->biolink->id
                && $mail->frequency === 'weekly';
        });
    });
});

describe('handler validation', function () {
    it('validates webhook settings', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Valid Webhook',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $errors = $handler->validateSettings();
        expect($errors)->toBeEmpty();
    });

    it('detects missing webhook url', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Invalid Webhook',
            'type' => 'webhook',
            'settings' => [], // Missing URL
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $errors = $handler->validateSettings();
        expect($errors)->toHaveKey('url');
    });

    it('validates email recipients', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Invalid Email',
            'type' => 'email',
            'settings' => [
                'recipients' => ['not-an-email', 'also-invalid'],
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $errors = $handler->validateSettings();
        expect($errors)->toHaveKey('recipients');
    });

    it('validates telegram settings', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Invalid Telegram',
            'type' => 'telegram',
            'settings' => [
                'bot_token' => '123456789:ABC', // Missing chat_id
            ],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        $errors = $handler->validateSettings();
        expect($errors)->toHaveKey('chat_id');
    });
});

describe('handler relationships', function () {
    it('belongs to a biolink', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->biolink)->toBeInstanceOf(Page::class)
            ->and($handler->biolink->id)->toBe($this->biolink->id);
    });

    it('belongs to a workspace', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($handler->workspace)->toBeInstanceOf(Workspace::class)
            ->and($handler->workspace->id)->toBe($this->workspace->id);
    });

    it('can access handlers from biolink', function () {
        NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Handler 1',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook1'],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Handler 2',
            'type' => 'email',
            'settings' => ['recipients' => ['user@example.com']],
            'events' => ['click'],
            'is_enabled' => true,
        ]);

        expect($this->biolink->notificationHandlers)->toHaveCount(2);
    });
});

describe('success and failure tracking', function () {
    it('records successful triggers', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Tracking Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
            'trigger_count' => 0,
        ]);

        $handler->recordSuccess();
        $handler->recordSuccess();

        $handler->refresh();

        expect($handler->trigger_count)->toBe(2)
            ->and($handler->last_triggered_at)->not->toBeNull()
            ->and($handler->consecutive_failures)->toBe(0);
    });

    it('records failed triggers', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Failing Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
            'consecutive_failures' => 0,
        ]);

        $handler->recordFailure();
        $handler->recordFailure();

        $handler->refresh();

        expect($handler->consecutive_failures)->toBe(2)
            ->and($handler->last_failed_at)->not->toBeNull()
            ->and($handler->is_enabled)->toBeTrue(); // Still enabled after 2 failures
    });

    it('resets failure count on success', function () {
        $handler = NotificationHandler::create([
            'biolink_id' => $this->biolink->id,
            'workspace_id' => $this->workspace->id,
            'name' => 'Recovery Handler',
            'type' => 'webhook',
            'settings' => ['url' => 'https://example.com/webhook'],
            'events' => ['click'],
            'is_enabled' => true,
            'consecutive_failures' => 3,
        ]);

        $handler->recordSuccess();
        $handler->refresh();

        expect($handler->consecutive_failures)->toBe(0);
    });
});
