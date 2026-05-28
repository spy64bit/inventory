<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class CustomerFIlter extends QueryFilter
{
    protected function allowedSorts(): array
    {
        return ['id', 'name', 'email', 'contact_no', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('contact_no', 'like', "%{$search}%");
        });
    }
}
