<div>
    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">Contacts</h1>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Manage your customers and vendors
                    </p>
                </div>
                @can('create', App\Models\Contact::class)
                    <flux:button href="{{ route('contacts.create') }}" icon="plus">
                        New Contact
                    </flux:button>
                @endcan
            </div>
        </div>

        <!-- Filters -->
        <x-data-table.filters :show-clear="$search || $type">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search contacts..."
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="type" placeholder="All Types">
                <option value="">All Types</option>
                <option value="customer">Customers</option>
                <option value="vendor">Vendors</option>
                <option value="both">Both</option>
            </flux:select>
        </x-data-table.filters>

        <!-- Table -->
        <x-data-table.wrapper>
            @if($this->contacts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <x-data-table.th sortable="name" :sort-by="$sortBy" :sort-direction="$sortDirection">
                                    Name
                                </x-data-table.th>
                                <x-data-table.th>Type</x-data-table.th>
                                <x-data-table.th>Contact</x-data-table.th>
                                <x-data-table.th>Location</x-data-table.th>
                                <x-data-table.th>Actions</x-data-table.th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->contacts as $contact)
                                <x-contacts.table-row :contact="$contact" />
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <x-data-table.pagination :paginator="$this->contacts" />
            @else
                <x-data-table.empty title="No contacts found" icon="users">
                    <x-slot:description>
                        {{ ($search || $type) ? 'Try adjusting your filters or search terms.' : 'Get started by creating your first contact.' }}
                    </x-slot:description>

                    @can('create', App\Models\Contact::class)
                        @if(!$search && !$type)
                            <x-slot:action>
                                <flux:button href="{{ route('contacts.create') }}" icon="plus">
                                    Create Contact
                                </flux:button>
                            </x-slot:action>
                        @endif
                    @endcan
                </x-data-table.empty>
            @endif
        </x-data-table.wrapper>
    </div>
</div>
