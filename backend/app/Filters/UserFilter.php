<?php

namespace App\Filters;

class UserFilter extends BaseFilter
{
    protected array $searchable = [
        'name',
        'email',
    ];

    protected array $sortable = [
        'name',
        'email',
        'created_at',
    ];

    protected array $filters = [];

    public function __construct()
    {
        $this->filters = [
            'account_status' => fn($query, $value) =>
                $query->where('account_status', $value),
        ];
    }
}
