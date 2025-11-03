<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ContactQueryService
{
    /**
     * Build a contact query with search and filter parameters
     */
    public function buildQuery(
        ?string $search = null,
        ?string $type = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc'
    ): Builder {
        $query = Contact::query();

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('contact_person', 'like', '%'.$search.'%');
            });
        }

        // Apply type filter
        if ($type) {
            $query = $this->applyTypeFilter($query, $type);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query;
    }

    /**
     * Get paginated contacts with filters
     */
    public function getPaginated(
        ?string $search = null,
        ?string $type = null,
        string $sortBy = 'name',
        string $sortDirection = 'asc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->buildQuery($search, $type, $sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Apply type filter to query
     */
    protected function applyTypeFilter(Builder $query, string $type): Builder
    {
        return match ($type) {
            'customer' => $query->whereIn('type', ['customer', 'both']),
            'vendor' => $query->whereIn('type', ['vendor', 'both']),
            'both' => $query->where('type', 'both'),
            default => $query,
        };
    }
}
