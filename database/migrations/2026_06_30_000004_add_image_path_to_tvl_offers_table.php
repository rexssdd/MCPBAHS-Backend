<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds an image to TVL offers so the homepage TVL track cards and the
     * admin TVL offers manager (AdminTvlOffersPage.jsx) can show a real
     * photo instead of only an emoji icon.
     */
    public function up(): void
    {
        Schema::table('tvl_offers', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('tvl_offers', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
