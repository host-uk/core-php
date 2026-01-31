---
title: Testing
description: Test coverage and testing guide for core-analytics
updated: 2026-01-29
---

# Testing

This document describes the testing strategy, coverage, and guidelines for the core-analytics package.

## Test Structure

```
tests/
├── Feature/
│   ├── Admin/                    # Admin panel tests
│   ├── Api/                      # API endpoint tests
│   ├── Integration/              # End-to-end flow tests
│   ├── Mcp/                      # MCP tool tests
│   ├── AnalyticsServiceTest.php
│   ├── AnalyticsTrackingServiceTest.php
│   ├── BotDetectionServiceTest.php
│   ├── ExperimentTest.php
│   ├── FunnelTest.php
│   ├── GdprTest.php
│   ├── GoalApiTest.php
│   ├── GoalTest.php
│   ├── HeatmapTest.php
│   ├── SessionReplayTest.php
│   └── ...
├── Unit/
│   └── UserAgentParserTest.php
├── UseCase/
│   ├── CreateWebsiteBasic.php
│   └── CreateWebsiteEnhanced.php
└── TestCase.php
```

## Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test -- --coverage

# Run specific test file
./vendor/bin/pest tests/Feature/BotDetectionServiceTest.php

# Run specific test
./vendor/bin/pest --filter="test_detects_known_bot_user_agents"

# Run tests in parallel
./vendor/bin/pest --parallel
```

## Test Database

Tests use SQLite in-memory database with `RefreshDatabase` trait:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class BotDetectionServiceTest extends TestCase
{
    use RefreshDatabase;
}
```

## Coverage Summary

### Services

| Service | Coverage | Notes |
|---------|----------|-------|
| AnalyticsService | Good | Stats generation, time series |
| AnalyticsTrackingService | Good | Event tracking, session management |
| BotDetectionService | Good | Detection, caching, rules |
| FunnelService | Partial | Analysis covered, step matching needs more |
| GdprService | Good | Export, delete, anonymise |
| GeoIpService | Partial | CDN headers covered, MaxMind mocked |
| RealtimeAnalyticsService | Partial | Redis operations, needs integration test |
| SessionReplayStorageService | Partial | Store/retrieve, cleanup needs more |
| AnalyticsExperimentService | Good | Variant assignment, significance |

### Controllers

| Controller | Coverage | Notes |
|------------|----------|-------|
| PixelController | Partial | Basic tracking, needs edge cases |
| AnalyticsWebsiteController | Basic | CRUD operations |
| AnalyticsStatsController | Basic | Endpoint tests |
| GoalController | Good | CRUD and conversions |
| ExperimentController | Good | Full lifecycle |
| FunnelController | Partial | Analysis endpoint |
| GdprController | Partial | Export tested |
| BotDetectionController | Basic | Stats endpoint |

### Models

| Model | Coverage | Notes |
|-------|----------|-------|
| AnalyticsWebsite | Good | Relationships, scopes |
| AnalyticsVisitor | Partial | Basic tests |
| AnalyticsSession | Partial | Basic tests |
| AnalyticsEvent | Partial | Basic tests |
| Goal | Good | Matching logic, conversions |
| AnalyticsFunnel | Partial | Basic tests |
| AnalyticsExperiment | Good | Lifecycle, variants |
| BotDetection | Good | Logging tests |
| BotRule | Good | Matching tests |

## Testing Patterns

### Service Tests

```php
describe('Analytics Service', function () {
    beforeEach(function () {
        Cache::flush();
        $this->service = new AnalyticsService;
        $this->website = Website::factory()->create();
    });

    it('generates basic statistics for a website', function () {
        AnalyticsEvent::factory()->count(10)->create([
            'website_id' => $this->website->id,
            'type' => 'pageview',
        ]);

        $stats = $this->service->generateStats($this->website);

        expect($stats)->toHaveKeys([
            'total_pageviews',
            'unique_visitors',
            'bounce_rate',
        ]);
    });
});
```

### Bot Detection Tests

```php
public function test_detects_known_bot_user_agents(): void
{
    $botUserAgents = [
        'Googlebot/2.1 (+http://www.google.com/bot.html)',
        'curl/7.79.1',
        'HeadlessChrome/120.0.6099.109',
    ];

    foreach ($botUserAgents as $ua) {
        $request = $this->createRequest($ua);
        $result = $this->service->analyse($request);

        $this->assertTrue(
            $result['is_bot'] || $result['score'] >= 30,
            "Failed to detect bot: {$ua}"
        );
    }
}
```

### API Tests

```php
it('returns website statistics', function () {
    $website = AnalyticsWebsite::factory()->create();

    $this->actingAs($user)
        ->getJson("/analytics/websites/{$website->id}/stats")
        ->assertOk()
        ->assertJsonStructure([
            'total_pageviews',
            'unique_visitors',
            'bounce_rate',
        ]);
});
```

