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
        Schema::create('entitlement_namespace_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('namespace_id')
                ->constrained('namespaces')
                ->cascadeOnDelete();
            $table->foreignId('package_id')
                ->constrained('entitlement_packages')
                ->cascadeOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('billing_cycle_anchor')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['namespace_id', 'status']);
            $table->index(['package_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entitlement_namespace_packages');
    }
};
