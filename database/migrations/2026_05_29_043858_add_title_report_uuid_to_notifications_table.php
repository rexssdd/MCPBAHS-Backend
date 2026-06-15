<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `title` and `report_uuid` columns to the notifications table.
 *
 * These fields are now written by ReportService::approve() / reject()
 * so that notification rows carry a human-readable title and a direct
 * link back to the originating report.
 *
 * Run: php artisan migrate
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Only add if not already present (idempotent).
            if (! Schema::hasColumn('notifications', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            if (! Schema::hasColumn('notifications', 'report_uuid')) {
                $table->uuid('report_uuid')->nullable()->after('message');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['title', 'report_uuid']);
        });
    }
};