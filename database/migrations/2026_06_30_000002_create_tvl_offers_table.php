<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Backs the public homepage TVL (Technical-Vocational-Livelihood)
     * track section (TVLSection.jsx), which previously fetched a
     * relative `/api/tvl-offers` path that never resolved against the
     * real backend, so it always rendered hard-coded fallback tracks.
     */
    public function up(): void
    {
        Schema::create('tvl_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->string('title');
            $table->text('description')->nullable();

            // Emoji / icon key rendered on the homepage card, e.g. "🌱".
            $table->string('icon')->nullable();

            // Optional list of NC (National Certificate) levels offered,
            // e.g. ["NC II"]. Stored as JSON for flexibility.
            $table->json('certifications')->nullable();

            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tvl_offers');
    }
};
