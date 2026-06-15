<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\Learners\EnrollmentStatus;
use App\Enums\Learners\LearnerType;
use App\Enums\Personnel\EmploymentStatus;
use App\Enums\Reports\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Learner;
use App\Models\Personnel;
use App\Models\Report;
use App\Models\Section;
use Illuminate\Support\Carbon;

class PrincipalDashboardController extends Controller
{
    private const DEFAULT_SECTION_CAPACITY = 40;

    public function stats(): array
    {
        $totalApplications = Learner::count();
        $totalEnrolled = $this->learnerStatusCount(EnrollmentStatus::Enrolled->value);
        $pendingApps = $this->learnerStatusCount(EnrollmentStatus::Pending->value)
            + $this->learnerStatusCount(EnrollmentStatus::PartiallyEnrolled->value);
        $teachingStats = $this->teachingStaffStats();

        return [
            'enrolledToday' => Learner::query()
                ->where('enrollment_status', EnrollmentStatus::Enrolled->value)
                ->whereDate('approved_at', Carbon::today())
                ->count(),
            'totalEnrolled' => $totalEnrolled,
            'pendingApps' => $pendingApps,
            'completionRate' => $this->percentage($totalEnrolled, $totalApplications),
            'atRiskCount' => 0,
            'avgGpa' => 0,
            'passRate' => 0,
            'lowAttendanceSections' => 0,
            'totalSections' => Section::count(),
            'totalStudents' => $totalEnrolled,
            'teachingStaff' => $teachingStats['total'],
            'teachingActive' => $teachingStats['active'],
            'teachingLeave' => $teachingStats['on_leave'],
            'nonTeaching' => max(Personnel::count() - $teachingStats['total'], 0),
            'parentContacts' => Learner::query()
                ->whereNotNull('contact_number')
                ->where('contact_number', '!=', '')
                ->count(),
            'totalCollected' => 0,
            'totalBilled' => 0,
            'unpaidBalances' => 0,
            'unpaidCount' => 0,
            'waiverCount' => 0,
            'overdueReports' => Report::query()
                ->where('status', '!=', ReportStatus::Approved->value)
                ->where('created_at', '<', Carbon::now()->subDays(7))
                ->count(),
        ];
    }

    public function gradeData(): array
    {
        // FIX: was ->get() on all learners then grouping in PHP.
        // One GROUP BY query replaces the full table scan.
        $rows = Learner::query()
            ->selectRaw('grade_to_enroll, sex, COUNT(*) as cnt')
            ->groupBy('grade_to_enroll', 'sex')
            ->get();

        $byGrade = [];
        foreach ($rows as $row) {
            $grade = $this->gradeNumber($row->grade_to_enroll);
            if ($grade === null) continue;

            $byGrade[$grade] ??= ['grade' => $grade, 'male' => 0, 'female' => 0];
            $sex = $this->enumValue($row->sex);
            if ($sex === 'male')        $byGrade[$grade]['male']   += (int) $row->cnt;
            elseif ($sex === 'female')  $byGrade[$grade]['female'] += (int) $row->cnt;
        }

        ksort($byGrade);
        return array_values($byGrade);
    }

    public function enrollmentTable(): array
    {
        $enrolledByGrade = $this->learnerCountsByGrade(
            Learner::query()->where('enrollment_status', EnrollmentStatus::Enrolled->value)->get(['grade_to_enroll'])
        );
        $sectionsByGrade = $this->sectionCountsByGrade();
        $grades = array_unique(array_merge(array_keys($enrolledByGrade), array_keys($sectionsByGrade)));
        sort($grades);

        return array_map(function (int $grade) use ($enrolledByGrade, $sectionsByGrade) {
            $enrolled = $enrolledByGrade[$grade] ?? 0;
            $capacity = ($sectionsByGrade[$grade] ?? 0) * self::DEFAULT_SECTION_CAPACITY;

            if ($capacity <= 0 && $enrolled > 0) {
                $capacity = $enrolled;
            }

            $fillRate = $this->percentage($enrolled, $capacity);

            return [
                'grade' => $grade,
                'enrolled' => $enrolled,
                'capacity' => $capacity,
                'status' => $fillRate >= 100 ? 'Full' : ($fillRate >= 80 ? 'Near' : 'Available'),
            ];
        }, $grades);
    }

    public function applicationStatus(): array
    {
        $enrolled = $this->learnerStatusCount(EnrollmentStatus::Enrolled->value);
        $pending = $this->learnerStatusCount(EnrollmentStatus::Pending->value)
            + $this->learnerStatusCount(EnrollmentStatus::PartiallyEnrolled->value);
        $cancelled = $this->learnerStatusCount(EnrollmentStatus::Rejected->value);

        return [
            'total' => $enrolled + $pending + $cancelled,
            'enrolled' => $enrolled,
            'pending' => $pending,
            'cancelled' => $cancelled,
        ];
    }

