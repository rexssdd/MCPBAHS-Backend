<?php

namespace App\Services\ClassSchedules;

use App\Models\ClassSchedule;
use Illuminate\Validation\ValidationException;

class ClassScheduleConflictService
{
    public function validate(array $data, ?int $ignoreId = null): void
    {
        // FIX: was returning early when either school_year or semester was absent,
        // which meant schedules created via the compat layer (which often omits
        // semester) were never checked for conflicts. Now semester is treated as
        // optional for the query filter — if absent we validate across ALL semesters
        // for that school_year, which is the conservative/safe behaviour.
        if (! isset($data['school_year'])) {
            return;
        }

        $query = ClassSchedule::query()
            ->where('school_year', $data['school_year']);

        if (isset($data['semester'])) {
            $query->where('semester', $data['semester']);
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        foreach ($query->get() as $existing) {

            if ($this->conflicts($existing, $data)) {
                throw ValidationException::withMessages([
                    'schedule' => 'Schedule conflict detected.',
                ]);
            }
        }
    }

    private function conflicts(ClassSchedule $existing, array $new): bool
    {
        if (!isset($new['days'], $new['start_time'], $new['end_time'])) {
            return false;
        }

        // FIX: $existing->days is cast to array by the model, but $new['days']
        // may arrive as a formatted string ("Mon-Fri") from the compat layer.
        // Normalise both sides to arrays before intersecting so day-overlap
        // detection fires correctly regardless of which path created the schedule.
        $existingDays = $this->normalizeDays($existing->days);
        $newDays      = $this->normalizeDays($new['days']);

        $dayOverlap = count(array_intersect($existingDays, $newDays)) > 0;

        if (!$dayOverlap) {
            return false;
        }

        return $new['start_time'] < $existing->end_time
            && $new['end_time'] > $existing->start_time;
    }

    /**
     * Normalise a days value to a plain string array of day abbreviations.
     * Handles: array ['Mon','Tue'], JSON string '["Mon","Tue"]',
     *          range string 'Mon-Fri', and comma-separated 'Mon,Wed,Fri'.
     */
    private function normalizeDays(mixed $days): array
    {
        if (is_array($days)) {
            return array_map('trim', $days);
        }

        $str = trim((string) $days);

        // JSON-encoded array stored as string
        if (str_starts_with($str, '[')) {
            $decoded = json_decode($str, true);
            if (is_array($decoded)) {
                return array_map('trim', $decoded);
            }
        }

        // Range like "Mon-Fri"
        $rangeMap = [
            'Mon-Fri' => ['Mon','Tue','Wed','Thu','Fri'],
            'Mon-Sat' => ['Mon','Tue','Wed','Thu','Fri','Sat'],
            'Sat-Sun' => ['Sat','Sun'],
        ];
        if (isset($rangeMap[$str])) {
            return $rangeMap[$str];
        }

        // Comma/space separated individual days
        return array_map('trim', preg_split('/[\s,]+/', $str) ?: [$str]);
    }
}