### Mock Request Helper

```php
protected function createRequest(string $userAgent, array $headers = []): Request
{
    $request = Request::create('/', 'GET');
    $request->headers->set('User-Agent', $userAgent);

    foreach ($headers as $name => $value) {
        $request->headers->set($name, $value);
    }

    $request->server->set('REMOTE_ADDR', '127.0.0.1');

    return $request;
}
```

## Missing Test Coverage

### High Priority

1. **Full tracking flow integration test**
   - Pixel hit -> Queue job -> Database write -> Cache invalidation
   - Goal conversion flow
   - Experiment variant assignment

2. **Rate limiting tests**
   - Verify rate limits are enforced
   - Test different throttle scenarios

3. **Multi-tenant isolation tests**
   - Verify workspace scoping
   - Cross-workspace data leak prevention

### Medium Priority

1. **Bot detection edge cases**
   - Privacy browsers (Brave, Tor)
   - Corporate proxies
   - VPN users

2. **Session replay tests**
   - Large replay handling
   - Compression/decompression
   - Expiry cleanup

3. **Real-time analytics tests**
   - Redis sorted set operations
   - Broadcast throttling
   - Cleanup of stale data

### Low Priority

1. **Email report tests**
   - Report generation
   - Scheduling
   - Preview functionality

2. **Heatmap aggregation tests**
   - Large dataset aggregation
   - Viewport normalisation

## Test Data Factories

### AnalyticsWebsiteFactory

```php
AnalyticsWebsite::factory()->create([
    'name' => 'Test Site',
    'host' => 'test.example.com',
    'tracking_enabled' => true,
]);
```

### AnalyticsEventFactory

```php
AnalyticsEvent::factory()->count(100)->create([
    'website_id' => $website->id,
    'type' => 'pageview',
    'created_at' => now()->subDays(rand(1, 30)),
]);
```

### AnalyticsSessionFactory

```php
AnalyticsSession::factory()->create([
    'website_id' => $website->id,
    'is_bounce' => false,
    'duration' => 300,
    'pageviews' => 5,
]);
```

## Mocking External Services

### Redis

```php
use Illuminate\Support\Facades\Redis;

Redis::shouldReceive('zadd')->once();
Redis::shouldReceive('zrangebyscore')->andReturn(['visitor-1', 'visitor-2']);
```

### MaxMind GeoIP

```php
$this->mock(GeoIpService::class, function ($mock) {
    $mock->shouldReceive('lookup')
        ->andReturn([
            'country_code' => 'GB',
            'city_name' => 'London',
        ]);
});
```

### Queue Jobs

```php
use Illuminate\Support\Facades\Queue;

Queue::fake();

// Perform action that dispatches job

Queue::assertPushed(ProcessTrackingEvent::class);
```

## Performance Testing

For high-volume testing:

```php
it('handles high volume of events efficiently', function () {
    $website = AnalyticsWebsite::factory()->create();

    // Create 10,000 events
    AnalyticsEvent::factory()
        ->count(10000)
        ->create(['website_id' => $website->id]);

    $start = microtime(true);
    $stats = $this->service->generateStats($website);
    $duration = microtime(true) - $start;

    expect($duration)->toBeLessThan(1.0); // Under 1 second
});
```

## Test Environment Configuration

### phpunit.xml

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="REDIS_CLIENT" value="mock"/>
</php>
```

### Test-Specific Config

```php
config(['analytics.bot_detection.enabled' => true]);
config(['analytics.bot_detection.threshold' => 50]);
```

## CI Integration

Tests run on GitHub Actions:

```yaml
- name: Run Tests
  run: composer test -- --coverage-clover coverage.xml

- name: Upload Coverage
  uses: codecov/codecov-action@v3
```

## Writing New Tests

### Guidelines

1. Use Pest syntax for new tests
2. Use descriptive test names
3. Test one thing per test
4. Use factories for test data
5. Clean up after tests (RefreshDatabase handles this)
6. Mock external services
7. Test edge cases and error conditions

### Example Test Structure

```php
describe('Feature Name', function () {
    beforeEach(function () {
        // Setup
    });

    describe('scenario A', function () {
        it('does expected behaviour', function () {
            // Arrange
            $input = [...];

            // Act
            $result = $this->service->method($input);

            // Assert
            expect($result)->toBe($expected);
        });

        it('handles edge case', function () {
            // ...
        });

        it('throws exception for invalid input', function () {
            expect(fn() => $this->service->method(null))
                ->toThrow(InvalidArgumentException::class);
        });
    });
});
```

## Debugging Tests

### Dump and Die

```php
dd($result); // Dump and die
dump($result); // Dump and continue
```

### Database Queries

```php
\DB::enableQueryLog();
// ... code ...
dd(\DB::getQueryLog());
```

### Test Output

```bash
./vendor/bin/pest --verbose
./vendor/bin/pest --debug
```
