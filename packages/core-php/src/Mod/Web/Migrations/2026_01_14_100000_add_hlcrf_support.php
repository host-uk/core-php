<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add HLCRF (Header-Left-Content-Right-Footer) layout support.
     *
     * This migration adds:
     * - layout_config to biolinks for page-level layout settings
     * - region, region_order, breakpoint_visibility to blocks for multi-region support
     */
    public function up(): void
    {
        // Skip if tables don't exist (e.g., SQLite schema check)
        if (! Schema::hasTable('biolinks') || ! Schema::hasTable('biolink_blocks')) {
            return;
        }

        // Add layout_config to biolinks table
        if (! Schema::hasColumn('biolinks', 'layout_config')) {
            Schema::table('biolinks', function (Blueprint $table) {
                $table->json('layout_config')->nullable()->after('settings');
            });
        }

        // Add region fields to blocks table
        Schema::table('biolink_blocks', function (Blueprint $table) {
            if (! Schema::hasColumn('biolink_blocks', 'region')) {
                $table->string('region', 16)->default('content')->after('type');
            }

            if (! Schema::hasColumn('biolink_blocks', 'region_order')) {
                $table->unsignedSmallInteger('region_order')->default(0)->after('order');
            }

            if (! Schema::hasColumn('biolink_blocks', 'breakpoint_visibility')) {
                $table->json('breakpoint_visibility')->nullable()->after('region_order');
            }
        });

        // Add optimised index for region queries
        try {
            $indexExists = collect(DB::select("SHOW INDEX FROM biolink_blocks WHERE Key_name = 'blocks_region_index'"))->isNotEmpty();
        } catch (\Exception $e) {
            $indexExists = false;
        }

        if (! $indexExists) {
            Schema::table('biolink_blocks', function (Blueprint $table) {
                $table->index(
                    ['biolink_id', 'region', 'region_order', 'is_enabled'],
                    'blocks_region_index'
                );
            });
        }

        // Migrate existing blocks: set region='content' and region_order=order
        DB::table('biolink_blocks')
            ->whereNull('region')
            ->orWhere('region', '')
            ->update([
                'region' => 'content',
            ]);

        DB::table('biolink_blocks')
            ->where('region_order', 0)
            ->whereColumn('region_order', '!=', 'order')
            ->update([
                'region_order' => DB::raw('`order`'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biolink_blocks', function (Blueprint $table) {
            $table->dropIndex('blocks_region_index');
        });

        Schema::table('biolink_blocks', function (Blueprint $table) {
            $table->dropColumn(['region', 'region_order', 'breakpoint_visibility']);
        });

        Schema::table('biolinks', function (Blueprint $table) {
            $table->dropColumn('layout_config');
        });
    }
};
