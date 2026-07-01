<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds admin-editable `duration` and `details` (the "What You'll
     * Learn" checklist) fields to tvl_offers. TVLModal.jsx on the public
     * homepage already renders both (offer.duration, offer.details) but
     * previously had no backing column, so it always showed the
     * hardcoded "2 Semesters" fallback and an empty list regardless of
     * what the admin set.
     */
    public function up(): void
    {
        Schema::table('tvl_offers', function (Blueprint $table) {
            // Free-text program length, e.g. "2 Semesters", "1 School Year".
            $table->string('duration')->nullable()->after('icon');

            // Ordered list of competency bullet points shown in the
            // public "What You'll Learn" modal panel. Stored as JSON,
            // same pattern as `certifications`.
            $table->json('details')->nullable()->after('certifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tvl_offers', function (Blueprint $table) {
            $table->dropColumn(['duration', 'details']);
        });
    }
};
