<?php

namespace Database\Seeders;

use App\Enums\Reports\ReportStatus;
use App\Enums\Reports\ReportType;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Requires SystemAdminSeeder to have run first — submitted_by / reviewed_by
     * are nullable FKs to users, but we prefer real seeded accounts when present
     * so the dashboard shows realistic submitter/reviewer names.
     */
    public function run(): void
    {
        $faker = \Faker\Factory::create();

        $teacher = User::where('email', 'teacher@gmail.com')->first();
        $admin = User::where('email', 'admin@gmail.com')->first();
        $principal = User::where('email', 'principal@gmail.com')->first();

        $schoolYears = ['2024-2025', '2025-2026', '2026-2027'];
        $statuses = ReportStatus::cases();
        $types = ReportType::cases();

        // Generate a handful of reports per form type so every SF1–SF10 type
        // has at least a few rows across different statuses.
        foreach ($types as $type) {
            foreach (range(1, 3) as $i) {
                $status = $faker->randomElement($statuses);

                $reviewedAt = in_array($status, [ReportStatus::Approved, ReportStatus::Rejected, ReportStatus::ForPrincipalApproval], true)
                    ? $faker->dateTimeBetween('-1 month', 'now')
                    : null;

                $reviewer = in_array($status, [ReportStatus::ForPrincipalApproval, ReportStatus::Approved, ReportStatus::Rejected], true)
                    ? ($admin?->id)
                    : null;

                // Approved reports that reached principal stage get reviewed_by
                // swapped to the principal to reflect final sign-off.
                if ($status === ReportStatus::Approved && $principal) {
                    $reviewer = $principal->id;
                }

                $filename = strtoupper($type->value) . '-' . $faker->unique()->numerify('####') . '.pdf';

                Report::create([
                    // DatabaseSeeder wraps all seeders in WithoutModelEvents, which
                    // disables the `creating` event HasPublicUuid relies on to
                    // auto-generate this — must set it explicitly here.
                    'uuid' => (string) \Illuminate\Support\Str::uuid(),
                    'form_type' => $type,
                    'school_year' => $faker->randomElement($schoolYears),
                    'file_path' => 'reports/' . $filename,
                    'original_filename' => $filename,
                    'mime_type' => 'application/pdf',
                    'file_size' => $faker->numberBetween(20_000, 2_000_000),
                    'status' => $status,
                    'remarks' => $faker->optional(0.4)->sentence(),
                    'submitted_by' => $teacher?->id,
                    'reviewed_by' => $reviewer,
                    'reviewed_at' => $reviewedAt,
                ]);
            }
        }
    }
}
