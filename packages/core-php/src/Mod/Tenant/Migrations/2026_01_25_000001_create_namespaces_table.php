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
        Schema::create('namespaces', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name', 128);
            $table->string('slug', 64);
            $table->string('description', 512)->nullable();
            $table->string('icon', 64)->default('folder');
            $table->string('color', 16)->default('zinc');

            // Polymorphic owner (User::class or Workspace::class)
            $table->morphs('owner');

            // Workspace context for billing aggregation (optional)
            // User-owned namespaces can have a workspace for billing
            $table->foreignId('workspace_id')->nullable()
                ->constrained()->nullOnDelete();

            $table->json('settings')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Each owner can only have one namespace with a given slug
            $table->unique(['owner_type', 'owner_id', 'slug']);
            $table->index(['workspace_id', 'is_active']);
            $table->index(['owner_type', 'owner_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('namespaces');
    }
};
