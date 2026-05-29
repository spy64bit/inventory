<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderFilter extends QueryFilter
{
    protected function allowedSorts(): array
    {
        return ['id', 'status', 'created_at', 'confirmed_at', 'received_at'];
    }

    protected function defaultSort(): string
    {
        return 'id';
    }

    protected function allowedWith(): array
    {
        return ['supplier:id,name', 'createdBy:id,name', 'approvedBy:id,name'];
    }

    protected function applySearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%")
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', "%{$search}%"));
        });
    }
}
