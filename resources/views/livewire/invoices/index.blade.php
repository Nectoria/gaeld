@section('title', __('Invoices'))
<x-page-layout>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Invoices') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Manage your invoices and generate Swiss QR bills') }}
                </p>
            </div>
            @can('create', App\Models\Invoice::class)
                <flux:button href="{{ route('invoices.create') }}" icon="plus">
                    {{ __('New Invoice') }}
                </flux:button>
            @endcan
        </div>
    </div>

        <!-- Filters -->
        <x-data-table.filters :show-clear="$this->hasActiveFilters()">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="text"
                :placeholder="__('Search invoices...')"
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="status" :placeholder="__('All Statuses')">
                <option value="">{{ __('All Statuses') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="sent">{{ __('Sent') }}</option>
                <option value="viewed">{{ __('Viewed') }}</option>
                <option value="partial">{{ __('Partially Paid') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="overdue">{{ __('Overdue') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </flux:select>

            <flux:input
                wire:model.live="dateFrom"
                type="date"
                :label="__('From Date')"
            />

            <flux:input
                wire:model.live="dateTo"
                type="date"
                :label="__('To Date')"
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
                                    {{ __('Invoice #') }}
                                </x-data-table.th>
                                <x-data-table.th sortable="invoice_date" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    {{ __('Date') }}
                                </x-data-table.th>
                                <x-data-table.th>{{ __('Customer') }}</x-data-table.th>
                                <x-data-table.th sortable="total_amount" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    {{ __('Total') }}
                                </x-data-table.th>
                                <x-data-table.th sortable="status" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    {{ __('Status') }}
                                </x-data-table.th>
                                <x-data-table.th>{{ __('Actions') }}</x-data-table.th>
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
                <x-data-table.empty :title="__('No invoices found')" icon="document">
                    <x-slot:description>
                        {{ $this->hasActiveFilters() ? __('Try adjusting your filters or search terms.') : __('Get started by creating your first invoice.') }}
                    </x-slot:description>

                    @can('create', App\Models\Invoice::class)
                        @if(!$this->hasActiveFilters())
                            <x-slot:action>
                                <flux:button href="{{ route('invoices.create') }}" icon="plus">
                                    {{ __('Create Invoice') }}
                                </flux:button>
                            </x-slot:action>
                        @endif
                    @endcan
                </x-data-table.empty>
            @endif
        </x-data-table.wrapper>
</x-page-layout>
