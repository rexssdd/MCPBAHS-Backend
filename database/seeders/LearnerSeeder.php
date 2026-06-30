<?php

namespace Database\Seeders;

use App\Models\Learner;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * LearnerSeeder
 *
 * Seeds learners (enrolled students) and distributes them across the
 * sections created by SectionSeeder. Field names match the actual
 * `learners` table (see database/migrations/..._create_learners_table.php
 * and App\Models\Learner::$fillable) — NOT the old enrollment-form field
 * names this seeder previously (incorrectly) assumed.
 *
 *   • Model: App\Models\Learner   (table: learners)
 *   • FK:    learners.section_assignment_id → sections.id
 *   • Run AFTER SectionSeeder (sections must exist first).
 */
class LearnerSeeder extends Seeder
{
    use WithoutModelEvents;

    /** How many learners to create per section. */
    private int $perSection = 25;

    private array $firstNames = [
        'Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Liza', 'Carlos', 'Rosa',
        'Miguel', 'Carmela', 'Antonio', 'Teresa', 'Ramon', 'Cecilia', 'Jericho',
        'Bianca', 'Andres', 'Joy', 'Mark', 'Angel', 'Paolo', 'Krystal', 'Noel',
        'Faith', 'Vince', 'Hazel', 'Gabriel', 'Mikaela', 'Daniel', 'Patricia',
    ];

    private array $lastNames = [
        'Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Mendoza', 'Torres',
        'Gonzales', 'Ramos', 'Flores', 'Aquino', 'Castillo', 'Villanueva',
        'De Leon', 'Salazar', 'Marquez', 'Aguilar', 'Domingo', 'Pascual', 'Rivera',
    ];

    private array $middleNames = [
        'Dela Cruz', 'Santos', 'Reyes', 'Bautista', 'Garcia', 'Mendoza', 'Torres',
    ];

    private array $barangays = [
        'Barangay Holy Spirit', 'Barangay Batasan Hills', 'Barangay Commonwealth',
        'Barangay Payatas', 'Barangay Fairview', 'Barangay Pasong Tamo',
    ];

    public function run(): void
    {
        if (! class_exists(Learner::class)) {
            $this->command?->warn('LearnerSeeder: App\\Models\\Learner not found — skipping.');
            return;
        }

        $sections = Section::all();

        if ($sections->isEmpty()) {
            $this->command?->warn('LearnerSeeder: no sections found — run SectionSeeder first.');
            return;
        }

        $schoolYear  = $this->currentSchoolYear();
        $lrnSequence = 100000000001; // 12-digit DepEd-style LRN, incremented per learner

        foreach ($sections as $section) {
            for ($i = 0; $i < $this->perSection; $i++) {
                $sex   = $this->randomItem(['Male', 'Female']);
                $first = $this->randomItem($this->firstNames);
                $last  = $this->randomItem($this->lastNames);
                $lrn   = (string) $lrnSequence;

                Learner::updateOrCreate(
                    [
                        // Treat LRN as the natural unique key so re-running
                        // the seeder doesn't duplicate learners.
                        'lrn' => $lrn,
                    ],
                    [
                        'uuid'             => (string) Str::uuid(),
                        'school_year'      => $section->school_year ?? $schoolYear,
                        'grade_to_enroll'  => $section->grade_level,
                        'learner_type'     => 'old student',
                        'enrollment_status' => 'enrolled',

                        'section_assignment_id' => $section->id,

                        'has_lrn' => DB::raw('true'),
                        'lrn'     => $lrn,

                        'last_name'      => $last,
                        'first_name'     => $first,
                        'middle_name'    => $this->randomItem($this->middleNames),
                        'name_extension' => null,

                        'birth_date'     => $this->randomBirthDateForGrade($section->grade_level),
                        'sex'            => $sex,
                        'age'            => $this->ageForGrade($section->grade_level),
                        'mother_tongue'  => 'Filipino',
                        'religion'       => $this->randomItem(['Roman Catholic', 'Iglesia ni Cristo', 'Born Again Christian', 'Islam']),
                        'place_of_birth' => 'Quezon City',

                        'is_ip'             => DB::raw('false'),
                        'ip_specification'  => null,
                        'is_4ps'            => DB::raw($this->randomItem(['true', 'false', 'false', 'false'])),
                        'household_id_number' => null,
                        'is_pwd'            => DB::raw('false'),
                        'pwd_specification' => null,

                        'house_no_street' => (string) random_int(1, 999),
                        'street_name'     => 'Sampaguita Street',
                        'barangay'        => $this->randomItem($this->barangays),
                        'municipality'    => 'Quezon City',
                        'province'        => 'Metro Manila',
                        'country'         => 'Philippines',
                        'zip_code'        => '1121',
                        'contact_number'  => '09' . random_int(100000000, 999999999),

                        'father_last_name'   => $last,
                        'father_first_name'  => $this->randomItem($this->firstNames),
                        'father_middle_name' => $this->randomItem($this->middleNames),

                        'mother_last_name'   => $this->randomItem($this->lastNames),
                        'mother_first_name'  => $this->randomItem($this->firstNames),
                        'mother_middle_name' => $this->randomItem($this->middleNames),

                        'last_grade_completed' => $this->previousGrade($section->grade_level),

                        'academic_track'  => $section->academic_track,
                        'academic_strand' => $section->academic_strand,

                        'image_usage_consent'  => DB::raw('true'),
                        'data_privacy_consent' => DB::raw('true'),
                        'consented_at'         => now(),
                    ]
                );

                $lrnSequence++;
            }
        }

        $this->command?->info(
            "LearnerSeeder: seeded {$this->perSection} learners per section across {$sections->count()} sections."
        );
    }

    private function randomItem(array $items)
    {
        return $items[array_rand($items)];
    }

    private function ageForGrade(string $gradeLevel): int
    {
        // Grade 7 ≈ 12, Grade 12 ≈ 17 — DepEd's typical age bracket.
        $num = (int) preg_replace('/\D/', '', $gradeLevel);
        $num = $num ?: 7;
        return min(18, max(11, $num + 5));
    }

    private function previousGrade(string $gradeLevel): string
    {
        $num = (int) preg_replace('/\D/', '', $gradeLevel);
        $num = $num ?: 7;
        return $num <= 1 ? 'Kinder' : 'Grade ' . ($num - 1);
    }

    private function randomBirthDateForGrade(string $gradeLevel): string
    {
        $age = $this->ageForGrade($gradeLevel);
        $year = (int) date('Y') - $age;
        $month = str_pad((string) random_int(1, 12), 2, '0', STR_PAD_LEFT);
        $day   = str_pad((string) random_int(1, 28), 2, '0', STR_PAD_LEFT);
        return "{$year}-{$month}-{$day}";
    }

    private function currentSchoolYear(): string
    {
        $year = (int) date('Y');
        return "{$year}-" . ($year + 1);
    }
}