    public function attendance(): array
    {
        return [];
    }

    public function atRisk(): array
    {
        return [];
    }

    public function strands(): array
    {
        $counts = [];

        Learner::query()
            ->select(['academic_strand', 'shs_strand'])
            ->where('enrollment_status', EnrollmentStatus::Enrolled->value)
            ->get()
            ->each(function (Learner $learner) use (&$counts) {
                $strand = trim((string) ($learner->academic_strand ?: $learner->shs_strand));
                if ($strand === '') {
                    return;
                }

                $counts[$strand] = ($counts[$strand] ?? 0) + 1;
            });

        ksort($counts);

        return collect($counts)
            ->map(fn (int $count, string $name) => ['name' => $name, 'count' => $count])
            ->values()
            ->all();
    }

    public function transferees(): array
    {
        return [
            'incoming' => Learner::query()
                ->where('learner_type', LearnerType::Transferee->value)
                ->whereIn('enrollment_status', [
                    EnrollmentStatus::Pending->value,
                    EnrollmentStatus::PartiallyEnrolled->value,
                ])
                ->count(),
            'outgoing' => 0,
            'returnees' => Learner::query()
                ->where('learner_type', LearnerType::OldStudent->value)
                ->count(),
            'demographics' => $this->learnerDemographics(),
        ];
    }

