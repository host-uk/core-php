<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('default');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            // WP Connector fields
            $table->boolean('wp_connector_enabled')->default(false);
            $table->string('wp_connector_url')->nullable();
            $table->string('wp_connector_secret')->nullable();
            $table->timestamp('wp_connector_verified_at')->nullable();
            $table->timestamp('wp_connector_last_sync')->nullable();
            $table->json('wp_connector_config')->nullable();

            // Billing fields
            $table->string('stripe_customer_id')->nullable();
            $table->string('btcpay_customer_id')->nullable();
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_address_line1')->nullable();
            $table->string('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_state')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('tax_exempt')->default(false);

            $table->timestamps();
        });

        Schema::create('user_workspace', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'workspace_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_workspace');
        Schema::dropIfExists('workspaces');
    }
};
