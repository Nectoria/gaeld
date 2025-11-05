<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ApiQueryFilter
{
    /**
     * Apply common query filters to a builder instance
     */
    protected function applyFilters(Builder $query, Request $request, array $filterableFields = []): Builder
    {
        foreach ($filterableFields as $field => $config) {
            if (! $request->has($field)) {
                continue;
            }

            $value = $request->input($field);
            $type = is_array($config) ? ($config['type'] ?? 'exact') : $config;

            match ($type) {
                'exact' => $query->where($field, $value),
                'like' => $query->where($field, 'like', "%{$value}%"),
                'date_from' => $query->whereDate($config['column'] ?? $field, '>=', $value),
                'date_to' => $query->whereDate($config['column'] ?? $field, '<=', $value),
                'in' => $query->whereIn($field, is_array($value) ? $value : [$value]),
                'search' => $this->applySearch($query, $config['columns'], $value),
                default => $query->where($field, $value),
            };
        }

        return $query;
    }

    /**
     * Apply search across multiple columns
     */
    protected function applySearch(Builder $query, array $columns, string $search): Builder
    {
        return $query->where(function ($q) use ($columns, $search) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, Request $request, string $defaultField = 'created_at', string $defaultOrder = 'desc'): Builder
    {
        $sortField = $request->get('sort', $defaultField);
        $sortOrder = strtolower($request->get('order', $defaultOrder));

        // Validate sort order
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : $defaultOrder;

        return $query->orderBy($sortField, $sortOrder);
    }

    /**
     * Get pagination parameters from request
     */
    protected function getPaginationParams(Request $request, int $defaultPerPage = 15, int $maxPerPage = 100): int
    {
        return min($request->get('per_page', $defaultPerPage), $maxPerPage);
    }

    /**
     * Apply filters, sorting, and pagination in one go
     */
    protected function applyQueryModifiers(
        Builder $query,
        Request $request,
        array $filterableFields = [],
        string $defaultSort = 'created_at',
        string $defaultOrder = 'desc'
    ): Builder {
        return $this->applyFilters($query, $request, $filterableFields)
            ->pipe(fn ($q) => $this->applySorting($q, $request, $defaultSort, $defaultOrder));
    }
}
