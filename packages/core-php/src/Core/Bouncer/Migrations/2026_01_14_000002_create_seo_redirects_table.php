<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500);
            $table->string('to_path', 500);
            $table->smallInteger('status_code')->default(301);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();

            $table->unique('from_path');
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_redirects');
    }
};
