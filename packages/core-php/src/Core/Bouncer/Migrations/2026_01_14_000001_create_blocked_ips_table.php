<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason', 50)->default('manual');
            $table->timestamp('blocked_at');
            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();

            $table->index('expires_at');
            $table->index('reason');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
    }
};
