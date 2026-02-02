<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->index(['subject_type', 'subject_id'], 'subject_index');
            $table->index(['causer_type', 'causer_id'], 'causer_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('subject_index');
            $table->dropIndex('causer_index');
        });
    }
};
