<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('honeypot_hits')) {
            return;
        }

        if (Schema::hasColumn('honeypot_hits', 'severity')) {
            return;
        }

        Schema::table('honeypot_hits', function (Blueprint $table) {
            $table->string('severity', 20)->default('warning')->after('bot_name');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::table('honeypot_hits', function (Blueprint $table) {
            $table->dropIndex(['severity']);
            $table->dropColumn('severity');
        });
    }
};
