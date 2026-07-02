<?php

use App\Enums\Announcements\AnnouncementCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // Classification shown as a colored badge (Event / Notice /
            // Holiday / Exam) on the public homepage and in Principal
            // notifications — previously this was faked client-side by
            // guessing from `urgency`, which produced misleading badges
            // (e.g. any High-urgency post showing as "Exam").
            $table->enum('category', AnnouncementCategory::values())
                ->default(AnnouncementCategory::Notice->value)
                ->after('urgency');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};