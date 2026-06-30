<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * TvlOfferController previously always stored uploaded images on
     * config('filesystems.default') and assumed the same disk when
     * building the public URL. In production that disk is 's3' per
     * .env.example, but if S3 isn't actually configured (no AWS
     * credentials), the upload would throw and the whole request 500'd.
     *
     * StorageUploader now tries the configured disk first and falls back
     * to the always-available local 'public' disk on failure — but that
     * means the image may not always live on the disk Laravel currently
     * has configured as default. Recording which disk it actually landed
     * on lets getImageUrlAttribute() build the correct URL every time.
     */
    public function up(): void
    {
        Schema::table('tvl_offers', function (Blueprint $table) {
            $table->string('image_disk', 30)->nullable()->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tvl_offers', function (Blueprint $table) {
            $table->dropColumn('image_disk');
        });
    }
};