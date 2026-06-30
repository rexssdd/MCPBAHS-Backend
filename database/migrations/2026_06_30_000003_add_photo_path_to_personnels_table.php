<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds a profile photo to personnels so the public homepage faculty
     * directory (FacultySection.jsx) can show real faces instead of
     * initials-only avatars. Photos are uploaded by admin/principal via
     * POST /v1/personnels/{personnel}/photo and stored on the default
     * (Supabase S3-compatible) disk; only the relative path is kept here.
     */
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropColumn('photo_path');
        });
    }
};
