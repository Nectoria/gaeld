@section('title', __('Contacts'))
<x-page-layout>
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">{{ __('Contacts') }}</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Manage your customers and vendors') }}
                </p>
            </div>
            @can('create', App\Models\Contact::class)
                <flux:button href="{{ route('contacts.create') }}" icon="plus">
                    {{ __('New Contact') }}
                </flux:button>
            @endcan
        </div>
    </div>

        <!-- Filters -->
        <x-data-table.filters :show-clear="$search || $type">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="text"
                :placeholder="__('Search contacts...')"
                icon="magnifying-glass"
            />

            <flux:select wire:model.live="type" :placeholder="__('All Types')">
                <option value="">{{ __('All Types') }}</option>
                <option value="customer">{{ __('Customers') }}</option>
                <option value="vendor">{{ __('Vendors') }}</option>
                <option value="both">{{ __('Both') }}</option>
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
                                    {{ __('Name') }}
                                </x-data-table.th>
                                <x-data-table.th>{{ __('Type') }}</x-data-table.th>
                                <x-data-table.th>{{ __('Contact') }}</x-data-table.th>
                                <x-data-table.th>{{ __('Location') }}</x-data-table.th>
                                <x-data-table.th>{{ __('Actions') }}</x-data-table.th>
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
                <x-data-table.empty :title="__('No contacts found')" icon="users">
                    <x-slot:description>
                        {{ ($search || $type) ? __('Try adjusting your filters or search terms.') : __('Get started by creating your first contact.') }}
                    </x-slot:description>

                    @can('create', App\Models\Contact::class)
                        @if(!$search && !$type)
                            <x-slot:action>
                                <flux:button href="{{ route('contacts.create') }}" icon="plus">
                                    {{ __('Create Contact') }}
                                </flux:button>
                            </x-slot:action>
                        @endif
                    @endcan
                </x-data-table.empty>
            @endif
        </x-data-table.wrapper>
</x-page-layout>
