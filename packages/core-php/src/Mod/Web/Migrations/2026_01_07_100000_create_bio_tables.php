<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Domains
        if (! Schema::hasTable('biolink_domains')) {
            Schema::create('biolink_domains', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('host', 256)->unique();
                $table->string('scheme', 8)->default('https');
                $table->foreignId('biolink_id')->nullable(); // Constraint added later to circular dependency
                $table->string('custom_index_url', 512)->nullable();
                $table->string('custom_not_found_url', 512)->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->enum('verification_status', ['pending', 'verified', 'failed'])->default('pending');
                $table->string('verification_token', 64)->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'is_enabled']);
                $table->index(['workspace_id', 'is_enabled']);
            });
        }

        // 2. Projects
        if (! Schema::hasTable('biolink_projects')) {
            Schema::create('biolink_projects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('name', 128);
                $table->string('color', 16)->default('#6366f1');
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'created_at']);
                $table->index(['workspace_id', 'created_at']);
            });
        }

        // 3. Themes
        if (! Schema::hasTable('biolink_themes')) {
            Schema::create('biolink_themes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 64);
                $table->string('slug', 64)->unique();
                $table->json('settings');
                $table->boolean('is_system')->default(false);
                $table->boolean('is_premium')->default(false);
                $table->boolean('is_gallery')->default(false);
                $table->string('category', 32)->nullable();
                $table->string('preview_image', 255)->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->smallInteger('sort_order')->unsigned()->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['is_system', 'is_active', 'sort_order']);
                $table->index(['user_id', 'is_active']);
                $table->index(['workspace_id', 'is_active']);
                $table->index(['is_gallery', 'is_active', 'category', 'sort_order'], 'gallery_filter_index');
            });
        }

        // 4. Biolinks (Main)
        if (! Schema::hasTable('biolinks')) {
            Schema::create('biolinks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained('biolink_projects')->nullOnDelete();
                $table->foreignId('domain_id')->nullable()->constrained('biolink_domains')->nullOnDelete();
                $table->foreignId('theme_id')->nullable()->constrained('biolink_themes')->nullOnDelete();
                $table->string('type', 32)->default('biolink');
                $table->string('url', 256);
                $table->string('location_url', 2048)->nullable();
                $table->json('settings')->nullable();
                $table->json('email_report_settings')->nullable();
                $table->unsignedBigInteger('clicks')->default(0);
                $table->unsignedBigInteger('unique_clicks')->default(0);
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('is_verified')->default(false);
                $table->timestamps();
                $table->softDeletes();
                $table->timestamp('last_click_at')->nullable();

                $table->unique(['domain_id', 'url']);
                $table->index(['user_id', 'type', 'is_enabled']);
                $table->index(['user_id', 'project_id']);
                $table->index(['workspace_id', 'type']);
            });

            // Add constraint to domains table now that biolinks exists
            Schema::table('biolink_domains', function (Blueprint $table) {
                $table->foreign('biolink_id')->references('id')->on('biolinks')->nullOnDelete();
            });
        }

        // 5. Blocks
        if (! Schema::hasTable('biolink_blocks')) {
            Schema::create('biolink_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->string('type', 32);
                $table->string('location_url', 512)->nullable();
                $table->json('settings')->nullable();
                $table->unsignedSmallInteger('order')->default(0);
                $table->unsignedBigInteger('clicks')->default(0);
                $table->timestamp('start_date')->nullable();
                $table->timestamp('end_date')->nullable();
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->index(['biolink_id', 'is_enabled', 'order']);
            });
        }

        // 6. Pixels
        if (! Schema::hasTable('biolink_pixels')) {
            Schema::create('biolink_pixels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 32);
                $table->string('name', 64);
                $table->string('pixel_id', 128);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['user_id', 'type']);
                $table->index(['workspace_id', 'type']);
            });
        }

        // 7. Biolink Pixel Pivot
        if (! Schema::hasTable('biolink_pixel')) {
            Schema::create('biolink_pixel', function (Blueprint $table) {
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->foreignId('pixel_id')->constrained('biolink_pixels')->cascadeOnDelete();
                $table->primary(['biolink_id', 'pixel_id']);
            });
        }

        // 8. Click Stats (Aggregated)
        if (! Schema::hasTable('biolink_click_stats')) {
            Schema::create('biolink_click_stats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->foreignId('block_id')->nullable()->constrained('biolink_blocks')->nullOnDelete();
                $table->date('date');
                $table->unsignedTinyInteger('hour')->nullable();
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('unique_clicks')->default(0);
                $table->char('country_code', 2)->nullable();
                $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'other'])->nullable();
                $table->string('referrer_host', 256)->nullable();
                $table->string('utm_source', 64)->nullable();
                $table->timestamps();

                $table->unique(['biolink_id', 'block_id', 'date', 'hour', 'country_code', 'device_type', 'referrer_host', 'utm_source'], 'biolink_stats_unique');
                $table->index(['biolink_id', 'date']);
                $table->index(['biolink_id', 'date', 'country_code']);
            });
        }

        // 9. Clicks (Raw)
        if (! Schema::hasTable('biolink_clicks')) {
            Schema::create('biolink_clicks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->foreignId('block_id')->nullable()->constrained('biolink_blocks')->nullOnDelete();
                $table->string('visitor_hash', 64)->nullable();
                $table->char('country_code', 2)->nullable();
                $table->string('region', 64)->nullable();
                $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'other'])->default('other');
                $table->string('os_name', 32)->nullable();
                $table->string('browser_name', 32)->nullable();
                $table->string('referrer_host', 256)->nullable();
                $table->string('utm_source', 64)->nullable();
                $table->string('utm_medium', 64)->nullable();
                $table->string('utm_campaign', 64)->nullable();
                $table->boolean('is_unique')->default(false);
                $table->timestamp('created_at'); // No updated_at needed for raw logs

                $table->index(['biolink_id', 'created_at']);
                $table->index(['biolink_id', 'country_code']);
                $table->index(['biolink_id', 'device_type']);
                $table->index(['biolink_id', 'referrer_host']);
                $table->index(['block_id', 'created_at']);
            });
        }

        // 10. Notification Handlers
        if (! Schema::hasTable('biolink_notification_handlers')) {
            Schema::create('biolink_notification_handlers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
                $table->string('name', 128);
                $table->enum('type', ['webhook', 'email', 'slack', 'discord', 'telegram']);
                $table->json('settings');
                $table->json('events')->default(json_encode(['click']));
                $table->boolean('is_enabled')->default(true);
                $table->unsignedInteger('trigger_count')->default(0);
                $table->timestamp('last_triggered_at')->nullable();
                $table->timestamp('last_failed_at')->nullable();
                $table->unsignedSmallInteger('consecutive_failures')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['biolink_id', 'is_enabled']);
                $table->index(['workspace_id', 'type']);
            });
        }

        // 11. Push Configs
        if (! Schema::hasTable('biolink_push_configs')) {
            Schema::create('biolink_push_configs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->unique()->constrained('biolinks')->cascadeOnDelete();
                $table->text('vapid_public_key');
                $table->text('vapid_private_key');
                $table->string('default_icon_url', 512)->nullable();
                $table->boolean('prompt_enabled')->default(true);
                $table->unsignedSmallInteger('prompt_delay_seconds')->default(5);
                $table->unsignedSmallInteger('prompt_min_pageviews')->default(2);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        // 12. Push Subscribers
        if (! Schema::hasTable('biolink_push_subscribers')) {
            Schema::create('biolink_push_subscribers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->string('subscriber_hash', 64)->unique();
                $table->text('endpoint');
                $table->string('key_auth', 128);
                $table->string('key_p256dh', 128);
                $table->char('country_code', 2)->nullable();
                $table->string('city_name', 64)->nullable();
                $table->string('os_name', 32)->nullable();
                $table->string('browser_name', 32)->nullable();
                $table->string('browser_language', 16)->nullable();
                $table->enum('device_type', ['desktop', 'mobile', 'tablet', 'other'])->default('other');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_notification_at')->nullable();
                $table->unsignedInteger('notifications_received')->default(0);
                $table->timestamp('subscribed_at');
                $table->timestamp('unsubscribed_at')->nullable();
                $table->timestamps();

                $table->index(['biolink_id', 'is_active']);
                $table->index(['biolink_id', 'country_code']);
                $table->index(['biolink_id', 'device_type']);
            });
        }

        // 13. Push Notifications
        if (! Schema::hasTable('biolink_push_notifications')) {
            Schema::create('biolink_push_notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->string('title', 64);
                $table->string('body', 256)->nullable();
                $table->string('url', 512)->nullable();
                $table->string('icon_url', 512)->nullable();
                $table->string('badge_url', 512)->nullable();
                $table->enum('segment', ['all', 'desktop', 'mobile', 'country'])->default('all');
                $table->string('segment_value', 64)->nullable();
                $table->unsignedInteger('total_subscribers')->default(0);
                $table->unsignedInteger('sent_count')->default(0);
                $table->unsignedInteger('delivered_count')->default(0);
                $table->unsignedInteger('clicked_count')->default(0);
                $table->unsignedInteger('failed_count')->default(0);
                $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['biolink_id', 'status']);
                $table->index(['status', 'scheduled_at']);
            });
        }

        // 14. Push Deliveries
        if (! Schema::hasTable('biolink_push_deliveries')) {
            Schema::create('biolink_push_deliveries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('notification_id')->constrained('biolink_push_notifications')->cascadeOnDelete();
                $table->foreignId('subscriber_id')->constrained('biolink_push_subscribers')->cascadeOnDelete();
                $table->enum('status', ['pending', 'sent', 'delivered', 'clicked', 'failed'])->default('pending');
                $table->string('error_message', 256)->nullable();
                $table->unsignedTinyInteger('retry_count')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('clicked_at')->nullable();
                $table->timestamps();

                $table->unique(['notification_id', 'subscriber_id']);
                $table->index(['notification_id', 'status']);
            });
        }

        // 15. PWAs
        if (! Schema::hasTable('biolink_pwas')) {
            Schema::create('biolink_pwas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->unique()->constrained('biolinks')->cascadeOnDelete();
                $table->string('name', 128);
                $table->string('short_name', 32)->nullable();
                $table->string('description', 256)->nullable();
                $table->string('theme_color', 16)->default('#6366f1');
                $table->string('background_color', 16)->default('#ffffff');
                $table->enum('display', ['standalone', 'fullscreen', 'minimal-ui', 'browser'])->default('standalone');
                $table->enum('orientation', ['any', 'natural', 'portrait', 'landscape'])->default('any');
                $table->string('icon_url', 512)->nullable();
                $table->string('icon_maskable_url', 512)->nullable();
                $table->json('screenshots')->nullable();
                $table->json('shortcuts')->nullable();
                $table->string('start_url', 512)->nullable();
                $table->string('scope', 512)->nullable();
                $table->string('lang', 8)->default('en');
                $table->enum('dir', ['ltr', 'rtl', 'auto'])->default('auto');
                $table->unsignedInteger('installs')->default(0);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();
            });
        }

        // 16. Submissions
        if (! Schema::hasTable('biolink_submissions')) {
            Schema::create('biolink_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('biolink_id')->constrained('biolinks')->cascadeOnDelete();
                $table->foreignId('block_id')->constrained('biolink_blocks')->cascadeOnDelete();
                $table->enum('type', ['email', 'phone', 'contact']);
                $table->json('data');
                $table->string('ip_hash', 64)->nullable();
                $table->char('country_code', 2)->nullable();
                $table->boolean('notification_sent')->default(false);
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['biolink_id', 'created_at']);
                $table->index(['block_id', 'created_at']);
                $table->index(['biolink_id', 'type']);
                $table->index('type');
            });
        }

        // 17. Templates
        if (! Schema::hasTable('biolink_templates')) {
            Schema::create('biolink_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('workspace_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name', 128);
                $table->string('slug', 128)->unique();
                $table->string('category', 64);
                $table->text('description')->nullable();
                $table->json('blocks_json');
                $table->json('settings_json');
                $table->json('placeholders')->nullable();
                $table->string('preview_image', 255)->nullable();
                $table->json('tags')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_premium')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->unsignedInteger('usage_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['category', 'is_active', 'sort_order']);
                $table->index(['is_system', 'is_active', 'sort_order']);
                $table->index(['user_id', 'is_active']);
                $table->index(['workspace_id', 'is_active']);
                $table->index('category');
            });
        }

        // 18. Theme Favourites
        if (! Schema::hasTable('theme_favourites')) {
            Schema::create('theme_favourites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('theme_id')->constrained('biolink_themes')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'theme_id']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_favourites');
        Schema::dropIfExists('biolink_templates');
        Schema::dropIfExists('biolink_submissions');
        Schema::dropIfExists('biolink_pwas');
        Schema::dropIfExists('biolink_push_deliveries');
        Schema::dropIfExists('biolink_push_notifications');
        Schema::dropIfExists('biolink_push_subscribers');
        Schema::dropIfExists('biolink_push_configs');
        Schema::dropIfExists('biolink_notification_handlers');
        Schema::dropIfExists('biolink_clicks');
        Schema::dropIfExists('biolink_click_stats');
        Schema::dropIfExists('biolink_pixel');
        Schema::dropIfExists('biolink_pixels');
        Schema::dropIfExists('biolink_blocks');
        Schema::table('biolink_domains', function (Blueprint $table) {
            $table->dropForeign(['biolink_id']);
        });
        Schema::dropIfExists('biolinks');
        Schema::dropIfExists('biolink_themes');
        Schema::dropIfExists('biolink_projects');
        Schema::dropIfExists('biolink_domains');
    }
};
