<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\LifecycleEventProvider;
use Core\Seo\Boot;
use Core\Seo\SeoMetadata;
use Core\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TestSeoable extends Model
{
    protected $table = 'test_seoables';

    protected $guarded = [];
}

class SeoAuditCanonicalTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LifecycleEventProvider::class,
            Boot::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Create tables
        Schema::create('test_seoables', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');
            $table->string('canonical_url')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('og_data')->nullable();
            $table->json('twitter_data')->nullable();
            $table->json('schema_markup')->nullable();
            $table->string('robots')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->integer('seo_score')->nullable();
            $table->json('seo_issues')->nullable();
            $table->json('seo_suggestions')->nullable();
            $table->timestamps();
        });
    }

    public function test_fixes_protocol_issues(): void
    {
        // Create seoables
        $item1 = TestSeoable::create();
        $item2 = TestSeoable::create();
        $item3 = TestSeoable::create();
        $item4 = TestSeoable::create();

        // Create records with HTTP canonical URLs
        SeoMetadata::create([
            'seoable_type' => TestSeoable::class,
            'seoable_id' => $item1->id,
            'canonical_url' => 'http://example.com/post-1',
        ]);

        SeoMetadata::create([
            'seoable_type' => TestSeoable::class,
            'seoable_id' => $item2->id,
            'canonical_url' => 'http://example.com/post-2',
        ]);

        // Create a record with HTTPS (should not be touched)
        SeoMetadata::create([
            'seoable_type' => TestSeoable::class,
            'seoable_id' => $item3->id,
            'canonical_url' => 'https://example.com/post-3',
        ]);

        // Create a record without canonical URL
        SeoMetadata::create([
            'seoable_type' => TestSeoable::class,
            'seoable_id' => $item4->id,
            'canonical_url' => null,
        ]);

        // Run the command with --fix
        $this->artisan('seo:audit-canonical', ['--fix' => true])
            ->expectsOutput('Fixed 2 issue(s).')
            ->assertExitCode(1);

        // Verify updates
        $this->assertEquals('https://example.com/post-1', SeoMetadata::find(1)->canonical_url);
        $this->assertEquals('https://example.com/post-2', SeoMetadata::find(2)->canonical_url);
        $this->assertEquals('https://example.com/post-3', SeoMetadata::find(3)->canonical_url);
        $this->assertNull(SeoMetadata::find(4)->canonical_url);
    }
}
