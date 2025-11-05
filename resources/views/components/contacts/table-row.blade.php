@props(['contact'])

@php
$typeBadgeColor = match($contact->type) {
    'customer' => 'blue',
    'vendor' => 'purple',
    'both' => 'green',
    default => 'zinc',
};
@endphp

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
        <flux:badge :color="$typeBadgeColor" size="sm">
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
        <a
            href="{{ route('contacts.edit', $contact) }}"
            class="text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-300"
            wire:navigate
        >
            {{ __('Edit') }}
        </a>
    </td>
</tr>
