<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class CategoryFilter extends QueryFilter
{
    protected function allowedSorts(): array
    {
        return ['id', 'name', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
