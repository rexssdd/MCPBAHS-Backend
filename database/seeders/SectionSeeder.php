<?php

namespace Database\Seeders;

use App\Models\Personnel;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * SectionSeeder
 *
 * Seeds one school year's worth of sections for Grades 7–12, matching the
 * payload shape used by the frontend (schedulingService.js → createSection):
 *
 *   section_name, grade_level ("Grade 7" .. "Grade 12"), school_year,
 *   academic_track, academic_strand, adviser_id (FK → personnels.id)
 *
 * Junior High (7–10) sections are plain (no track/strand).
 * Senior High (11–12) sections are split across the four DepEd tracks,
 * with strands populated for Academic-track sections.
 *
 * ── ASSUMPTIONS — adjust to match your actual schema if different ──
 *   • Model:      App\Models\Section          (table: sections)
 *   • Adviser FK: sections.adviser_id          → personnels.id
 *   • Personnel:  App\Models\Personnel         (table: personnels)
 *                 filterable by `role` = 'teacher' (falls back to "any
 *                 personnel" if no `role` column / no teachers found)
 *
 * Run order: SectionSeeder → LearnerSeeder → ClassScheduleSeeder
 * (Learners and schedules both reference sections, so sections go first.)
 */
class SectionSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Section name pools, themed per grade band (DepEd schools commonly
     *  name sections after virtues, scientists, or local heroes). */
    private array $juniorHighNames = [
        'Grade 7'  => ['Mabini', 'Rizal', 'Bonifacio'],
        'Grade 8'  => ['Aguinaldo', 'Luna', 'Silang'],
        'Grade 9'  => ['Faith', 'Hope', 'Charity'],
        'Grade 10' => ['Diligence', 'Integrity', 'Excellence'],
    ];

    // Keys must match App\Enums\Sections\AcademicTrack values exactly
    // (the column has a DB check constraint against those enum values).
    private array $tracks = [
        'Academic' => ['STEM', 'ABM', 'HUMSS', 'GAS'],
        'TVL'       => [null],
        'Sports'    => [null],
        'Arts and Design' => [null],
    ];

    public function run(): void
    {
        $schoolYear = $this->currentSchoolYear();

        // Pull eligible advisers once; gracefully degrade if Personnel
        // has no `role` column or no teachers are seeded yet.
        $teachers = $this->resolveTeacherPool();
        $teacherCursor = 0;

        $nextAdviser = function () use ($teachers, &$teacherCursor) {
            if ($teachers->isEmpty()) {
                return null;
            }
            $adviser = $teachers[$teacherCursor % $teachers->count()];
            $teacherCursor++;
            return $adviser->id;
        };

        // ── Junior High: Grades 7–10, plain sections ──────────────────
        foreach ($this->juniorHighNames as $gradeLevel => $names) {
            foreach ($names as $name) {
                Section::updateOrCreate(
                    [
                        'section_name' => $name,
                        'grade_level'  => $gradeLevel,
                        'school_year'  => $schoolYear,
                    ],
                    [
                        'uuid'            => (string) Str::uuid(),
                        'academic_track'  => null,
                        'academic_strand' => null,
                        'adviser_id'      => $nextAdviser(),
                    ]
                );
            }
        }

        // ── Senior High: Grades 11–12, track + strand ──────────────────
        foreach (['Grade 11', 'Grade 12'] as $gradeLevel) {
            foreach ($this->tracks as $track => $strands) {
                foreach ($strands as $strand) {
                    $label = $strand ?? str_replace(' Track', '', $track);
                    $sectionName = "{$label} - " . ($gradeLevel === 'Grade 11' ? 'A' : 'A');

                    Section::updateOrCreate(
                        [
                            'section_name' => $sectionName,
                            'grade_level'  => $gradeLevel,
                            'school_year'  => $schoolYear,
                        ],
                        [
                            'uuid'            => (string) Str::uuid(),
                            'academic_track'  => $track,
                            'academic_strand' => $strand,
                            'adviser_id'      => $nextAdviser(),
                        ]
                    );
                }
            }
        }

        $this->command?->info('SectionSeeder: seeded sections for Grades 7–12 ('.$schoolYear.').');
    }

    private function resolveTeacherPool()
    {
        if (! class_exists(Personnel::class)) {
            return collect();
        }

        try {
            // Prefer an explicit role column if present.
            if (\Illuminate\Support\Facades\Schema::hasColumn('personnels', 'role')) {
                $pool = Personnel::where('role', 'teacher')->get();
                if ($pool->isNotEmpty()) {
                    return $pool;
                }
            }
            // Fall back to "position contains Teacher".
            if (\Illuminate\Support\Facades\Schema::hasColumn('personnels', 'position')) {
                $pool = Personnel::where('position', 'like', '%Teacher%')->get();
                if ($pool->isNotEmpty()) {
                    return $pool;
                }
            }
            // Last resort: any personnel at all.
            return Personnel::all();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    private function currentSchoolYear(): string
    {
        $year = (int) date('Y');
        return "{$year}-" . ($year + 1);
    }
}