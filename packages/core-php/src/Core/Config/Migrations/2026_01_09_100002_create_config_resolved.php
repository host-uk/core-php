<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * config_resolved is the materialised resolution table.
     * All reads hit this table directly - no computation at read time.
     * Prime operation populates this table by running resolution logic.
     *
     * Uses a generated scope_key column for unique lookups since
     * MariaDB doesn't support nullable columns in unique constraints.
     */
    public function up(): void
    {
        Schema::create('config_resolved', function (Blueprint $table) {
            $table->id();

            // The full address: workspace + channel + key
            $table->unsignedBigInteger('workspace_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->string('key_code');

            // Generated scope key for unique lookups (NULL becomes 'N')
            // This allows nullable columns while maintaining uniqueness
            $table->string('scope_key')->storedAs(
                "CONCAT(COALESCE(workspace_id, 'N'), ':', COALESCE(channel_id, 'N'), ':', key_code)"
            );

            // The resolved value
            $table->json('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('locked')->default(false);

            // Audit: where did this value come from?
            $table->unsignedBigInteger('source_profile_id')->nullable();
            $table->unsignedBigInteger('source_channel_id')->nullable();
            $table->boolean('virtual')->default(false);

            $table->timestamp('computed_at');

            // Unique on generated scope_key - O(1) lookup
            $table->unique('scope_key', 'config_resolved_lookup');

            // Index for scope queries
            $table->index(['workspace_id', 'channel_id'], 'config_resolved_scope_idx');

            $table->foreign('source_profile_id')
                ->references('id')
                ->on('config_profiles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_resolved');
    }
};
