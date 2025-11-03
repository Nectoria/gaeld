<?php

namespace App\Livewire\Traits;

use Livewire\WithPagination;

trait HasDataTable
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = '';
    public string $sortDirection = 'asc';
    public int $perPage = 15;

    /**
     * Reset pagination when search is updated
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update sorting for a given column
     */
    public function updateSorting(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->sortBy = '';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    /**
     * Check if a column is currently being sorted
     */
    public function isSortedBy(string $column): bool
    {
        return $this->sortBy === $column;
    }

    /**
     * Get the sort direction icon
     */
    public function getSortIcon(string $column): string
    {
        if (!$this->isSortedBy($column)) {
            return '';
        }

        return $this->sortDirection === 'asc' ? '↑' : '↓';
    }

    /**
     * Check if any filters are active
     */
    public function hasActiveFilters(): bool
    {
        return !empty($this->search);
    }
}
