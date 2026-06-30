<?php

namespace Database\Seeders;

use App\Enums\Announcements\AnnouncementStatus;
use App\Enums\Announcements\AnnouncementUrgency;
use App\Enums\Announcements\DisseminationMode;
use App\Enums\Announcements\TargetAudience;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Requires RolesAndPermissionSeeder + SystemAdminSeeder to have run first
     * (announcements.created_by is a non-nullable FK to users).
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        // Prefer the seeded admin account as the author; fall back to any user.
        $author = User::where('email', 'admin@gmail.com')->first()
            ?? User::first();

        if (! $author) {
            $this->command?->warn('AnnouncementSeeder skipped — no users found. Run SystemAdminSeeder first.');
            return;
        }

        $titles = [
            'Enrollment for SY 2026–2027 Now Open',
            'Brigada Eskwela Schedule Released',
            '4th Quarter Exam Schedule Posted',
            'School Closed – Holy Week',
            'Parent-Teacher Conference Reminder',
            'Updated School ID Requirements',
            'Flag Ceremony Schedule Change',
            'Library Hours Extended for Finals Week',
            'Athletics Tryouts Announcement',
            'New Health Protocol Guidelines',
            'Scholarship Application Deadline Approaching',
            'School Clinic Maintenance Notice',
        ];

        $statuses = AnnouncementStatus::cases();
        $urgencies = AnnouncementUrgency::cases();
        $audiences = TargetAudience::cases();
        $modesPool = DisseminationMode::values();

        foreach ($titles as $title) {
            $status = $faker->randomElement($statuses);

            // Posted announcements have a posted_at in the past; scheduled ones
            // have a future scheduled_at and no posted_at yet; everything else
            // (draft/processing/failed/cancelled) has neither.
            $postedAt = $status === AnnouncementStatus::Posted
                ? $faker->dateTimeBetween('-2 months', 'now')
                : null;

            $scheduledAt = $status === AnnouncementStatus::Scheduled
                ? $faker->dateTimeBetween('now', '+2 weeks')
                : null;

            Announcement::create([
                'uuid' => (string) Str::uuid(),
                'title' => $title,
                'message' => $faker->paragraphs(rand(1, 3), true),
                'status' => $status,
                'urgency' => $faker->randomElement($urgencies),
                'dissemination_modes' => $faker->randomElements(
                    $modesPool,
                    rand(1, count($modesPool))
                ),
                'target_audience' => $faker->randomElement($audiences),
                'scheduled_at' => $scheduledAt,
                'posted_at' => $postedAt,
                'created_by' => $author->id,
            ]);
        }
    }
}
