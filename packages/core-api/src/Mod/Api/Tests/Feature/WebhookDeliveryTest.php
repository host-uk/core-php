<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Mod\Api\Jobs\DeliverWebhookJob;
use Mod\Api\Models\WebhookDelivery;
use Mod\Api\Models\WebhookEndpoint;
use Mod\Api\Services\WebhookService;
use Mod\Tenant\Models\Workspace;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Http::fake();

    $this->workspace = Workspace::factory()->create();
    $this->service = app(WebhookService::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Webhook Service
// ─────────────────────────────────────────────────────────────────────────────

describe('Webhook Service', function () {
    it('dispatches event to subscribed endpoints', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        $deliveries = $this->service->dispatch(
            $this->workspace->id,
            'bio.created',
            ['bio_id' => 123, 'name' => 'Test Bio']
        );

        expect($deliveries)->toHaveCount(1);
        expect($deliveries[0]->event_type)->toBe('bio.created');
        expect($deliveries[0]->webhook_endpoint_id)->toBe($endpoint->id);
        expect($deliveries[0]->status)->toBe(WebhookDelivery::STATUS_PENDING);
    });

    it('does not dispatch to endpoints not subscribed to event', function () {
        WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.updated'] // Different event
        );

        $deliveries = $this->service->dispatch(
            $this->workspace->id,
            'bio.created',
            ['bio_id' => 123]
        );

        expect($deliveries)->toBeEmpty();
    });

    it('dispatches to wildcard subscribed endpoints', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['*'] // Subscribe to all events
        );

        $deliveries = $this->service->dispatch(
            $this->workspace->id,
            'any.event.type',
            ['data' => 'test']
        );

        expect($deliveries)->toHaveCount(1);
    });

    it('does not dispatch to inactive endpoints', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );
        $endpoint->update(['active' => false]);

        $deliveries = $this->service->dispatch(
            $this->workspace->id,
            'bio.created',
            ['bio_id' => 123]
        );

        expect($deliveries)->toBeEmpty();
    });

    it('does not dispatch to disabled endpoints', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );
        $endpoint->update(['disabled_at' => now()]);

        $deliveries = $this->service->dispatch(
            $this->workspace->id,
            'bio.created',
            ['bio_id' => 123]
        );

        expect($deliveries)->toBeEmpty();
    });

    it('returns webhook stats for workspace', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        // Create some deliveries
        WebhookDelivery::createForEvent($endpoint, 'bio.created', ['id' => 1]);
        $delivery2 = WebhookDelivery::createForEvent($endpoint, 'bio.created', ['id' => 2]);
        $delivery2->markSuccess(200);
        $delivery3 = WebhookDelivery::createForEvent($endpoint, 'bio.created', ['id' => 3]);
        $delivery3->markFailed(500, 'Server Error');

        $stats = $this->service->getStats($this->workspace->id);

        expect($stats['total'])->toBe(3);
        expect($stats['pending'])->toBe(1);
        expect($stats['success'])->toBe(1);
        expect($stats['retrying'])->toBe(1); // Failed with retries remaining
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Webhook Delivery Job
// ─────────────────────────────────────────────────────────────────────────────

describe('Webhook Delivery Job', function () {
    it('marks delivery as success on 2xx response', function () {
        Http::fake([
            'example.com/*' => Http::response(['received' => true], 200),
        ]);

        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        $delivery = WebhookDelivery::createForEvent(
            $endpoint,
            'bio.created',
            ['bio_id' => 123]
        );

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        $delivery->refresh();
        expect($delivery->status)->toBe(WebhookDelivery::STATUS_SUCCESS);
        expect($delivery->response_code)->toBe(200);
        expect($delivery->delivered_at)->not->toBeNull();
    });

    it('marks delivery as retrying on 5xx response', function () {
        Http::fake([
            'example.com/*' => Http::response('Server Error', 500),
        ]);

        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        $delivery = WebhookDelivery::createForEvent(
            $endpoint,
            'bio.created',
            ['bio_id' => 123]
        );

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        $delivery->refresh();
        expect($delivery->status)->toBe(WebhookDelivery::STATUS_RETRYING);
        expect($delivery->response_code)->toBe(500);
        expect($delivery->attempt)->toBe(2);
        expect($delivery->next_retry_at)->not->toBeNull();
    });

    it('marks delivery as failed after max retries', function () {
        Http::fake([
            'example.com/*' => Http::response('Server Error', 500),
        ]);

        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        $delivery = WebhookDelivery::createForEvent(
            $endpoint,
            'bio.created',
            ['bio_id' => 123]
        );
        $delivery->update(['attempt' => WebhookDelivery::MAX_RETRIES]);

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        $delivery->refresh();
        expect($delivery->status)->toBe(WebhookDelivery::STATUS_FAILED);
    });

    it('includes correct signature header', function () {
        Http::fake(function ($request) {
            // Verify signature header exists
            expect($request->hasHeader('X-HostHub-Signature'))->toBeTrue();
            expect($request->hasHeader('X-HostHub-Event'))->toBeTrue();
            expect($request->hasHeader('X-HostHub-Delivery'))->toBeTrue();

            return Http::response(['ok' => true], 200);
        });

        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        $delivery = WebhookDelivery::createForEvent(
            $endpoint,
            'bio.created',
            ['bio_id' => 123]
        );

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook';
        });
    });

    it('skips delivery if endpoint becomes inactive', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        $delivery = WebhookDelivery::createForEvent(
            $endpoint,
            'bio.created',
            ['bio_id' => 123]
        );

        // Deactivate endpoint after delivery created
        $endpoint->update(['active' => false]);

        $job = new DeliverWebhookJob($delivery);
        $job->handle();

        // Should not have made any HTTP requests
        Http::assertNothingSent();

        // Delivery should remain pending (skipped)
        $delivery->refresh();
        expect($delivery->status)->toBe(WebhookDelivery::STATUS_PENDING);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Webhook Endpoint Auto-Disable
// ─────────────────────────────────────────────────────────────────────────────

describe('Webhook Endpoint Auto-Disable', function () {
    it('disables endpoint after consecutive failures', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        // Simulate 10 consecutive failures
        for ($i = 0; $i < 10; $i++) {
            $endpoint->recordFailure();
        }

        $endpoint->refresh();
        expect($endpoint->active)->toBeFalse();
        expect($endpoint->disabled_at)->not->toBeNull();
        expect($endpoint->failure_count)->toBe(10);
    });

    it('resets failure count on success', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        // Record some failures
        $endpoint->recordFailure();
        $endpoint->recordFailure();
        $endpoint->recordFailure();
        expect($endpoint->fresh()->failure_count)->toBe(3);

        // Record success
        $endpoint->recordSuccess();

        $endpoint->refresh();
        expect($endpoint->failure_count)->toBe(0);
    });

    it('can be re-enabled after being disabled', function () {
        $endpoint = WebhookEndpoint::createForWorkspace(
            $this->workspace->id,
            'https://example.com/webhook',
            ['bio.created']
        );

        // Disable it
        $endpoint->update([
            'active' => false,
            'disabled_at' => now(),
            'failure_count' => 10,
        ]);

        // Re-enable
        $endpoint->enable();

        $endpoint->refresh();
        expect($endpoint->active)->toBeTrue();
        expect($endpoint->disabled_at)->toBeNull();
        expect($endpoint->failure_count)->toBe(0);
    });
});
