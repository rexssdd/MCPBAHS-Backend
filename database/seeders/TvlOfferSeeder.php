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
            ['title' => 'Agri-Fishery Arts', 'icon' => '🌱', 'certs' => ['NC II'], 'desc' => 'Hands-on training in crop production, livestock, and aquaculture for learners pursuing agricultural careers.', 'duration' => '2 Semesters', 'details' => ['Crop production & soil management', 'Livestock & poultry raising', 'Basic aquaculture techniques', 'Farm records & enterprise planning']],
            ['title' => 'Home Economics', 'icon' => '🍳', 'certs' => ['NC II'], 'desc' => 'Covers cookery, bread and pastry production, and housekeeping for hospitality-bound learners.', 'duration' => '2 Semesters', 'details' => ['Cookery fundamentals', 'Bread & pastry production', 'Housekeeping operations', 'Food safety & sanitation']],
            ['title' => 'Industrial Arts', 'icon' => '🛠️', 'certs' => ['NC II'], 'desc' => 'Electrical installation, carpentry, and shielded metal arc welding for trade and construction careers.', 'duration' => '2 Semesters', 'details' => ['Electrical installation & maintenance', 'Carpentry & joinery basics', 'Shielded metal arc welding (SMAW)', 'Workplace safety practices']],
            ['title' => 'Information & Communications Technology', 'icon' => '💻', 'certs' => ['NC II'], 'desc' => 'Computer systems servicing and basic programming to prepare learners for IT-related work.', 'duration' => '2 Semesters', 'details' => ['PC hardware assembly & troubleshooting', 'OS installation & configuration', 'Basic networking', 'Intro to programming']],
            ['title' => 'Fishery Arts', 'icon' => '🐟', 'certs' => ['NC II'], 'desc' => 'Fish capture, aquaculture, and fish processing for learners in coastal and fishing communities.', 'duration' => '2 Semesters', 'details' => ['Fish capture techniques', 'Aquaculture systems', 'Fish processing & preservation', 'Post-harvest handling']],
            ['title' => 'Drafting Technology', 'icon' => '📐', 'certs' => ['NC II'], 'desc' => 'Technical drafting fundamentals for learners interested in engineering and architecture pathways.', 'duration' => '2 Semesters', 'details' => ['Manual & CAD drafting basics', 'Blueprint reading', 'Architectural & mechanical drawing', 'Scale & dimensioning standards']],
        ];

        foreach ($tracks as $index => $track) {
            TvlOffer::create([
                'uuid' => (string) Str::uuid(),
                'title' => $track['title'],
                'description' => $track['desc'],
                'icon' => $track['icon'],
                'certifications' => $track['certs'],
                'duration' => $track['duration'],
                'details' => $track['details'],
                'display_order' => $index + 1,
                // Intentionally omitted: 'is_active' => true — see the note
                // in CalendarEventSeeder about PDO pgsql binding PHP booleans
                // as bare integer literals. The migration already defaults
                // this column to true.
            ]);
        }
    }
}