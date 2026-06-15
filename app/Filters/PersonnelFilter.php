<?php

namespace App\Filters;

class PersonnelFilter extends BaseFilter
{
    protected array $searchable = [
        'first_name',
        'last_name',
        'email',
        'personnel_id_number',
    ];

    protected array $sortable = [
        'first_name',
        'last_name',
        'created_at',
        'teaching_load',
    ];

    protected array $filters = [];

    public function __construct()
    {
        $this->filters = [
            'position' => fn ($q, $value) => $q->where('position', $value),
            'employment_status' => fn ($q, $value) => $q->where('employment_status', $value),
            'sex' => fn ($q, $value) => $q->where('sex', $value),
        ];
    }
}
