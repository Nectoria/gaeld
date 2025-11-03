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
        <x-data-table.filters :show-clear="$this->hasActiveFilters()">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search invoices..."
                icon="magnifying-glass"
            />

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

            <flux:input
                wire:model.live="dateFrom"
                type="date"
                label="From Date"
            />

            <flux:input
                wire:model.live="dateTo"
                type="date"
                label="To Date"
            />
        </x-data-table.filters>

        <!-- Table -->
        <x-data-table.wrapper>
            @if($this->invoices->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <x-data-table.th sortable="invoice_number" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    Invoice #
                                </x-data-table.th>
                                <x-data-table.th sortable="invoice_date" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    Date
                                </x-data-table.th>
                                <x-data-table.th>Customer</x-data-table.th>
                                <x-data-table.th sortable="total_amount" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    Total
                                </x-data-table.th>
                                <x-data-table.th sortable="status" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    Status
                                </x-data-table.th>
                                <x-data-table.th>Actions</x-data-table.th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->invoices as $invoice)
                                <x-invoices.table-row :invoice="$invoice" />
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-data-table.pagination :paginator="$this->invoices" />
            @else
                <x-data-table.empty title="No invoices found" icon="document">
                    <x-slot:description>
                        {{ $this->hasActiveFilters() ? 'Try adjusting your filters or search terms.' : 'Get started by creating your first invoice.' }}
                    </x-slot:description>

                    @can('create', App\Models\Invoice::class)
                        @if(!$this->hasActiveFilters())
                            <x-slot:action>
                                <flux:button href="{{ route('invoices.create') }}" icon="plus">
                                    Create Invoice
                                </flux:button>
                            </x-slot:action>
                        @endif
                    @endcan
                </x-data-table.empty>
            @endif
        </x-data-table.wrapper>
    </div>
</div>
