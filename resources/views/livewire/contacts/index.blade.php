<?php

use App\Models\Contact;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $type = '';
    public string $sortBy = 'name';
    public string $sortDirection = 'asc';

    #[Computed]
    public function contacts()
    {
        $query = Contact::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->type) {
            if ($this->type === 'customer') {
                $query->whereIn('type', ['customer', 'both']);
            } elseif ($this->type === 'vendor') {
                $query->whereIn('type', ['vendor', 'both']);
            }
        }

        $query->orderBy($this->sortBy, $this->sortDirection);

        return $query->paginate(15);
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'asc';
        }
    }

    public function getTypeBadgeColor(string $type): string
    {
        return match ($type) {
            'customer' => 'blue',
            'vendor' => 'purple',
            'both' => 'green',
            default => 'zinc',
        };
    }
}; ?>

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
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow mb-6">
            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div>
                    <flux:input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search contacts..."
                        icon="magnifying-glass"
                    />
                </div>

                <!-- Type Filter -->
                <div>
                    <flux:select wire:model.live="type" placeholder="All Types">
                        <option value="">All Types</option>
                        <option value="customer">Customers</option>
                        <option value="vendor">Vendors</option>
                        <option value="both">Both</option>
                    </flux:select>
                </div>

                <!-- Clear Filters -->
                @if($search || $type)
                    <div class="flex items-end">
                        <flux:button
                            wire:click="$set('search', ''); $set('type', '')"
                            variant="ghost"
                        >
                            Clear Filters
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        <!-- Contacts Table -->
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow overflow-hidden">
            @if($this->contacts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th
                                    wire:click="sortBy('name')"
                                    class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300"
                                >
                                    Name
                                    @if($sortBy === 'name')
                                        <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                    @endif
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Location
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($this->contacts as $contact)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $contact->name }}
                                        </div>
                                        @if($contact->contact_person)
                                            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                                                {{ $contact->contact_person }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <flux:badge :color="$this->getTypeBadgeColor($contact->type)" size="sm">
                                            {{ ucfirst($contact->type) }}
                                        </flux:badge>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        @if($contact->email)
                                            <div>{{ $contact->email }}</div>
                                        @endif
                                        @if($contact->phone)
                                            <div>{{ $contact->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-400">
                                        @if($contact->city)
                                            {{ $contact->postal_code }} {{ $contact->city }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <a
                                                href="{{ route('contacts.edit', $contact) }}"
                                                class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300"
                                                wire:navigate
                                            >
                                                Edit
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                    {{ $this->contacts->links() }}
                </div>
            @else
                <div class="p-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-zinc-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-1">
                        No contacts found
                    </h3>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                        @if($search || $type)
                            Try adjusting your filters or search terms.
                        @else
                            Get started by creating your first contact.
                        @endif
                    </p>
                    @can('create', App\Models\Contact::class)
                        @if(!$search && !$type)
                            <flux:button href="{{ route('contacts.create') }}" icon="plus">
                                Create Contact
                            </flux:button>
                        @endif
                    @endcan
                </div>
            @endif
        </div>
    </div>
</div>
