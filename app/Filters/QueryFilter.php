<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class QueryFilter
{
    public function __construct(protected Request $request) {}

    abstract protected function allowedSorts(): array;

    abstract protected function defaultSort(): string;

    abstract protected function applySearch(Builder $query, string $search): Builder;

    protected function allowedWith(): array
    {
        return [];
    }

    public function apply(Builder $query): LengthAwarePaginator
    {
        if ($search = $this->sanitizeSearch()) {
            $query = $this->applySearch($query, $search);
        }

        if ($with = $this->allowedWith()) {
            $query->with($with);
        }

        $query->orderBy($this->resolveSort(), $this->resolveDirection());

        return $query->paginate($this->resolvePerPage())->withQueryString();
    }

    public function filters(): array
    {
        return $this->request->only(['search', 'sort', 'direction', 'per_page']);
    }

    private function sanitizeSearch(): ?string
    {
        $search = $this->request->input('search');

        if (blank($search)) {
            return null;
        }

        $search = strip_tags(trim((string) $search));

        if (mb_strlen($search) > 100) {
            return null;
        }

        return $search;
    }

    private function resolveSort(): string
    {
        $sort = $this->request->input('sort', $this->defaultSort());

        return in_array($sort, $this->allowedSorts(), true)
            ? $sort
            : $this->defaultSort();
    }

    private function resolveDirection(): string
    {
        $direction = $this->request->input('direction', 'desc');

        return in_array($direction, ['asc', 'desc'], true)
            ? $direction
            : 'desc';
    }

    private function resolvePerPage(): int
    {
        $perPage = (int) $this->request->input('per_page', 10);

        return max(1, min($perPage, 100));
    }
}
