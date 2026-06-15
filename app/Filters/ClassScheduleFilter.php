<?php

namespace App\Filters;

class ClassScheduleFilter extends BaseFilter
{
    protected array $searchable = [
        'subject',
        'room',
    ];

    protected array $sortable = [
        'start_time',
        'end_time',
        'created_at',
    ];

    protected array $filters = [];

    public function __construct()
    {
        $this->filters = [

            'teacher_uuid' => fn($q, $v) =>
                $q->whereHas(
                    'teacher',
                    fn($q) => $q->where('uuid', $v)
                ),

            'section_uuid' => fn($q, $v) =>
                $q->whereHas(
                    'section',
                    fn($q) => $q->where('uuid', $v)
                ),

            'school_year' => fn($q, $v) =>
                $q->where('school_year', $v),

            'semester' => fn($q, $v) =>
                $q->where('semester', $v),
        ];
    }
}
