<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class InvoiceQueryService
{
    /**
     * Build an invoice query with search and filter parameters
     */
    public function buildQuery(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'invoice_date',
        string $sortDirection = 'desc'
    ): Builder {
        $query = Invoice::query()->with(['contact', 'creator']);

        // Apply search filter
        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhere('reference_number', 'like', '%'.$search.'%')
                    ->orWhereHas('contact', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
            });
        }

        // Apply status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Apply date range filters
        if ($dateFrom) {
            $query->where('invoice_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('invoice_date', '<=', $dateTo);
        }

        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);

        return $query;
    }

    /**
     * Get paginated invoices with filters
     */
    public function getPaginated(
        ?string $search = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        string $sortBy = 'invoice_date',
        string $sortDirection = 'desc',
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->buildQuery($search, $status, $dateFrom, $dateTo, $sortBy, $sortDirection)
            ->paginate($perPage);
    }
}
