<?php

namespace App\Filters;

class SectionFilter extends BaseFilter
{
    protected array $searchable = [
        'section_name',
        'school_year',
    ];

    protected array $sortable = [
        'section_name',
        'grade_level',
        'school_year',
    ];

    public function __construct()
    {
        $this->filters = [

            'grade_level' => fn($q, $v) =>
                $q->where('grade_level', $v),

            'school_year' => fn($q, $v) =>
                $q->where('school_year', $v),

            'academic_track' => fn($q, $v) =>
                $q->where('academic_track', $v),

            'academic_strand' => fn($q, $v) =>
                $q->where('academic_strand', $v),

            'adviser_uuid' => fn($q, $v) =>
                $q->whereHas(
                    'adviser',
                    fn($q) =>
                    $q->where('uuid', $v)
                ),
        ];
    }
}