    public function teachers(): array
    {
        return Personnel::query()
            ->where('position', 'like', '%TEACHER%')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Personnel $personnel) => [
                'name' => $personnel->full_name,
                'subject' => $this->humanize($this->enumValue($personnel->position)),
                'load' => (int) ($personnel->teaching_load ?? 0),
                'status' => $this->enumValue($personnel->employment_status) === EmploymentStatus::Active->value
                    ? 'Active'
                    : 'On Leave',
            ])
            ->values()
            ->all();
    }

    public function feeCollection(): array
    {
        return [];
    }

    public function events(): array
    {
        return Announcement::query()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>=', Carbon::today())
            ->orderBy('scheduled_at')
            ->limit(8)
            ->get()
            ->map(fn (Announcement $announcement) => [
                'date'  => Carbon::parse($announcement->scheduled_at)->format('M j'),
                'label' => $announcement->title,
                'type'  => $this->announcementType($announcement->urgency),
            ])
            ->values()
            ->all();
    }

    public function notifications(): array
    {
        $notifications = collect();
        $pendingReports = Report::query()
            ->where('status', ReportStatus::ForPrincipalApproval->value)
            ->count();

        if ($pendingReports > 0) {
            $notifications->push([
                'msg' => "{$pendingReports} report(s) awaiting principal approval",
                'type' => 'warn',
                'time' => 'Now',
            ]);
        }

        Announcement::query()
            ->latest()
            ->limit(5)
            ->get()
            ->each(function (Announcement $announcement) use ($notifications) {
                $notifications->push([
                    'msg' => $announcement->title,
                    'type' => $this->announcementType($announcement->urgency),
                    'time' => optional($announcement->created_at)->diffForHumans() ?? 'Now',
                ]);
            });

        return $notifications->values()->all();
    }

    public function recentActivity(): array
    {
        return Learner::query()
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (Learner $learner) => [
                'name' => $this->learnerName($learner),
                'grade' => $this->gradeNumber($learner->grade_to_enroll) ?? 0,
                'time' => optional($learner->updated_at)->diffForHumans() ?? 'Recently',
                'action' => $this->enumValue($learner->enrollment_status) === EnrollmentStatus::Enrolled->value
                    ? 'Enrolled'
                    : 'Pending',
            ])
            ->values()
            ->all();
    }

    public function depedReports(): array
    {
        return Report::query()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (Report $report) => [
                'label' => strtoupper($this->enumValue($report->form_type)),
                'status' => $this->reportStatusLabel($report->status),
            ])
            ->values()
            ->all();
    }

    public function executiveSummary(): array
    {
        $stats = $this->stats();

        return [
            'completionRate' => $stats['completionRate'],
            'avgGpa' => $stats['avgGpa'],
            'passRate' => $stats['passRate'],
            'avgAttendance' => 0,
            'collectionRate' => 0,
            'atRiskCount' => $stats['atRiskCount'],
        ];
    }

    public function schoolHealth(): array
    {
        $stats = $this->stats();

        return [
            'academic' => $stats['passRate'],
            'attendance' => 0,
            'enrollment' => $stats['completionRate'],
            'collection' => 0,
        ];
    }

    public function quarterlySummary(): array
    {
        $totalStudents = $this->learnerStatusCount(EnrollmentStatus::Enrolled->value);

        return [
            'quarter' => 'Q' . Carbon::now()->quarter,
            'schoolYear' => $this->currentSchoolYear(),
            'totalStudents' => $totalStudents,
            'promoted' => 0,
            'retained' => 0,
            'dropped' => 0,
            'honorRoll' => 0,
            'perfectAttendance' => 0,
            'generatedAt' => Carbon::now()->toIso8601String(),
        ];
    }

    public function staffPerformance(): array
    {
        $totalTeachers = $this->teachingStaffStats()['total'];

        return [
            'totalTeachers' => $totalTeachers,
            'rated' => 0,
            'outstanding' => 0,
            'verySatisfactory' => 0,
            'satisfactory' => 0,
            'unsatisfactory' => 0,
            'needsImprovement' => 0,
            'avgRating' => 0,
        ];
    }

    public function sipProgress(): array
    {
        return [];
    }

    private function learnerStatusCount(string $status): int
    {
        return Learner::query()
            ->where('enrollment_status', $status)
            ->count();
    }

    private function learnerCountsByGrade($learners): array
    {
        $counts = [];

        foreach ($learners as $learner) {
            $grade = $this->gradeNumber($learner->grade_to_enroll);
            if ($grade === null) {
                continue;
            }

            $counts[$grade] = ($counts[$grade] ?? 0) + 1;
        }

        return $counts;
    }

    private function sectionCountsByGrade(): array
    {
        // FIX: was ->get()->each(...) — loaded all sections. SQL GROUP BY instead.
        $rows = Section::query()
            ->selectRaw('grade_level, COUNT(*) as cnt')
            ->groupBy('grade_level')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $grade = $this->gradeNumber($row->grade_level);
            if ($grade === null) continue;
            $counts[$grade] = ($counts[$grade] ?? 0) + (int) $row->cnt;
        }
        return $counts;
    }

    private function teachingStaffStats(): array
    {
        // FIX: was Personnel::query()->get()->each(...) — loaded all personnel.
        // GROUP BY gives the same result in one query.
        $rows = Personnel::query()
            ->selectRaw('position, employment_status, COUNT(*) as cnt')
            ->groupBy('position', 'employment_status')
            ->get();

        $stats = ['total' => 0, 'active' => 0, 'on_leave' => 0];

        foreach ($rows as $row) {
            if (! $this->isTeachingPosition($row->position)) continue;

            $cnt    = (int) $row->cnt;
            $status = $this->enumValue($row->employment_status);

            $stats['total'] += $cnt;
            if ($status === EmploymentStatus::Active->value)  $stats['active']   += $cnt;
            elseif ($status === EmploymentStatus::OnLeave->value) $stats['on_leave'] += $cnt;
        }

        return $stats;
    }

    private function learnerDemographics(): array
    {
        // FIX: was two separate COUNT queries. One GROUP BY replaces both.
        $bySex = Learner::query()
            ->selectRaw('sex, COUNT(*) as cnt')
            ->groupBy('sex')
            ->pluck('cnt', 'sex');

        $total = $bySex->sum();
        if ($total === 0) return [];

        $male   = (int) ($bySex['male']   ?? 0);
        $female = (int) ($bySex['female'] ?? 0);

        return [
            ['label' => 'Male learners',   'pct' => $this->percentage($male,   $total) . '%'],
            ['label' => 'Female learners', 'pct' => $this->percentage($female, $total) . '%'],
        ];
    }

    private function currentSchoolYear(): string
    {
        $schoolYear = Learner::query()
            ->whereNotNull('school_year')
            ->latest()
            ->value('school_year');

        if ($schoolYear) {
            return $schoolYear;
        }

        $year = (int) Carbon::now()->year;

        return "{$year}-" . ($year + 1);
    }

    private function gradeNumber(?string $gradeLevel): ?int
    {
        if (! $gradeLevel) {
            return null;
        }

        preg_match('/\d+/', $gradeLevel, $matches);

        return $matches ? (int) $matches[0] : null;
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }

    private function isTeachingPosition(mixed $position): bool
    {
        return str_contains($this->enumValue($position), 'TEACHER');
    }

    private function learnerName(Learner $learner): string
    {
        return trim("{$learner->first_name} {$learner->middle_name} {$learner->last_name}");
    }

    private function announcementType(mixed $urgency): string
    {
        return match ($this->enumValue($urgency)) {
            'high' => 'alert',
            'medium' => 'warn',
            default => 'info',
        };
    }

    private function reportStatusLabel(mixed $status): string
    {
        return match ($this->enumValue($status)) {
            ReportStatus::Approved->value => 'Complete',
            ReportStatus::Rejected->value => 'Rejected',
            default => 'Pending',
        };
    }

    private function percentage(int|float $part, int|float $whole): int
    {
        return $whole > 0 ? (int) round(($part / $whole) * 100) : 0;
    }

    private function humanize(string $value): string
    {
        return ucwords(strtolower(str_replace(['_', '-'], ' ', $value)));
    }
}
