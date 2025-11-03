<?php

namespace App\Livewire\Contacts;

use App\Livewire\Traits\HasDataTable;
use App\Services\ContactQueryService;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Index extends Component
{
    use HasDataTable;

    public string $type = '';

    public function mount(): void
    {
        $this->sortBy = 'name';
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function contacts()
    {
        return app(ContactQueryService::class)->getPaginated(
            $this->search ?: null,
            $this->type ?: null,
            $this->sortBy,
            $this->sortDirection,
            $this->perPage
        );
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->type = '';
        $this->sortBy = 'name';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.contacts.index');
    }
}
