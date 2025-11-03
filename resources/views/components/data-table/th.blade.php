@props([
    'sortable' => null,
    'sortBy' => null,
    'sortDirection' => 'asc',
])

@php
    $isSorted = $sortable && $sortBy === $sortable;
    $classes = 'px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider';

    if ($sortable) {
        $classes .= ' cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-300';
    }
@endphp

<th
    {{ $attributes->merge(['class' => $classes]) }}
    @if($sortable) wire:click="updateSorting('{{ $sortable }}')" @endif
>
    <div class="flex items-center gap-1">
        {{ $slot }}

        @if($isSorted)
            <span class="text-sm">
                {{ $sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
        @endif
    </div>
</th>
