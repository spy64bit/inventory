<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends QueryFilter
{
    protected function allowedSorts(): array
    {
        return ['id', 'sku', 'name', 'cost_price', 'reorder_level', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        });
    }
}
