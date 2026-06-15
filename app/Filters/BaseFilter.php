<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

// temporary base filter class, will be removed once we have a
// better understanding of the filtering requirements of the app
abstract class BaseFilter
{
    protected array $searchable = [];
    protected array $sortable = [];
    protected array $filters = [];

    public function apply(Builder $query, array $params): Builder
    {
        $this->applySearch($query, $params['search'] ?? null);
        $this->applyFilters($query, $params);
        $this->applySort($query, $params);

        return $query;
    }

    protected function applySearch(Builder $query, ?string $search): void
    {
        if (!$search)
            return;

        $query->where(function ($q) use ($search) {
            foreach ($this->searchable as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });
    }

    protected function applySort(Builder $query, array $params): void
    {
        $sort = $params['sort'] ?? null;
        $dir = $params['direction'] ?? 'asc';

        if (!$sort || !in_array($sort, $this->sortable))
            return;

        $query->orderBy($sort, $dir);
    }

    protected function applyFilters(Builder $query, array $params): void
    {
        foreach ($this->filters as $key => $callback) {
            if (isset($params[$key])) {
                $callback($query, $params[$key]);
            }
        }
    }
}
