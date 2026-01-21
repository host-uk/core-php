<?php

declare(strict_types=1);

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
        // Channel model - voice/context substrate
        if (! Schema::hasTable('config_channels')) {
            Schema::create('config_channels', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('name');
                $table->foreignId('parent_id')->nullable()
                    ->constrained('config_channels')
                    ->nullOnDelete();
                $table->foreignId('workspace_id')->nullable()
                    ->constrained('workspaces')
                    ->cascadeOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                // System channels have unique codes
                // Workspace channels can reuse codes (override system)
                $table->unique(['code', 'workspace_id']);
                $table->index('parent_id');
            });
        }

        // Skip config_values alterations if table doesn't exist
        if (! Schema::hasTable('config_values')) {
            return;
        }

        // Skip if already migrated
        if (Schema::hasColumn('config_values', 'channel_id')) {
            return;
        }

        // Add channel dimension to config values
        Schema::table('config_values', function (Blueprint $table) {
            // Add a standalone index on profile_id so FK can use it
            // (before we drop the unique constraint that FK was using)
            $table->index('profile_id', 'config_values_profile_id_index');

            // Add channel_id column
            $table->foreignId('channel_id')->nullable()
                ->after('key_id')
                ->constrained('config_channels')
                ->nullOnDelete();
        });

        // Update unique constraint in separate statement
        // (dropping after index created to avoid FK issues)
        Schema::table('config_values', function (Blueprint $table) {
            $table->dropUnique(['profile_id', 'key_id']);
            $table->unique(['profile_id', 'key_id', 'channel_id'], 'config_values_profile_key_channel_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('config_values', function (Blueprint $table) {
            $table->dropUnique('config_values_profile_key_channel_unique');
            $table->dropConstrainedForeignId('channel_id');
            $table->dropIndex('config_values_profile_id_index');
            $table->unique(['profile_id', 'key_id']);
        });

        Schema::dropIfExists('config_channels');
    }
};
