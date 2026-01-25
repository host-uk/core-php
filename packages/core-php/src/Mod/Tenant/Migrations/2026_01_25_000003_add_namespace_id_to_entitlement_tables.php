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
        // Add namespace_id to entitlement_boosts
        Schema::table('entitlement_boosts', function (Blueprint $table) {
            $table->foreignId('namespace_id')->nullable()
                ->after('workspace_id')
                ->constrained('namespaces')
                ->nullOnDelete();

            $table->index(['namespace_id', 'feature_code', 'status']);
        });

        // Add namespace_id to entitlement_usage_records
        Schema::table('entitlement_usage_records', function (Blueprint $table) {
            $table->foreignId('namespace_id')->nullable()
                ->after('workspace_id')
                ->constrained('namespaces')
                ->nullOnDelete();

            $table->index(['namespace_id', 'feature_code', 'recorded_at']);
        });

        // Add namespace_id to entitlement_logs
        Schema::table('entitlement_logs', function (Blueprint $table) {
            $table->foreignId('namespace_id')->nullable()
                ->after('workspace_id')
                ->constrained('namespaces')
                ->nullOnDelete();

            $table->index(['namespace_id', 'action', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entitlement_boosts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('namespace_id');
        });

        Schema::table('entitlement_usage_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('namespace_id');
        });

        Schema::table('entitlement_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('namespace_id');
        });
    }
};
