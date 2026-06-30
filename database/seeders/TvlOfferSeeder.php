<?php

namespace Database\Seeders;

use App\Models\TvlOffer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TvlOfferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the public homepage TVL track list (TVLSection.jsx).
     */
    public function run(): void
    {
        $tracks = [
            ['title' => 'Agri-Fishery Arts', 'icon' => '🌱', 'certs' => ['NC II'], 'desc' => 'Hands-on training in crop production, livestock, and aquaculture for learners pursuing agricultural careers.'],
            ['title' => 'Home Economics', 'icon' => '🍳', 'certs' => ['NC II'], 'desc' => 'Covers cookery, bread and pastry production, and housekeeping for hospitality-bound learners.'],
            ['title' => 'Industrial Arts', 'icon' => '🛠️', 'certs' => ['NC II'], 'desc' => 'Electrical installation, carpentry, and shielded metal arc welding for trade and construction careers.'],
            ['title' => 'Information & Communications Technology', 'icon' => '💻', 'certs' => ['NC II'], 'desc' => 'Computer systems servicing and basic programming to prepare learners for IT-related work.'],
            ['title' => 'Fishery Arts', 'icon' => '🐟', 'certs' => ['NC II'], 'desc' => 'Fish capture, aquaculture, and fish processing for learners in coastal and fishing communities.'],
            ['title' => 'Drafting Technology', 'icon' => '📐', 'certs' => ['NC II'], 'desc' => 'Technical drafting fundamentals for learners interested in engineering and architecture pathways.'],
        ];

        foreach ($tracks as $index => $track) {
            TvlOffer::create([
                'uuid' => (string) Str::uuid(),
                'title' => $track['title'],
                'description' => $track['desc'],
                'icon' => $track['icon'],
                'certifications' => $track['certs'],
                'display_order' => $index + 1,
                'is_active' => true,
            ]);
        }
    }
}
