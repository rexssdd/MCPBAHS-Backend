<?php

namespace Database\Seeders;

use App\Models\ClassSchedule;
use App\Models\Personnel;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * ClassScheduleSeeder
 *
 * Seeds a weekly class schedule for every section created by SectionSeeder,
 * matching the payload shape used by the frontend (schedulingService.js →
 * toBackendPayload):
 *
 *   subject, school_year, semester, room_no,
 *   days (string[] e.g. ["Monday","Wednesday"]), start_time, end_time,
 *   section_id (resolved from section_uuid on the frontend), teacher_id
 *
 * Each section gets one subject block per weekday pair (Mon/Wed, Tue/Thu,
 * Fri standalone), cycling through the standard DepEd subject list for its
 * grade band and assigning a teacher round-robin so the same set of
 * teachers don't all get identical loads.
 *
 * ── ASSUMPTIONS — adjust to match your actual schema if different ──
 *   • Model:  App\Models\ClassSchedule   (table: class_schedules)
 *   • `days`  is cast to array/json on the model (json column)
 *   • FKs:    class_schedules.section_id → sections.id
 *             class_schedules.teacher_id → personnels.id
 *   • Run AFTER SectionSeeder (and ideally after LearnerSeeder, though
 *     class schedules don't depend on learners directly).
 */
class ClassScheduleSeeder extends Seeder
{
    use WithoutModelEvents;

    private array $juniorHighSubjects = [
        'Filipino', 'English', 'Mathematics', 'Science',
        'Araling Panlipunan', 'MAPEH', 'Edukasyon sa Pagpapakatao',
        'Technology and Livelihood Education',
    ];

    private array $seniorHighSubjects = [
        'Oral Communication', 'General Mathematics', 'Earth and Life Science',
        'Personal Development', 'Physical Education and Health',
        'Empowerment Technologies', 'Practical Research 1',
        'Understanding Culture, Society and Politics',
    ];

    /** Mon/Wed, Tue/Thu, Fri-only — three recurring slot patterns per week. */
    private array $dayPatterns = [
        ['Monday', 'Wednesday'],
        ['Tuesday', 'Thursday'],
        ['Friday'],
    ];

    private array $rooms = ['Room 101', 'Room 102', 'Room 201', 'Room 202', 'Science Lab', 'TLE Workshop'];

    public function run(): void
    {
        if (! class_exists(ClassSchedule::class)) {
            $this->command?->warn('ClassScheduleSeeder: App\\Models\\ClassSchedule not found — skipping. '
                . 'Update the model namespace in ClassScheduleSeeder.php if your model lives elsewhere.');
            return;
        }

        $sections = Section::all();
        if ($sections->isEmpty()) {
            $this->command?->warn('ClassScheduleSeeder: no sections found — run SectionSeeder first.');
            return;
        }

        $teachers = $this->resolveTeacherPool();
        $teacherCursor = 0;
        $nextTeacherId = function () use ($teachers, &$teacherCursor) {
            if ($teachers->isEmpty()) {
                return null;
            }
            $teacher = $teachers[$teacherCursor % $teachers->count()];
            $teacherCursor++;
            return $teacher->id;
        };

        $schoolYear = $this->currentSchoolYear();
        $semester   = 'First Semester';

        $created = 0;

        foreach ($sections as $section) {
            $isSeniorHigh = in_array($section->grade_level, ['Grade 11', 'Grade 12'], true);
            $subjects     = $isSeniorHigh ? $this->seniorHighSubjects : $this->juniorHighSubjects;

            // 7:30am start, 1-hour blocks, one block per subject/day-pattern.
            $startHour = 7;
            $startMin  = 30;

            foreach ($subjects as $i => $subject) {
                $pattern = $this->dayPatterns[$i % count($this->dayPatterns)];

                $start = sprintf('%02d:%02d', $startHour, $startMin);
                $endMinutes = $startMin + 60;
                $endHour = $startHour + intdiv($endMinutes, 60);
                $end = sprintf('%02d:%02d', $endHour, $endMinutes % 60);

                ClassSchedule::updateOrCreate(
                    [
                        'section_id'  => $section->id,
                        'subject'     => $subject,
                        'school_year' => $schoolYear,
                    ],
                    [
                        'uuid'        => (string) Str::uuid(),
                        'semester'    => $isSeniorHigh ? $semester : null,
                        'days'        => $pattern,
                        'start_time'  => $start,
                        'end_time'    => $end,
                        'room_no'     => $this->rooms[$i % count($this->rooms)],
                        'teacher_id'  => $nextTeacherId(),
                    ]
                );

                $created++;

                // Advance the time block for the next subject; wrap after
                // the morning block so afternoon subjects start at 1:00pm.
                $startMin += 60;
                if ($startMin >= 60) {
                    $startHour += intdiv($startMin, 60);
                    $startMin  %= 60;
                }
                if ($startHour === 10) { // mid-morning recess
                    $startHour = 10;
                    $startMin  = 30;
                }
                if ($startHour === 12) { // lunch break
                    $startHour = 13;
                    $startMin  = 0;
                }
            }
        }

        $this->command?->info("ClassScheduleSeeder: seeded {$created} class schedule entries across {$sections->count()} sections.");
    }

    private function resolveTeacherPool()
    {
        if (! class_exists(Personnel::class)) {
            return collect();
        }

        try {
            if (\Illuminate\Support\Facades\Schema::hasColumn('personnels', 'role')) {
                $pool = Personnel::where('role', 'teacher')->get();
                if ($pool->isNotEmpty()) {
                    return $pool;
                }
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('personnels', 'position')) {
                $pool = Personnel::where('position', 'like', '%Teacher%')->get();
                if ($pool->isNotEmpty()) {
                    return $pool;
                }
            }
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