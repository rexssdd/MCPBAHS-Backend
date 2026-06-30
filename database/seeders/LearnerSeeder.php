<?php

namespace Database\Seeders;

use App\Models\Learner;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * LearnerSeeder
 *
 * Seeds learners (enrolled students) and distributes them across the
 * sections created by SectionSeeder. Field names mirror the DepEd
 * enrollment form fields used on the frontend (G7Form.jsx EMPTY_FORM)
 * converted to snake_case, matching the convention already used by
 * enrollmentService.js (first_name, last_name, birth_date, contact_number…):
 *
 *   learner_id, lrn, last_name, first_name, middle_name, name_ext,
 *   birth_date, sex, age, mother_tongue, religion, place_of_birth,
 *   is_ip, ip_specify, is_4ps, household_id, is_pwd,
 *   house_no, barangay, street_name, municipality, province, country,
 *   zip_code, contact_number,
 *   father_last, father_first, father_middle,
 *   mother_last, mother_first, mother_middle,
 *   grade_level, section_id, enrollment_status, school_year
 *
 * ── ASSUMPTIONS — adjust to match your actual schema if different ──
 *   • Model: App\Models\Learner   (table: learners)
 *   • FK:    learners.section_id  → sections.id
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
            $this->command?->warn('LearnerSeeder: App\\Models\\Learner not found — skipping. '
                . 'Update the model namespace in LearnerSeeder.php if your model lives elsewhere.');
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

                Learner::updateOrCreate(
                    [
                        // Treat LRN as the natural unique key so re-running
                        // the seeder doesn't duplicate learners.
                        'lrn' => (string) $lrnSequence,
                    ],
                    [
                        'uuid'           => (string) Str::uuid(),
                        'learner_id'     => 'LRN-' . $schoolYear . '-' . str_pad((string) ($lrnSequence % 100000), 5, '0', STR_PAD_LEFT),

                        'last_name'      => $last,
                        'first_name'     => $first,
                        'middle_name'    => $this->randomItem($this->middleNames),
                        'name_ext'       => null,

                        'birth_date'     => $this->randomBirthDateForGrade($section->grade_level),
                        'sex'            => $sex,
                        'age'            => $this->ageForGrade($section->grade_level),
                        'mother_tongue'  => 'Filipino',
                        'religion'       => $this->randomItem(['Roman Catholic', 'Iglesia ni Cristo', 'Born Again Christian', 'Islam']),
                        'place_of_birth' => 'Quezon City',

                        'is_ip'          => 'No',
                        'ip_specify'     => null,
                        'is_4ps'         => $this->randomItem(['Yes', 'No', 'No', 'No']),
                        'household_id'   => null,
                        'is_pwd'         => 'No',

                        'house_no'       => (string) random_int(1, 999),
                        'barangay'       => $this->randomItem($this->barangays),
                        'street_name'    => 'Sampaguita Street',
                        'municipality'   => 'Quezon City',
                        'province'       => 'Metro Manila',
                        'country'        => 'Philippines',
                        'zip_code'       => '1121',
                        'contact_number' => '09' . random_int(100000000, 999999999),

                        'father_last'    => $last,
                        'father_first'   => $this->randomItem($this->firstNames),
                        'father_middle'  => $this->randomItem($this->middleNames),

                        'mother_last'    => $this->randomItem($this->lastNames),
                        'mother_first'   => $this->randomItem($this->firstNames),
                        'mother_middle'  => $this->randomItem($this->middleNames),

                        'grade_level'      => $section->grade_level,
                        'section_id'       => $section->id,
                        'school_year'      => $section->school_year ?? $schoolYear,
                        'enrollment_status' => 'enrolled',
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