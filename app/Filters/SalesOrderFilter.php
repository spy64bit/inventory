<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class SalesOrderFilter extends QueryFilter
{
    protected function allowedSorts(): array
    {
        return ['id', 'status', 'created_at', 'confirmed_at', 'fulfilled_at'];
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    protected function allowedWith(): array
    {
        return ['customer:id,name', 'createdBy:id,name'];
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
        });
    }
}
