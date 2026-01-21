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
        // M1: Config key definitions
        Schema::create('config_keys', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('parent_id')->nullable()
                ->constrained('config_keys')
                ->nullOnDelete();
            $table->string('type')->default('string');
            $table->string('category')->index();
            $table->string('description')->nullable();
            $table->json('default_value')->nullable();
            $table->timestamps();

            $table->index(['category', 'code']);
        });

        // M2: Config profiles (scope containers)
        Schema::create('config_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('scope_type')->index();
            $table->unsignedBigInteger('scope_id')->nullable()->index();
            $table->foreignId('parent_profile_id')->nullable()
                ->constrained('config_profiles')
                ->nullOnDelete();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['scope_type', 'scope_id']);
            $table->unique(['scope_type', 'scope_id', 'priority']);
        });

        // Junction: Config values (profile <> key with value)
        Schema::create('config_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')
                ->constrained('config_profiles')
                ->cascadeOnDelete();
            $table->foreignId('key_id')
                ->constrained('config_keys')
                ->cascadeOnDelete();
            $table->json('value')->nullable();
            $table->boolean('locked')->default(false);
            $table->foreignId('inherited_from')->nullable()
                ->constrained('config_profiles')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(['profile_id', 'key_id']);
            $table->index(['key_id', 'locked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('config_values');
        Schema::dropIfExists('config_profiles');
        Schema::dropIfExists('config_keys');
    }
};
