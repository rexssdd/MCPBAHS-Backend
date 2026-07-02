<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix the announcements_category_check constraint on PostgreSQL.
     *
     * Root cause: Laravel's $table->enum() on PostgreSQL creates a varchar
     * column with a CHECK constraint. The original migration
     * (2026_07_02_000001) ran while AnnouncementCategory::values() returned
     * a different set (e.g. capitalised labels like 'General' vs the current
     * lowercase values 'general') OR the constraint was compiled at a point
     * where the enum had different cases. Either way the live CHECK constraint
     * no longer matches the values the application sends, so every INSERT/
     * UPDATE that touches category fails with:
     *
     *   new row for relation "announcements" violates check constraint
     *   "announcements_category_check"
     *
     * Fix: drop the stale constraint and recreate it explicitly with raw SQL
     * using the correct lowercase values. We use raw SQL instead of Blueprint
     * so the constraint definition is explicit and won't silently drift again
     * if the PHP enum is edited in future.
     *
     * Safe to re-run: IF EXISTS / IF NOT EXISTS guards make this idempotent.
     */
    public function up(): void
    {
        // PostgreSQL only — SQLite/MySQL don't create named CHECK constraints
        // this way so we skip on those drivers.
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE announcements DROP CONSTRAINT IF EXISTS announcements_category_check');

        DB::statement("
            ALTER TABLE announcements
            ADD CONSTRAINT announcements_category_check
            CHECK (category IN ('general', 'event', 'notice', 'holiday', 'exam'))
        ");

        // Also fix the default in case it was set to the wrong case.
        DB::statement("ALTER TABLE announcements ALTER COLUMN category SET DEFAULT 'general'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE announcements DROP CONSTRAINT IF EXISTS announcements_category_check');
    }
};