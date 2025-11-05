<?php

namespace App\Livewire\Invoices;

use App\Livewire\Traits\HasDataTable;
use App\Services\InvoiceQueryService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    use HasDataTable;

    public string $status = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function mount(): void
    {
        $this->sortBy = 'invoice_date';
        $this->sortDirection = 'desc';
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function invoices()
    {
        return app(InvoiceQueryService::class)->getPaginated(
            $this->search ?: null,
            $this->status ?: null,
            $this->dateFrom ?: null,
            $this->dateTo ?: null,
            $this->sortBy,
            $this->sortDirection,
            $this->perPage
        );
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->sortBy = 'invoice_date';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return ! empty($this->search)
            || ! empty($this->status)
            || ! empty($this->dateFrom)
            || ! empty($this->dateTo);
    }

    public function render()
    {
        return view('livewire.invoices.index');
    }
}
