<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $sortBy = 'invoice_date';
    public string $sortDirection = 'desc';

    /**
     * Get paginated invoices
     */
    #[Computed]
    public function invoices()
    {
        $query = Invoice::query()->with(['contact', 'creator']);

        // Apply search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('invoice_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('contact', function ($q) {
                      $q->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // Apply status filter
        if ($this->status) {
            $query->where('status', $this->status);
        }

        // Apply sorting
        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(15);
    }

    /**
     * Sort by column
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'draft' => 'zinc',
            'sent' => 'blue',
            'viewed' => 'indigo',
            'partial' => 'yellow',
            'paid' => 'green',
            'overdue' => 'red',
            'cancelled' => 'zinc',
            default => 'zinc',
        };
    }

    /**
     * Format money amount
     */
    public function formatMoney(int $amount, string $currency = 'CHF'): string
    {
        return number_format($amount / 100, 2, '.', '\'') . ' ' . $currency;
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Invoices</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Manage your invoices and generate Swiss QR bills
                    </p>
                </div>
                @can('create', App\Models\Invoice::class)
                    <flux:button href="{{ route('invoices.create') }}" icon="plus">
                        New Invoice
                    </flux:button>
                @endcan
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow mb-6">
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search invoices..."
                        icon="magnifying-glass"
                    />
                </div>

                <!-- Status Filter -->
                <div>
                    <flux:select wire:model.live="status" placeholder="All Statuses">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="viewed">Viewed</option>
                        <option value="partial">Partially Paid</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </flux:select>
                </div>

                <!-- Clear Filters -->
                @if($search || $status)
                    <div class="flex items-end">
                        <flux:button
                            wire:click="$set('search', ''); $set('status', '')"
                            variant="ghost"
                        >
                            Clear Filters
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Invoices Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            @if($this->invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th
                                    wire:click="sortBy('invoice_number')"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300"
                                >
                                    Invoice #
                                    @if($sortBy === 'invoice_number')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th
                                    wire:click="sortBy('invoice_date')"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300"
                                >
                                    Date
                                    @if($sortBy === 'invoice_date')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th
                                    wire:click="sortBy('due_date')"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300"
                                >
                                    Due Date
                                    @if($sortBy === 'due_date')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Amount
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->invoices as $invoice)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a
                                            href="{{ route('invoices.show', $invoice) }}"
                                            class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            wire:navigate
                                        >
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                        {{ $invoice->contact->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-600 dark:text-zinc-400">
                                        {{ $invoice->invoice_date->format('d.m.Y') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span @class([
                                            'text-red-600 dark:text-red-400 font-medium' => $invoice->isOverdue(),
                                            'text-zinc-600 dark:text-zinc-400' => !$invoice->isOverdue(),
                                        ])>
                                            {{ $invoice->due_date->format('d.m.Y') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $this->formatMoney($invoice->total_amount, $invoice->currency) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <flux:badge :color="$this->getStatusColor($invoice->status)" size="sm">
                                            {{ ucfirst($invoice->status) }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            @can('view', $invoice)
                                                <a
                                                    href="{{ route('invoices.show', $invoice) }}"
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300"
                                                    wire:navigate
                                                >
                                                    View
                                                </a>
                                            @endcan
                                            @can('update', $invoice)
                                                <a
                                                    href="{{ route('invoices.edit', $invoice) }}"
                                                    class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300"
                                                    wire:navigate
                                                >
                                                    Edit
                                                </a>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $this->invoices->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-zinc-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-1">
                        No invoices found
                    </h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                        @if($search || $status)
                            Try adjusting your filters or search terms.
                        @else
                            Get started by creating your first invoice.
                        @endif
                    </p>
                    @can('create', App\Models\Invoice::class)
                        @if(!$search && !$status)
                            <flux:button href="{{ route('invoices.create') }}" icon="plus">
                                Create Invoice
                            </flux:button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
