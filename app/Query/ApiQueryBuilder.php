<?php

namespace App\Query;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ApiQueryBuilder
{
    public static function apply(
        Builder $query,
        Request $request,
        array $searchable = [],
        array $sortable = [],
        array $filterable = []
    ){
        if ($request->filled('search') && !empty($searchable)) {
            $search = $request->search;

            $query->where(function ($q) use ($search, $searchable) {
                foreach ($searchable as $field) {
                    $q->orWhere($field, 'LIKE', "%{$search}%");
                }
            });
        }

        foreach ($filterable as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->$field);
            }
        }

        if ($request->filled('sort_by') && in_array($request->sort_by, $sortable)) {
            $query->orderBy(
                $request->sort_by,
                $request->sort_direction ?? 'asc'
            );
        }

        return $query;
    }

}
